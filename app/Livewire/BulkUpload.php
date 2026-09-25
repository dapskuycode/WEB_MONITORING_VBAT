<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LearningMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkUpload extends Component
{
    use WithFileUploads;

    public $file;
    public $preview = [];
    public $importResult = null;

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'course_slug');
        $sheet->setCellValue('B1', 'lesson_slug');
        $sheet->setCellValue('C1', 'unit_code');
        $sheet->setCellValue('D1', 'material_type');
        $sheet->setCellValue('E1', 'title');
        $sheet->setCellValue('F1', 'description');
        $sheet->setCellValue('G1', 'youtube_url');
        $sheet->setCellValue('H1', 'pdf_key');
        $sheet->setCellValue('I1', 'sort_order');
        $sheet->setCellValue('J1', 'is_required');
        $sheet->setCellValue('K1', 'status');

        $sheet->setCellValue('A2', 'android-basic');
        $sheet->setCellValue('B2', 'intro');
        $sheet->setCellValue('C2', 'UNIT-001');
        $sheet->setCellValue('D2', 'video');
        $sheet->setCellValue('E2', 'Introduction to Android');
        $sheet->setCellValue('F2', 'Basic concepts');
        $sheet->setCellValue('G2', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $sheet->setCellValue('H2', '');
        $sheet->setCellValue('I2', '1');
        $sheet->setCellValue('J2', '1');
        $sheet->setCellValue('K2', 'published');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'bulk_upload_template_' . date('Ymd_His') . '.xlsx';
        $tempFile = storage_path('app/' . $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend();
    }

    public function previewFile()
    {
        $this->validate(['file' => 'required|mimes:xlsx,xls|max:10240']);

        try {
            $path = $this->file->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $this->preview = [];
            $header = array_shift($rows);

            foreach ($rows as $idx => $row) {
                if (empty(array_filter($row))) continue;
                
                $this->preview[] = [
                    'row' => $idx + 2,
                    'course_slug' => $row[0] ?? '',
                    'lesson_slug' => $row[1] ?? '',
                    'unit_code' => $row[2] ?? '',
                    'material_type' => $row[3] ?? '',
                    'title' => $row[4] ?? '',
                    'description' => $row[5] ?? '',
                    'youtube_url' => $row[6] ?? '',
                    'pdf_key' => $row[7] ?? '',
                    'sort_order' => $row[8] ?? 0,
                    'is_required' => $row[9] ?? 0,
                    'status' => $row[10] ?? 'draft',
                ];
            }

            session()->flash('message', 'Preview loaded: ' . count($this->preview) . ' rows.');
        } catch (\Exception $e) {
            Log::error('Bulk upload preview error: ' . $e->getMessage());
            session()->flash('error', 'Failed to read file: ' . $e->getMessage());
        }
    }

    public function importData()
    {
        if (empty($this->preview)) {
            session()->flash('error', 'No preview data. Please upload and preview first.');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($this->preview as $item) {
                $course = Course::where('slug', $item['course_slug'])->first();
                if (!$course) {
                    $errors[] = "Row {$item['row']}: Course '{$item['course_slug']}' not found.";
                    $skipped++;
                    continue;
                }

                $lesson = Lesson::where('slug', $item['lesson_slug'])
                    ->where('course_id', $course->id)
                    ->first();
                if (!$lesson) {
                    $errors[] = "Row {$item['row']}: Lesson '{$item['lesson_slug']}' not found.";
                    $skipped++;
                    continue;
                }

                $existing = LearningMaterial::where('lesson_id', $lesson->id)
                    ->where('unit_code', $item['unit_code'])
                    ->first();

                $data = [
                    'lesson_id' => $lesson->id,
                    'unit_code' => $item['unit_code'],
                    'material_type' => $item['material_type'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'youtube_url' => $item['youtube_url'],
                    'pdf_object_key' => $item['pdf_key'],
                    'sort_order' => (int)$item['sort_order'],
                    'is_required' => (bool)$item['is_required'],
                    'status' => $item['status'],
                ];

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    LearningMaterial::create($data);
                    $created++;
                }
            }

            DB::commit();
            $this->importResult = compact('created', 'updated', 'skipped', 'errors');
            $this->preview = [];
            session()->flash('message', "Import complete: $created created, $updated updated, $skipped skipped.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk import error: ' . $e->getMessage());
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.bulk-upload');
    }
}
