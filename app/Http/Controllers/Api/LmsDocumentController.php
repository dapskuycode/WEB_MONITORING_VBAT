<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LmsUpload;
use App\Services\LmsReadingService;
use App\Services\LmsUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LmsDocumentController extends Controller
{
    public function upload(Request $request, LmsUploadService $service): JsonResponse
    {
        $data = $request->validate(['file' => 'required|file', 'purpose' => 'required|in:course_material']);

        return response()->json(['success' => true, 'data' => $service->data($service->receive($request->user(), $data['file'], $data['purpose']))], 202)->header('Cache-Control', 'private, no-store');
    }

    public function status(Request $request, string $id, LmsUploadService $service): JsonResponse
    {
        $upload = LmsUpload::whereKey($id)->where('user_id', $request->user()->id)->firstOrFail();

        return response()->json(['success' => true, 'data' => $service->data($upload)])->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request, int $id, LmsReadingService $service): JsonResponse
    {
        $data = $request->validate(['request_id' => 'required|uuid', 'user_id' => 'prohibited']);

        return response()->json(['success' => true, 'data' => $service->start($request->user(), $id, $data['request_id'])])->header('Cache-Control', 'private, no-store');
    }

    public function event(Request $request, string $id, LmsReadingService $service): JsonResponse
    {
        $data = $request->validate(['event_id' => 'required|uuid', 'sequence' => 'required|integer|min:1', 'page' => 'required|integer|min:1|max:1000',
            'confirm_read' => 'required|boolean', 'progress_percent' => 'prohibited', 'user_id' => 'prohibited']);
        $data['sequence'] = (int) $data['sequence'];
        $data['page'] = (int) $data['page'];
        $data['confirm_read'] = (bool) $data['confirm_read'];

        return response()->json(['success' => true, 'data' => $service->event($request->user(), $id, $data)])->header('Cache-Control', 'private, no-store');
    }
}
