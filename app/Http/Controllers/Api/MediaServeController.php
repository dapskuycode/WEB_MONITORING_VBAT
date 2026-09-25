<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\MediaAuthorizationService;
use App\Services\MediaProxyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MediaServeController extends Controller
{
    public function __construct(
        private MediaAuthorizationService $authService,
        private MediaProxyService $proxyService
    ) {}

    /**
     * GET /api/v1/media/serve/{type}/{id} — Serve protected media
     */
    public function serve(Request $request, string $type, int $id): Response|JsonResponse
    {
        // Verify signed URL expiry
        $expires = $request->query('expires');
        if ($expires && (int) $expires < now()->timestamp) {
            return response()->json(['error' => 'URL expired'], 410);
        }

        // Find material
        $material = LearningMaterial::with('lesson.course')->find($id);

        if (!$material) {
            return response()->json(['error' => 'Material not found'], 404);
        }

        // Check user authentication
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        // Check access authorization
        $canAccess = $this->authService->canAccessMaterial($user, $material);

        if (!$canAccess) {
            $accessTier = $this->authService->getAccessTier($user, $material);

            return response()->json([
                'error' => 'Access denied',
                'reason' => match ($accessTier) {
                    'none' => 'No entitlement for this course',
                    'qualified_pending' => 'Hardware Solution quiz threshold not met (90% required)',
                    default => 'Access restricted',
                },
                'tier' => $accessTier,
            ], 403);
        }

        // Serve media based on type
        return match ($type) {
            'pdf' => $this->servePdf($material),
            'video' => $this->serveVideo($material),
            'thumbnail' => $this->serveThumbnail($material),
            default => response()->json(['error' => 'Invalid media type'], 400),
        };
    }

    /**
     * Serve PDF file from storage
     */
    private function servePdf(LearningMaterial $material): Response
    {
        if (!$material->pdf_path) {
            return response(['error' => 'PDF not found'], 404);
        }

        $path = $material->pdf_path;

        if (!Storage::disk('public')->exists($path)) {
            return response(['error' => 'File not found'], 404);
        }

        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);
        $fileName = basename($path);

        return response($file, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Serve video metadata (YouTube embed URL via proxy)
     */
    private function serveVideo(LearningMaterial $material): JsonResponse
    {
        if (!$material->youtube_video_id) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        $accessData = $this->proxyService->generateYouTubeAccessUrl($material->youtube_video_id);

        return response()->json([
            'video_id' => $accessData['video_id'],
            'embed_url' => $accessData['embed_url'],
            'expires_at' => $accessData['expires_at']->toIso8601String(),
        ]);
    }

    /**
     * Serve thumbnail image
     */
    private function serveThumbnail(LearningMaterial $material): Response|JsonResponse
    {
        if ($material->thumbnail_path) {
            $path = $material->thumbnail_path;

            if (!Storage::disk('public')->exists($path)) {
                return response(['error' => 'Thumbnail not found'], 404);
            }

            $file = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);

            return response($file, 200, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        // Fallback to YouTube thumbnail
        if ($material->youtube_video_id) {
            $youtubeUrl = "https://img.youtube.com/vi/{$material->youtube_video_id}/mqdefault.jpg";

            return response()->json([
                'thumbnail_url' => $youtubeUrl,
                'source' => 'youtube',
            ]);
        }

        return response()->json(['error' => 'No thumbnail available'], 404);
    }

    /**
     * POST /api/v1/media/access-check — Check access before generating URL
     */
    public function checkAccess(Request $request): JsonResponse
    {
        $request->validate([
            'material_ids' => 'required|array',
            'material_ids.*' => 'integer|exists:learning_materials,id',
        ]);

        $user = $request->user();
        $results = [];

        foreach ($request->material_ids as $materialId) {
            $material = LearningMaterial::with('lesson.course')->find($materialId);

            if (!$material) {
                $results[$materialId] = ['access' => false, 'reason' => 'Material not found'];
                continue;
            }

            $canAccess = $this->authService->canAccessMaterial($user, $material);
            $tier = $this->authService->getAccessTier($user, $material);

            $results[$materialId] = [
                'access' => $canAccess,
                'tier' => $tier,
                'course_type' => $material->lesson?->course?->type,
            ];
        }

        return response()->json(['results' => $results]);
    }
}
