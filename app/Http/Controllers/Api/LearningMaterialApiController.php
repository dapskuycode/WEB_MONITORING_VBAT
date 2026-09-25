<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialView;
use App\Services\BulkImportService;
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

        if (!empty($validated['lesson_id'])) {
            $query->where('lesson_id', $validated['lesson_id']);
        }

        if (!empty($validated['material_type'])) {
            $query->where('material_type', $validated['material_type']);
        }

        if (!empty($validated['status'])) {
            if ($validated['status'] !== 'all') {
                $query->where('status', $validated['status']);
            }
        } else {
            $query->where('status', 'published');
        }

        if (isset($validated['is_required'])) {
            $query->where('is_required', $validated['is_required']);
        }

        if (!empty($validated['search'])) {
            $search = '%' . $validated['search'] . '%';
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
        $validated = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'unit_code' => ['required', 'string', 'max:50', 'unique:learning_materials,unit_code'],
            'unit_title' => ['required', 'string', 'max:255'],
            'material_type' => ['required', Rule::in(['youtube_video', 'pdf_document', 'text_content', 'external_link'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'pdf_path' => ['nullable', 'string', 'max:500'],
            'thumbnail_path' => ['nullable', 'string', 'max:500'],
            'content_text' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'is_required' => ['boolean'],
            'quiz_required' => ['boolean'],
            'quiz_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'duration_seconds' => ['integer', 'min:0'],
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['is_required'] = $validated['is_required'] ?? true;
        $validated['quiz_required'] = $validated['quiz_required'] ?? false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['duration_seconds'] = $validated['duration_seconds'] ?? 0;

        if ($validated['material_type'] === 'youtube_video' && !empty($validated['youtube_url'])) {
            $videoId = LearningMaterial::extractYouTubeId($validated['youtube_url']);
            if (!$videoId) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid YouTube URL format',
                ], 422);
            }
            $validated['youtube_video_id'] = $videoId;
        }

        $material = LearningMaterial::create($validated);

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

        // For guests/public: return only published free materials
        if (!$request->user() && $material->lesson?->course?->type !== 'free_class') {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required to access this material',
                'course_type' => $material->lesson?->course?->type,
            ], 401);
        }

        // For authenticated users: check authorization
        if ($request->user()) {
            $authService = app(\App\Services\MediaAuthorizationService::class);
            $canAccess = $authService->canAccessMaterial($request->user(), $material);
            $tier = $authService->getAccessTier($request->user(), $material);

            if (!$canAccess && $material->lesson?->course?->type !== 'free_class') {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied to this material',
                    'tier' => $tier,
                    'course_type' => $material->lesson?->course?->type,
                    'reason' => $tier === 'qualified_pending'
                        ? 'Hardware Solution quiz threshold not met (90% required)'
                        : 'No active entitlement for this course',
                ], 403);
            }
        }

        // Build response with access URLs
        $proxyService = app(\App\Services\MediaProxyService::class);
        $response = [
            ...$material->toArray(),
            'thumbnail_url' => $material->thumbnail_url,
            'embed_url' => $material->embed_url,
            'access_tier' => $request->user()
                ? app(\App\Services\MediaAuthorizationService::class)->getAccessTier($request->user(), $material)
                : 'public',
        ];

        // For PDF: generate signed URL
        if ($material->pdf_path && $request->user()) {
            $response['pdf_signed_url'] = $proxyService->generateSignedUrl($material->pdf_path);
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

        $validated = $request->validate([
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
            'unit_code' => ['nullable', 'string', 'max:50', Rule::unique('learning_materials')->ignore($material->id)],
            'unit_title' => ['nullable', 'string', 'max:255'],
            'material_type' => ['nullable', Rule::in(['youtube_video', 'pdf_document', 'text_content', 'external_link'])],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'pdf_path' => ['nullable', 'string', 'max:500'],
            'thumbnail_path' => ['nullable', 'string', 'max:500'],
            'content_text' => ['nullable', 'string'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'is_required' => ['boolean'],
            'quiz_required' => ['boolean'],
            'quiz_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'duration_seconds' => ['integer', 'min:0'],
        ]);

        if (isset($validated['youtube_url']) && $validated['youtube_url'] !== null) {
            $videoId = LearningMaterial::extractYouTubeId($validated['youtube_url']);
            if (!$videoId) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Invalid YouTube URL format',
                ], 422);
            }
            $validated['youtube_video_id'] = $videoId;
        }

        $material->update($validated);

        return response()->json([
            'success' => true,
            'data' => $material->fresh()->load('lesson.course'),
            'message' => 'Learning material updated successfully',
        ]);
    }

    /**
     * Soft delete a learning material.
     */
    public function destroy(int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);
        $material->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Learning material deleted successfully',
        ]);
    }

    /**
     * Upload thumbnail for a learning material.
     */
    public function uploadThumbnail(Request $request, int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);

        $validated = $request->validate([
            'thumbnail' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $validated['thumbnail'];
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('learning/thumbnails', $filename, 'public');

        $material->update(['thumbnail_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'thumbnail_path' => $path,
                'thumbnail_url' => asset('storage/' . $path),
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

        $validated = $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $validated['pdf'];
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('learning/pdfs', $filename, 'public');

        $material->update(['pdf_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'pdf_path' => $path,
                'pdf_url' => asset('storage/' . $path),
            ],
            'message' => 'PDF uploaded successfully',
        ]);
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

        if (!$videoId) {
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
                'is_valid' => true,
                'is_accessible' => $check['accessible'],
                'title' => $check['title'] ?? null,
                'author' => $check['author'] ?? null,
                'thumbnail_url' => $check['thumbnail_url'] ?? null,
                'raw_check' => $check,
            ],
            'message' => $check['accessible']
                ? 'YouTube video is accessible'
                : 'YouTube video is not accessible: ' . ($check['error'] ?? 'unknown error'),
        ]);
    }

    /**
     * Record a view/progress for a learning material.
     */
    public function recordProgress(Request $request, int $id): JsonResponse
    {
        $material = LearningMaterial::findOrFail($id);

        $validated = $request->validate([
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        // Ownership from server auth — NOT from client payload (Decision D-009)
        $userId = $request->user()?->id;

        if (! $userId) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Authentication required to record progress.',
            ], 401);
        }

        $completedAt = $validated['progress_percent'] >= 100 ? now() : null;

        $view = LearningMaterialView::updateOrCreate(
            [
                'user_id' => $userId,
                'learning_material_id' => $material->id,
            ],
            [
                'viewed_at' => now(),
                'progress_percent' => $validated['progress_percent'],
                'completed_at' => $completedAt,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $view,
            'message' => 'Progress recorded successfully',
        ]);
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
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'preview' => ['nullable', 'boolean'],
            'skip_invalid' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $isPreview = $request->boolean('preview', true);
        $skipInvalid = $request->boolean('skip_invalid', false);

        // Store temporarily for PhpSpreadsheet to read
        $tempPath = $file->store('temp/imports', 'local');
        $fullPath = storage_path('app/' . $tempPath);

        try {
            if ($isPreview) {
                $maxRows = (int) $request->input('preview_limit', 20);
                $result = $this->bulkImportService->preview($fullPath, $maxRows);
            } else {
                $result = $this->bulkImportService->commit($fullPath, $skipInvalid);
            }
        } finally {
            // Always clean up temp file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'],
            'errors' => $result['errors'],
            'message' => $isPreview
                ? ($result['success'] ? 'Preview generated successfully' : 'Preview failed')
                : ($result['success'] ? 'Import completed successfully' : 'Import failed'),
        ], $statusCode);
    }

    /**
     * Download XLSX import template with correct column headers.
     *
     * GET /api/v1/learning-materials/bulk-import/template
     */
    public function bulkImportTemplate(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'columns' => BulkImportService::TEMPLATE_COLUMNS,
                'column_descriptions' => [
                    'lesson_id' => 'Required. ID of the lesson this material belongs to.',
                    'unit_code' => 'Optional. Auto-generated if empty. Must be unique.',
                    'unit_title' => 'Optional. Falls back to title if empty.',
                    'material_type' => 'One of: youtube_video, pdf_document, text_content, external_link. Default: text_content',
                    'title' => 'Required. Title of the learning material.',
                    'description' => 'Optional. Description text.',
                    'youtube_url' => 'Optional. YouTube URL (for youtube_video type).',
                    'external_url' => 'Optional. External link URL (for external_link type).',
                    'content_text' => 'Optional. Text content (for text_content type).',
                    'status' => 'One of: draft, published, archived. Default: draft',
                ],
                'example_row' => [
                    'lesson_id' => 1,
                    'unit_code' => 'MAT-UNIT-001',
                    'unit_title' => 'Introduction to Topic',
                    'material_type' => 'youtube_video',
                    'title' => 'Video: Getting Started',
                    'description' => 'An introductory video',
                    'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'external_url' => null,
                    'content_text' => null,
                    'status' => 'published',
                ],
            ],
            'message' => 'Import template specification',
        ]);
    }
}
