<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\BulkImportService;
use App\Services\LmsAuthoringService;
use App\Services\LmsUploadService;
use App\Services\MediaAuthorizationService;
use App\Services\MediaProxyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LearningMaterialApiController extends Controller
{
    public function __construct(
        private readonly BulkImportService $bulkImportService,
    ) {}

    /**
     * List learning materials with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'material_type' => ['nullable', Rule::in(['youtube_video', 'pdf_document', 'text_content', 'external_link'])],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived', 'all'])],
            'is_required' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['sort_order', 'created_at', 'view_count', 'title'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = LearningMaterial::with('lesson.course');
        if (! auth('sanctum')->user()?->isSuperAdmin()) {
            $query->published()->whereHas('lesson', fn ($q) => $q->where('status', 'published')->whereHas('course', fn ($q) => $q->published()));
        }

        if (! empty($validated['lesson_id'])) {
            $query->where('lesson_id', $validated['lesson_id']);
        }

        if (! empty($validated['material_type'])) {
            $query->where('material_type', $validated['material_type']);
        }

        if (! empty($validated['status'])) {
            if ($validated['status'] !== 'all') {
                $query->where('status', $validated['status']);
            }
        } else {
            $query->where('status', 'published');
        }

        if (isset($validated['is_required'])) {
            $query->where('is_required', $validated['is_required']);
        }

        if (! empty($validated['search'])) {
            $search = '%'.$validated['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('unit_code', 'like', $search)
                    ->orWhere('unit_title', 'like', $search);
            });
        }

        $sortBy = $validated['sort_by'] ?? 'sort_order';
        $sortDir = $validated['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = $validated['per_page'] ?? 15;
        $materials = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $materials->items(),
            'meta' => [
                'current_page' => $materials->currentPage(),
                'per_page' => $materials->perPage(),
                'total' => $materials->total(),
                'last_page' => $materials->lastPage(),
            ],
            'message' => 'Learning materials retrieved successfully',
        ]);
    }

    /**
     * Store a new learning material.
     */
    public function store(Request $request): JsonResponse
    {
        $input = $request->all();
        $input['status'] = $input['status'] ?? 'draft';
        $material = app(LmsAuthoringService::class)->saveMaterial($input);

        return response()->json([
            'success' => true,
            'data' => $material->load('lesson.course'),
            'message' => 'Learning material created successfully',
        ], 201);
    }

    /**
     * Display a specific learning material.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $material = LearningMaterial::with('lesson.course')->findOrFail($id);
        if (auth('sanctum')->user()?->isSuperAdmin()) {
            return response()->json(['success' => true, 'data' => [
                ...$material->toArray(), 'thumbnail_url' => $material->thumbnail_url, 'embed_url' => $material->embed_url,
            ]]);
        }

        // For guests/public: return only published free materials
        if (! $request->user() && $material->lesson?->course?->type !== 'free_class') {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required to access this material',
                'course_type' => $material->lesson?->course?->type,
            ], 401);
        }

        // For authenticated users: check authorization
        if ($request->user()) {
            $authService = app(MediaAuthorizationService::class);
            $canAccess = $authService->canAccessMaterial($request->user(), $material);
            $tier = $authService->getAccessTier($request->user(), $material);

            if (! $canAccess && $material->lesson?->course?->type !== 'free_class') {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied to this material',
                    'tier' => $tier,
                    'course_type' => $material->lesson?->course?->type,
                    'reason' => $tier === 'qualified_pending'
                        ? 'Hardware Solution prerequisite validation is not available'
                        : 'No active entitlement for this course',
                ], 403);
            }
        }

        // Build response with access URLs
        $proxyService = app(MediaProxyService::class);
        $response = [
            ...$material->toArray(),
            'thumbnail_url' => $material->thumbnail_url,
            'embed_url' => $material->embed_url,
            'access_tier' => $request->user()
                ? app(MediaAuthorizationService::class)->getAccessTier($request->user(), $material)
                : 'public',
        ];

        // For PDF: generate signed URL
        if ($material->pdf_path && $material->material_type === 'pdf_document' && $request->user()) {
            $response['pdf_signed_url'] = $proxyService->generateProxyUrl('pdf', $material->id, $request->user()->id);
        }

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'Learning material retrieved successfully',
        ]);
    }

    /**
     * Update a learning material.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);
        $material = app(LmsAuthoringService::class)->saveMaterial([...$material->getAttributes(), ...$request->all()], $id);

        return response()->json(['success' => true, 'data' => $material->load('lesson.course'), 'message' => 'Learning material updated successfully']);
    }

    /**
     * Soft delete a learning material.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);
        app(LmsAuthoringService::class)->archive($material, $request->integer('expected_revision'));

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Learning material archived; learning history retained',
        ]);
    }

    /**
     * Upload thumbnail for a learning material.
     */
    public function uploadThumbnail(Request $request, int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);
        abort_unless($material->status === 'draft', 409, 'Ubah materi ke Draft sebelum mengganti berkas.');

        $validated = $request->validate([
            'thumbnail' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $validated['thumbnail'];
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('learning/thumbnails', $filename, 'public');

        try {
            app(LmsAuthoringService::class)->saveMaterial([...$material->getAttributes(), 'thumbnail_path' => $path, 'expected_revision' => $request->integer('expected_revision')], $material->id);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'thumbnail_path' => $path,
                'thumbnail_url' => asset('storage/'.$path),
            ],
            'message' => 'Thumbnail uploaded successfully',
        ]);
    }

    /**
     * Upload PDF for a learning material.
     */
    public function uploadPdf(Request $request, int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);
        abort_unless($material->status === 'draft', 409);
        $request->validate(['pdf' => 'required|file']);
        $upload = app(LmsUploadService::class)->receive($request->user(), $request->file('pdf'), 'course_material');

        return response()->json(['success' => true, 'data' => app(LmsUploadService::class)->data($upload),
            'message' => 'PDF menunggu pemeriksaan; tautkan ID upload setelah status ready.'], 202);
    }

    /**
     * Validate a YouTube URL and check accessibility.
     */
    public function validateYouTube(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'youtube_url' => ['required', 'url', 'max:500'],
        ]);

        $videoId = LearningMaterial::extractYouTubeId($validated['youtube_url']);

        if (! $videoId) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Could not extract YouTube video ID from URL',
            ], 422);
        }

        $check = LearningMaterial::checkYouTubeUnlisted($videoId);

        return response()->json([
            'success' => true,
            'data' => [
                'video_id' => $videoId,
                'is_valid' => $check['valid'],
                'embed_verified' => false,
                'is_accessible' => $check['accessible'],
                'title' => $check['title'] ?? null,
                'author' => $check['author'] ?? null,
                'thumbnail_url' => $check['thumbnail'] ?? null,
                'raw_check' => $check,
            ],
            'message' => $check['accessible']
                ? 'YouTube video is accessible'
                : 'YouTube video is not accessible: '.($check['error'] ?? 'unknown error'),
        ]);
    }

    /**
     * Record a view/progress for a learning material.
     */
    public function recordProgress(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false, 'code' => 'PLAYBACK_SESSION_REQUIRED',
            'message' => 'Gunakan sesi playback. Persentase dari klien tidak diterima.',
        ], 410);
    }

    /**
     * Get analytics for a learning material.
     */
    public function analytics(int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);

        $totalViews = $material->views()->count();
        $completedCount = $material->views()->whereNotNull('completed_at')->count();
        $avgProgress = $material->views()->avg('progress_percent') ?? 0;

        $progressDistribution = [
            '0-25%' => $material->views()->whereBetween('progress_percent', [0, 25])->count(),
            '26-50%' => $material->views()->whereBetween('progress_percent', [26, 50])->count(),
            '51-75%' => $material->views()->whereBetween('progress_percent', [51, 75])->count(),
            '76-99%' => $material->views()->whereBetween('progress_percent', [76, 99])->count(),
            '100%' => $material->views()->where('progress_percent', '>=', 100)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'material_id' => $material->id,
                'title' => $material->title,
                'total_views' => $totalViews,
                'completed_count' => $completedCount,
                'completion_rate' => $totalViews > 0 ? round(($completedCount / $totalViews) * 100, 2) : 0,
                'average_progress' => round($avgProgress, 2),
                'progress_distribution' => $progressDistribution,
            ],
            'message' => 'Analytics retrieved successfully',
        ]);
    }

    // ─── Bulk Import (REQ-ADM-02) ────────────────────────────────────────

    /**
     * Bulk import learning materials from XLSX/CSV file.
     *
     * POST /api/v1/learning-materials/bulk-import
     *
     * Modes:
     *  - preview=true  → dry-run: validate & return preview (no DB writes)
     *  - preview=false → commit: insert validated rows into DB
     */
    public function bulkImport(Request $request): JsonResponse
    {
        abort_unless($request->boolean('preview', true), 410, 'Gunakan preview ID pada endpoint apply LMS.');

        return app(LmsImportController::class)->preview($request, $this->bulkImportService);
    }

    public function bulkImportTemplate(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => ['columns' => BulkImportService::TEMPLATE_COLUMNS,
            'mode' => 'create_only_all_or_nothing_draft', 'max_rows' => 1000]]);
    }
}
