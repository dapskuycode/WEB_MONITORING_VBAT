<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LearningMaterialApiController extends Controller
{
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
    public function show(int $id): JsonResponse
    {
        $material = LearningMaterial::with('lesson.course')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                ...$material->toArray(),
                'thumbnail_url' => $material->thumbnail_url,
                'embed_url' => $material->embed_url,
            ],
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $completedAt = $validated['progress_percent'] >= 100 ? now() : null;

        $view = LearningMaterialView::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
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
}
