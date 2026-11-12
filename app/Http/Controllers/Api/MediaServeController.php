<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\LmsFileService;
use App\Services\LmsUploadService;
use App\Services\MediaAuthorizationService;
use App\Services\MediaProxyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaServeController extends Controller
{
    public function __construct(private MediaAuthorizationService $authService, private MediaProxyService $proxyService) {}

    public function serve(Request $request, string $type, int $id): BinaryFileResponse|JsonResponse
    {
        abort_unless($request->hasValidSignature() && (string) $request->query('user') === (string) $request->user()->id, 403);
        $material = LearningMaterial::with('lesson.course')->findOrFail($id);
        abort_unless($this->authService->canAccessMaterial($request->user(), $material), 403);
        if ($type === 'pdf' && $material->material_type === 'pdf_document') {
            $path = app(LmsFileService::class)->path('lms', $material->pdf_path);
            abort_unless($path !== null, 404);
            abort_unless($material->pdf_upload_id !== null, 422, 'Dokumen lama perlu pemeriksaan sebelum disajikan.');
            $upload = app(LmsUploadService::class)->ready($material->pdf_upload_id);
            abort_unless($material->pdf_path === $upload->path, 409);

            return response()->file($path, [
                'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }
        if ($type === 'video' && $material->material_type === 'youtube_video' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $material->youtube_video_id ?? '')) {
            return response()->json([
                'provider' => 'youtube', 'video_id' => $material->youtube_video_id,
                'embed_url' => 'https://www.youtube.com/embed/'.$material->youtube_video_id,
                'grant_expires_at' => (int) $request->query('expires'),
                'provider_url_expires' => false,
            ])->header('Cache-Control', 'private, no-store');
        }
        abort(404);
    }

    public function checkAccess(Request $request): JsonResponse
    {
        $data = $request->validate([
            'material_ids' => 'required|array|min:1|max:50', 'material_ids.*' => 'required|integer|distinct',
        ]);
        $materials = LearningMaterial::with('lesson.course')->whereIn('id', $data['material_ids'])->get()->keyBy('id');
        $results = [];
        foreach ($data['material_ids'] as $id) {
            $material = $materials->get($id);
            $allowed = $material && $this->authService->canAccessMaterial($request->user(), $material);
            $type = match ($material?->material_type) {
                'pdf_document' => 'pdf', 'youtube_video' => 'video', default => null
            };
            $results[$id] = [
                'access' => (bool) $allowed,
                'reason' => $allowed ? 'access_granted' : 'material_unavailable_or_no_access',
                'media_url' => $allowed && $type ? $this->proxyService->generateProxyUrl($type, $id, $request->user()->id) : null,
            ];
        }

        return response()->json(['results' => $results])->header('Cache-Control', 'private, no-store');
    }
}
