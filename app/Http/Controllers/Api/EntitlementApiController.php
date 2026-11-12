<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntitlementApiController extends Controller
{
    public function __construct(
        private EntitlementService $entitlementService
    ) {}

    public function snapshot(Request $request): JsonResponse
    {
        $access = [];
        foreach (Course::TYPES as $type) {
            $access[$type] = $this->entitlementService->accessForType($request->user(), $type);
        }

        return response()->json(['success' => true, 'data' => [
            'server_time' => now()->toIso8601String(), 'access' => $access,
        ]])->header('Cache-Control', 'private, no-store');
    }

    /**
     * Get download manifest for a course.
     */
    public function downloadManifest(int $courseId): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $course = Course::findOrFail($courseId);

        try {
            $manifest = $this->entitlementService->getDownloadManifest($user, $course);

            return response()->json([
                'success' => true,
                'data' => $manifest,
                'message' => 'Download manifest generated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Check course access.
     */
    public function checkAccess(int $courseId): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $course = Course::findOrFail($courseId);
        $hasAccess = $this->entitlementService->canAccessCourse($user, $course);

        return response()->json([
            'success' => true,
            'data' => [
                ...$this->entitlementService->courseAccess($user, $course),
                'course_id' => $courseId,
                'is_free_class' => $course->is_free_class,
            ],
            'message' => $hasAccess ? 'Access granted' : 'Access denied',
        ]);
    }

    public function issueKtaCertificate(int $courseId): JsonResponse
    {
        Course::findOrFail($courseId);

        return response()->json([
            'success' => false,
            'error' => ['code' => 'CERTIFICATE_ISSUANCE_UNAVAILABLE'],
            'message' => 'Penerbitan KTA dan sertifikat menunggu aturan keanggotaan dan kelulusan.',
        ], 503);
    }

    /**
     * List user's entitlements.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'package_type' => ['nullable', 'in:android,iphone,bundling,premium,hardware_solution'],
            'status' => ['nullable', 'in:active,expired,revoked'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $user->entitlements();

        if (! empty($validated['package_type'])) {
            $query->where('package_type', $validated['package_type']);
        }

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $entitlements = $query->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $entitlements->items(),
            'meta' => [
                'current_page' => $entitlements->currentPage(),
                'per_page' => $entitlements->perPage(),
                'total' => $entitlements->total(),
                'last_page' => $entitlements->lastPage(),
            ],
            'message' => 'Entitlements retrieved successfully',
        ]);
    }
}
