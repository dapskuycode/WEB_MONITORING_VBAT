<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsImport;
use App\Models\LmsUpload;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BulkImportService
{
    public const SUPPORTED_EXTENSIONS = ['xlsx', 'csv'];

    public const SUPPORTED_MIMES = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'text/plain'];

    public const TEMPLATE_COLUMNS = ['course_code', 'module_code', 'module_title', 'module_order', 'material_code', 'material_title', 'material_order', 'material_type', 'youtube_url', 'file_name', 'duration_seconds', 'is_required', 'is_preview', 'quiz_position', 'quiz_code', 'file_upload_id'];

    public function template(): string
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray(self::TEMPLATE_COLUMNS);
        $path = tempnam(sys_get_temp_dir(), 'lms-template-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return $path;
    }

    public function preview(string $path, User $user, string $extension): LmsImport
    {
        abort_unless($user->isSuperAdmin(), 403);
        $raw = $this->read($path, $extension);
        $headers = array_map(fn ($v) => trim(str_replace("\xEF\xBB\xBF", '', (string) $v)), array_shift($raw));
        if (count(array_unique($headers)) !== count($headers) || array_diff(array_slice(self::TEMPLATE_COLUMNS, 0, 15), $headers) || array_diff($headers, self::TEMPLATE_COLUMNS)) {
            throw ValidationException::withMessages(['file' => 'Header harus mengikuti template FSD; file_upload_id adalah kolom tambahan opsional.']);
        }
        $rows = [];
        $errors = [];
        $snapshot = ['courses' => [], 'lessons' => [], 'uploads' => []];
        $codes = [];
        $orders = [];
        $quizCodes = [];
        foreach ($raw as $index => $values) {
            if (count(array_filter($values, fn ($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue;
            }
            $rowNumber = $index + 2;
            $row = array_fill_keys($headers, null);
            foreach ($values as $column => $value) {
                if ($column >= count($headers) && $value !== null && $value !== '') {
                    throw ValidationException::withMessages(['file' => 'Ada data di luar header template.']);
                }
                if ($column < count($headers)) {
                    $row[$headers[$column]] = is_string($value) ? trim($value) : $value;
                }
            }
            try {
                $record = $this->validateRow($row, $user, $snapshot);
                if (isset($codes[$record['unit_code']])) {
                    throw ValidationException::withMessages(['material_code' => 'Kode materi duplikat di berkas.']);
                }
                $order = $record['lesson_id'].':'.$record['sort_order'];
                if (isset($orders[$order])) {
                    throw ValidationException::withMessages(['material_order' => 'Urutan materi duplikat dalam modul.']);
                }
                if ($record['_quiz_code'] && isset($quizCodes[$record['_quiz_code']])) {
                    throw ValidationException::withMessages(['quiz_code' => 'Kode kuis duplikat di berkas.']);
                }
                $codes[$record['unit_code']] = true;
                $orders[$order] = true;
                if ($record['_quiz_code']) {
                    $quizCodes[$record['_quiz_code']] = true;
                }
                $rows[] = ['row' => $rowNumber, 'record' => $record];
            } catch (ValidationException $e) {
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = ['row' => $rowNumber, 'title' => (string) ($row['material_title'] ?? ''), 'field' => $field, 'message' => $message, 'suggestion' => 'Perbaiki field sesuai template dan buat preview baru.'];
                    }
                }
            }
        }
        if (! $rows && ! $errors) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak berisi baris materi.']);
        }
        foreach ($rows as $entry) {
            $r = $entry['record'];
            if ($r['_quiz_position'] === 'end_of_module') {
                $max = max(LearningMaterial::where('lesson_id', $r['lesson_id'])->where('status', '!=', 'archived')->max('sort_order') ?? -1,
                    ...array_map(fn ($x) => $x['record']['lesson_id'] === $r['lesson_id'] ? $x['record']['sort_order'] : -1, $rows));
                if ($r['sort_order'] !== $max) {
                    $errors[] = ['row' => $entry['row'], 'title' => $r['title'], 'field' => 'quiz_position', 'message' => 'Kuis akhir modul harus berada pada materi dengan urutan terakhir.', 'suggestion' => 'Pindahkan kode kuis ke baris materi terakhir modul.'];
                }
            }
        }
        $invalidRows = array_unique(array_column($errors, 'row'));
        $rows = array_values(array_filter($rows, fn ($row) => ! in_array($row['row'], $invalidRows, true)));

        return LmsImport::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'file_hash' => hash_file('sha256', $path),
            'rows' => $rows, 'errors' => $errors, 'snapshot' => $snapshot, 'expires_at' => now()->addMinutes(30)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function apply(string $id, User $user): array
    {
        abort_unless($user->isSuperAdmin(), 403);

        return DB::transaction(function () use ($id, $user) {
            $preview = LmsImport::whereKey($id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($preview->status === 'applied') {
                return $preview->result;
            }
            abort_unless($preview->expires_at->isFuture(), 409, 'Preview kedaluwarsa. Unggah untuk preview baru.');
            abort_if(count($preview->errors) > 0, 422, 'Seluruh import dibatalkan; perbaiki semua baris.');
            $snapshot = $preview->snapshot;
            $courseIds = array_keys($snapshot['courses']);
            sort($courseIds);
            foreach ($courseIds as $courseId) {
                $course = Course::whereKey($courseId)->lockForUpdate()->firstOrFail();
                abort_unless($this->courseSignature($course) === $snapshot['courses'][$courseId], 409, 'Kelas berubah sejak preview.');
            }
            $lessonIds = array_keys($snapshot['lessons']);
            sort($lessonIds);
            foreach ($lessonIds as $lessonId) {
                $lesson = Lesson::whereKey($lessonId)->lockForUpdate()->firstOrFail();
                abort_unless($this->lessonSignature($lesson) === $snapshot['lessons'][$lessonId], 409, 'Modul berubah sejak preview.');
            }
            foreach ($snapshot['uploads'] as $uploadId => $hash) {
                $upload = app(LmsUploadService::class)->ready($uploadId, $user);
                abort_unless($upload->sha256 === $hash, 409, 'Sumber file berubah sejak preview.');
            }
            foreach ($preview->rows as $entry) {
                $r = $entry['record'];
                abort_if(LearningMaterial::withTrashed()->where('unit_code', $r['unit_code'])->exists() || LearningMaterial::withTrashed()->where('lesson_id', $r['lesson_id'])->where('sort_order', $r['sort_order'])->exists(), 409, 'Kode atau urutan sudah digunakan sejak preview.');
                abort_if($r['_quiz_code'] && Quiz::where('code', $r['_quiz_code'])->exists(), 409, 'Kode kuis sudah digunakan sejak preview.');
            }
            $ids = [];
            foreach ($preview->rows as $entry) {
                $r = $entry['record'];
                $code = $r['_quiz_code'];
                unset($r['_quiz_code'], $r['_quiz_position']);
                $material = app(LmsAuthoringService::class)->saveMaterial($r);
                if ($code) {
                    $quiz = Quiz::create(['code' => $code, 'title' => 'Kuis '.$code, 'learning_material_id' => $material->id, 'is_active' => false]);
                    app(LmsAuthoringService::class)->saveMaterial([...$material->getAttributes(), 'quiz_id' => $quiz->id, 'quiz_required' => true, 'expected_revision' => $material->revision], $material->id);
                }
                $ids[] = $material->id;
            }
            $result = ['imported' => count($ids), 'material_ids' => $ids, 'status' => 'draft'];
            $preview->update(['status' => 'applied', 'result' => $result]);
            foreach ($courseIds as $courseId) {
                AuditLog::create(['actor_type' => 'admin', 'actor_id' => $user->id, 'action' => 'lms.import_applied',
                    'auditable_type' => Course::class, 'auditable_id' => $courseId,
                    'new_values' => ['import_id' => $preview->id, 'file_hash' => $preview->file_hash, ...$result], 'old_values' => [], 'ip_address' => request()->ip()]);
            }

            return $result;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function data(LmsImport $import): array
    {
        return ['id' => $import->id, 'status' => $import->status, 'expires_at' => $import->expires_at->toIso8601String(), 'file_hash' => $import->file_hash,
            'total_rows' => count($import->rows) + count(array_unique(array_column($import->errors, 'row'))), 'ready_rows' => count($import->rows),
            'is_valid' => count($import->errors) === 0, 'errors' => $import->errors,
            'preview' => array_map(fn ($e) => ['row' => $e['row'], 'material_code' => $e['record']['unit_code'], 'title' => $e['record']['title'], 'status' => 'draft'], $import->rows), 'result' => $import->result];
    }

    /**
     * @param  array<string, mixed>  $r
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function validateRow(array $r, User $user, array &$snapshot): array
    {
        foreach (['is_required', 'is_preview'] as $flag) {
            $value = strtoupper((string) ($r[$flag] ?? ''));
            $r[$flag] = match ($value) {
                'TRUE', '1' => true, 'FALSE', '0' => false, default => $r[$flag]
            };
        }
        $r = Validator::make($r, ['course_code' => 'required|alpha_dash|max:50', 'module_code' => 'required|alpha_dash|max:50',
            'module_title' => 'required|string|max:255', 'module_order' => 'required|integer|min:0',
            'material_code' => 'required|alpha_dash|max:50', 'material_title' => 'required|string|max:255', 'material_order' => 'required|integer|min:0',
            'material_type' => 'required|in:youtube_video,pdf,ebook', 'youtube_url' => 'nullable|url|max:500', 'file_name' => 'nullable|string|max:200',
            'file_upload_id' => 'nullable|uuid', 'duration_seconds' => 'nullable|integer|min:0|max:86400', 'is_required' => 'required|boolean', 'is_preview' => 'required|boolean',
            'quiz_code' => 'nullable|alpha_dash|max:50', 'quiz_position' => 'nullable|in:after_material,end_of_module'])->validate();
        $course = Course::where('code', $r['course_code'])->where('status', '!=', 'archived')->whereNull('archived_at')->first();
        $lesson = $course ? Lesson::where('course_id', $course->id)->where('code', $r['module_code'])->where('status', '!=', 'archived')->first() : null;
        if (! $course || ! $lesson) {
            throw ValidationException::withMessages(['module_code' => 'Kode kelas/modul belum ada atau sudah diarsipkan. Buat melalui CMS dahulu.']);
        }
        if ($lesson->title !== $r['module_title'] || $lesson->sort_order !== (int) $r['module_order']) {
            throw ValidationException::withMessages(['module_title' => 'Judul/urutan modul tidak cocok dengan CMS; import tidak mengubah modul existing.']);
        }
        if (LearningMaterial::withTrashed()->where('unit_code', $r['material_code'])->exists()) {
            throw ValidationException::withMessages(['material_code' => 'Kode materi sudah ada; import ini hanya membuat materi baru.']);
        }
        if (LearningMaterial::withTrashed()->where('lesson_id', $lesson->id)->where('sort_order', $r['material_order'])->exists()) {
            throw ValidationException::withMessages(['material_order' => 'Urutan materi sudah terpakai.']);
        }
        $pdfId = null;
        if ($r['material_type'] === 'youtube_video') {
            if (! app(YouTubeValidationService::class)->extractVideoId($r['youtube_url'] ?? null)) {
                throw ValidationException::withMessages(['youtube_url' => 'Tautan YouTube tidak valid.']);
            }
            if (! empty($r['file_name']) || ! empty($r['file_upload_id'])) {
                throw ValidationException::withMessages(['file_name' => 'Baris video tidak menerima sumber PDF.']);
            }
        } else {
            if (! empty($r['youtube_url'])) {
                throw ValidationException::withMessages(['youtube_url' => 'Baris PDF/e-book tidak menerima sumber video.']);
            }
            $matches = LmsUpload::where('user_id', $user->id)->where('purpose', 'course_material')->where('status', 'ready');
            if (! empty($r['file_upload_id'])) {
                $matches->whereKey($r['file_upload_id']);
            } else {
                $matches->where('original_name', $r['file_name'] ?? '');
            }
            $uploads = $matches->limit(2)->get();
            if ($uploads->count() !== 1) {
                throw ValidationException::withMessages(['file_upload_id' => 'File siap tidak ditemukan atau namanya ambigu. Gunakan ID upload milik akun ini.']);
            }
            $upload = $uploads->first();
            if (! empty($r['file_name']) && $r['file_name'] !== $upload->original_name) {
                throw ValidationException::withMessages(['file_name' => 'Nama tidak cocok dengan ID upload.']);
            }
            try {
                app(LmsUploadService::class)->ready($upload->id, $user);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['file_upload_id' => 'Berkas tidak lolos pemeriksaan integritas/privasi.']);
            }
            $pdfId = $upload->id;
            $snapshot['uploads'][$pdfId] = $upload->sha256;
        }
        if (! empty($r['quiz_code']) !== ! empty($r['quiz_position'])) {
            throw ValidationException::withMessages(['quiz_code' => 'Kode dan posisi kuis harus diisi bersama.']);
        }
        if (! empty($r['quiz_code']) && Quiz::where('code', $r['quiz_code'])->exists()) {
            throw ValidationException::withMessages(['quiz_code' => 'Kode kuis sudah ada; tidak akan ditimpa/dipindahkan.']);
        }
        $snapshot['courses'][$course->id] = $this->courseSignature($course);
        $snapshot['lessons'][$lesson->id] = $this->lessonSignature($lesson);

        return ['lesson_id' => $lesson->id, 'unit_code' => $r['material_code'], 'unit_title' => $r['material_title'], 'title' => $r['material_title'],
            'sort_order' => (int) $r['material_order'], 'material_type' => $r['material_type'] === 'youtube_video' ? 'youtube_video' : 'pdf_document',
            'youtube_url' => $r['youtube_url'] ?? null, 'pdf_upload_id' => $pdfId, 'duration_seconds' => $r['material_type'] === 'youtube_video' ? (int) ($r['duration_seconds'] ?? 0) : 0,
            'is_required' => $r['is_required'], 'is_preview' => $r['is_preview'], 'status' => 'draft',
            '_quiz_code' => $r['quiz_code'] ?? null, '_quiz_position' => $r['quiz_position'] ?? null];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function read(string $path, string $extension): array
    {
        if (! in_array($extension, self::SUPPORTED_EXTENSIONS, true) || ! is_file($path) || filesize($path) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['file' => 'Gunakan XLSX/CSV maksimal 5 MB.']);
        }
        $rows = [];
        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw ValidationException::withMessages(['file' => 'Berkas tidak dapat dibuka.']);
            }
            try {
                while (($row = fgetcsv($handle, 100000, ',', '"', '')) !== false) {
                    foreach ($row as $value) {
                        if (is_string($value) && preg_match('/^\s*[=+@]/u', $value)) {
                            throw ValidationException::withMessages(['file' => 'CSV mengandung ekspresi/formula yang tidak didukung.']);
                        }
                    }
                    $rows[] = $row;
                    if (count($rows) > 1001) {
                        throw ValidationException::withMessages(['file' => 'Maksimal 1000 baris materi.']);
                    }
                }
            } finally {
                fclose($handle);
            }
        } else {
            $zip = new \ZipArchive;
            if ($zip->open($path) !== true) {
                throw ValidationException::withMessages(['file' => 'Berkas bukan XLSX valid.']);
            }
            try {
                $total = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->statIndex($i);
                    if ($entry === false) {
                        throw ValidationException::withMessages(['file' => 'Arsip XLSX rusak.']);
                    }
                    $name = $entry['name'];
                    $total += $entry['size'];
                    if ($zip->numFiles > 2000 || $total > 20 * 1024 * 1024 || preg_match('~vbaProject|externalLinks|\.\./~i', $name)) {
                        throw ValidationException::withMessages(['file' => 'Workbook terlalu besar atau mengandung fitur aktif/tautan eksternal.']);
                    }
                    $contents = $zip->getFromIndex($i);
                    if ($contents === false) {
                        throw ValidationException::withMessages(['file' => 'Isi arsip tidak dapat dibaca.']);
                    }
                    if (str_ends_with($name, '.rels') && preg_match('/TargetMode\s*=\s*["\x27]External["\x27]/i', $contents)) {
                        throw ValidationException::withMessages(['file' => 'Tautan eksternal tidak didukung.']);
                    }
                    if (str_starts_with($name, 'xl/') && str_ends_with($name, '.xml')) {
                        $xml = $contents;
                        if (substr_count($xml, '<') > 100000 || preg_match_all('/<(?:\w+:)?(?:si|xf)\b/', $xml) > 20000) {
                            throw ValidationException::withMessages(['file' => 'Struktur XML workbook melebihi batas aman.']);
                        }
                        if (str_starts_with($name, 'xl/worksheets/') && (preg_match_all('/<(?:\w+:)?c\b/', $xml) > 16016 || preg_match_all('/<(?:\w+:)?row\b/', $xml) > 1001)) {
                            throw ValidationException::withMessages(['file' => 'Jumlah sel/baris melampaui batas.']);
                        }
                    }
                    if (preg_match('~^xl/worksheets/sheet\d+\.xml$~', $name) && preg_match('/<dimension[^>]*ref="(?:[A-Z]+\d+:)?([A-Z]+)(\d+)"/', $contents, $m)
                        && ((int) $m[2] > 1001 || Coordinate::columnIndexFromString($m[1]) > 16)) {
                        throw ValidationException::withMessages(['file' => 'Worksheet melebihi batas baris/kolom.']);
                    }
                }
            } finally {
                $zip->close();
            }
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(false);
            try {
                $book = $reader->load($path);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['file' => 'Workbook tidak dapat dibaca.']);
            }
            try {
                if ($book->getSheetCount() !== 1) {
                    throw ValidationException::withMessages(['file' => 'Gunakan tepat satu sheet.']);
                }
                $sheet = $book->getSheet(0);
                if ($sheet->getHighestRow() > 1001 || Coordinate::columnIndexFromString($sheet->getHighestColumn()) > 16) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 1000 baris dan 16 kolom.']);
                }
                foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                    if ($sheet->getCell($coordinate)->getDataType() === DataType::TYPE_FORMULA) {
                        throw ValidationException::withMessages(['file' => 'Formula tidak didukung; gunakan nilai literal.']);
                    }
                }
                $rows = $sheet->toArray(null, false, false, false);
            } finally {
                $book->disconnectWorksheets();
            }
        }
        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'Template kosong; isi minimal satu materi.']);
        }

        return $rows;
    }

    private function courseSignature(Course $course): string
    {
        return hash('sha256', json_encode($course->getAttributes(), JSON_THROW_ON_ERROR));
    }

    private function lessonSignature(Lesson $lesson): string
    {
        return hash('sha256', json_encode($lesson->getAttributes(), JSON_THROW_ON_ERROR));
    }
}
