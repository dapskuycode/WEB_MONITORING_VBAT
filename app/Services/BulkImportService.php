<?php

namespace App\Services;

use App\Models\LearningMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkImportService
{
    /**
     * Supported MIME types for import.
     */
    public const SUPPORTED_MIMES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv',
        'text/plain',
    ];

    public const SUPPORTED_EXTENSIONS = ['xlsx', 'xls', 'csv'];

    /**
     * Canonical columns expected in the import template.
     */
    public const TEMPLATE_COLUMNS = [
        'lesson_id',
        'unit_code',
        'unit_title',
        'material_type',
        'title',
        'description',
        'youtube_url',
        'external_url',
        'content_text',
        'status',
    ];

    /**
     * Preview import data without committing (dry-run).
     *
     * @return array{success: bool, data: array|null, errors: array|null}
     */
    public function preview(string $filePath, int $maxRows = 20): array
    {
        $rows = $this->readRows($filePath);
        if ($rows === null) {
            return ['success' => false, 'data' => null, 'errors' => ['file' => ['Cannot read file or file is empty']]];
        }

        if (count($rows) < 2) {
            return ['success' => false, 'data' => null, 'errors' => ['file' => ['File is empty or has no data rows']]];
        }

        $header = $this->normalizeHeader(array_values($rows[0]));
        $dataRows = array_slice($rows, 1, $maxRows);

        $preview = [];
        $validationErrors = [];

        foreach ($dataRows as $idx => $row) {
            $rowValues = array_values($row);

            if ($this->isEmptyRow($rowValues)) {
                continue;
            }

            $record = $this->mapRowToRecord($header, $rowValues);
            $preview[] = $record;

            $errors = $this->validateRecord($record);
            if (! empty($errors)) {
                $validationErrors['row_' . ($idx + 2)] = $errors;
            }
        }

        return [
            'success' => true,
            'data' => [
                'total_rows' => count($rows) - 1,
                'header' => $header,
                'expected_columns' => self::TEMPLATE_COLUMNS,
                'preview' => $preview,
                'validation_errors' => $validationErrors,
                'preview_limit' => $maxRows,
                'is_valid' => empty($validationErrors),
            ],
            'errors' => null,
        ];
    }

    /**
     * Commit import: insert validated rows into learning_materials.
     *
     * @return array{success: bool, data: array|null, errors: array|null}
     */
    public function commit(string $filePath, bool $skipInvalid = false): array
    {
        $rows = $this->readRows($filePath);
        if ($rows === null) {
            return ['success' => false, 'data' => null, 'errors' => ['file' => ['Cannot read file or file is empty']]];
        }

        if (count($rows) < 2) {
            return ['success' => false, 'data' => null, 'errors' => ['file' => ['File is empty or has no data rows']]];
        }

        $header = $this->normalizeHeader(array_values($rows[0]));
        $dataRows = array_slice($rows, 1);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($dataRows as $idx => $row) {
                $rowValues = array_values($row);

                if ($this->isEmptyRow($rowValues)) {
                    continue;
                }

                $record = $this->mapRowToRecord($header, $rowValues);
                $recordErrors = $this->validateRecord($record);

                if (! empty($recordErrors)) {
                    if ($skipInvalid) {
                        $skipped++;
                        $errors['row_' . ($idx + 2)] = $recordErrors;
                        continue;
                    }
                    DB::rollBack();
                    return [
                        'success' => false,
                        'data' => ['imported' => $imported, 'failed_at_row' => $idx + 2],
                        'errors' => ['row_' . ($idx + 2) => $recordErrors],
                    ];
                }

                // Defaults
                $record['status'] = $record['status'] ?? 'draft';
                $record['material_type'] = $record['material_type'] ?? 'text_content';
                $record['sort_order'] = $record['sort_order'] ?? 0;
                $record['is_required'] = $record['is_required'] ?? true;
                $record['quiz_required'] = $record['quiz_required'] ?? false;
                $record['duration_seconds'] = $record['duration_seconds'] ?? 0;

                // Auto-generate unique unit_code if absent
                if (empty($record['unit_code'])) {
                    $record['unit_code'] = 'MAT-' . strtoupper(Str::random(8));
                }

                // unit_title falls back to title
                if (empty($record['unit_title'])) {
                    $record['unit_title'] = $record['title'];
                }

                // Extract YouTube video id when applicable
                if ($record['material_type'] === 'youtube_video' && ! empty($record['youtube_url'])) {
                    $videoId = LearningMaterial::extractYouTubeId($record['youtube_url']);
                    if ($videoId) {
                        $record['youtube_video_id'] = $videoId;
                    }
                }

                LearningMaterial::create($record);
                $imported++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['success' => false, 'data' => ['imported' => $imported], 'errors' => ['general' => [$e->getMessage()]]];
        }

        return [
            'success' => true,
            'data' => [
                'imported' => $imported,
                'skipped' => $skipped,
                'total_rows' => count($dataRows),
            ],
            'errors' => empty($errors) ? null : $errors,
        ];
    }

    /**
     * Read all rows from an uploaded spreadsheet/CSV file.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function readRows(string $filePath): ?array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            return $sheet->toArray(null, true, true, true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isEmptyRow(array $rowValues): bool
    {
        return empty(array_filter($rowValues, fn ($v) => $v !== null && trim((string) $v) !== ''));
    }

    /**
     * Normalize header row: lowercase, strip spaces, map aliases.
     */
    private function normalizeHeader(array $header): array
    {
        $aliasMap = [
            'title' => 'title',
            'judul' => 'title',
            'name' => 'title',
            'description' => 'description',
            'deskripsi' => 'description',
            'desc' => 'description',
            'material_type' => 'material_type',
            'tipe' => 'material_type',
            'type' => 'material_type',
            'youtube_url' => 'youtube_url',
            'url_video' => 'youtube_url',
            'video_url' => 'youtube_url',
            'duration_seconds' => 'duration_seconds',
            'durasi' => 'duration_seconds',
            'duration' => 'duration_seconds',
            'status' => 'status',
            'lesson_id' => 'lesson_id',
            'pelajaran' => 'lesson_id',
            'unit_code' => 'unit_code',
            'kode' => 'unit_code',
            'unit_title' => 'unit_title',
            'content_text' => 'content_text',
            'content' => 'content_text',
            'external_url' => 'external_url',
            'sort_order' => 'sort_order',
            'is_required' => 'is_required',
        ];

        return array_map(function ($col) use ($aliasMap) {
            $normalized = strtolower(trim((string) $col));

            return $aliasMap[$normalized] ?? $normalized;
        }, $header);
    }

    /**
     * Map row values to associative record by header keys.
     */
    private function mapRowToRecord(array $header, array $rowValues): array
    {
        $record = [];
        foreach ($header as $i => $key) {
            $value = $rowValues[$i] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                $record[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $record;
    }

    /**
     * Validate a single record against LearningMaterial rules.
     *
     * @return array<string, list<string>>
     */
    private function validateRecord(array $record): array
    {
        $validator = Validator::make($record, [
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'unit_code' => ['nullable', 'string', 'max:50', 'unique:learning_materials,unit_code'],
            'unit_title' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'material_type' => ['nullable', 'in:youtube_video,pdf_document,text_content,external_link'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'content_text' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:published,draft,archived'],
        ]);

        return $validator->errors()->messages();
    }
}
