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

    /**
     * Get download manifest for a course.
     */
    public function downloadManifest(int $courseId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
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
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $course = Course::findOrFail($courseId);
        $hasAccess = $this->entitlementService->canAccessCourse($user, $course);

        return response()->json([
            'success' => true,
            'data' => [
                'has_access' => $hasAccess,
                'course_id' => $courseId,
                'is_free_class' => $course->is_free_class,
            ],
            'message' => $hasAccess ? 'Access granted' : 'Access denied',
        ]);
    }

    /**
     * Issue KTA certificate (Hardware Solution 90% threshold).
     */
    public function issueKtaCertificate(int $courseId): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $course = Course::findOrFail($courseId);

        try {
            $certificate = $this->entitlementService->issueKtaCertificate($user, $course);

            return response()->json([
                'success' => true,
                'data' => $certificate,
                'message' => 'KTA certificate issued successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List user's entitlements.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'entitlement_type' => ['nullable', 'in:course_access,certificate'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $user->entitlements();

        if (!empty($validated['entitlement_type'])) {
            $query->where('entitlement_type', $validated['entitlement_type']);
        }

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
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
