<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LmsImport;
use App\Services\BulkImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LmsImportController extends Controller
{
    public function preview(Request $request, BulkImportService $service): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:5120', 'user_id' => 'prohibited', 'skip_invalid' => 'prohibited']);
        $file = $request->file('file');
        $preview = $service->preview($file->getRealPath(), $request->user(), strtolower($file->getClientOriginalExtension()));

        return response()->json(['success' => true, 'data' => $service->data($preview)])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, string $id, BulkImportService $service): JsonResponse
    {
        $preview = LmsImport::whereKey($id)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(['success' => true, 'data' => $service->data($preview)])->header('Cache-Control', 'private, no-store');
    }

    public function apply(Request $request, string $id, BulkImportService $service): JsonResponse
    {
        $request->validate(['rows' => 'prohibited', 'skip_invalid' => 'prohibited', 'user_id' => 'prohibited']);

        return response()->json(['success' => true, 'data' => $service->apply($id, $request->user())])->header('Cache-Control', 'private, no-store');
    }

    public function template(BulkImportService $service): BinaryFileResponse
    {
        return response()->download($service->template(), 'template-lms.xlsx', ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }

    public function errors(Request $request, string $id): BinaryFileResponse
    {
        $preview = LmsImport::whereKey($id)->where('user_id', $request->user()->id)->firstOrFail();

        return self::errorDownload($preview);
    }

    public static function errorDownload(LmsImport $preview): BinaryFileResponse
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $fields = ['row', 'title', 'field', 'message', 'suggestion'];
        $sheet->fromArray($fields);
        foreach ($preview->errors as $index => $error) {
            foreach ($fields as $column => $field) {
                $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($column + 1).($index + 2),
                    (string) $error[$field], DataType::TYPE_STRING);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'lms-errors-');
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();

        return response()->download($path, 'laporan-import.xlsx', ['Cache-Control' => 'private, no-store'])->deleteFileAfterSend();
    }
}
