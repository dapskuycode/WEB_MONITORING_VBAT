<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseApiController extends Controller
{
    /**
     * List courses with filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(Course::TYPES)],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived', 'all'])],
            'is_free_class' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', Rule::in(['sort_order', 'created_at', 'title', 'published_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'with_lessons' => ['nullable', 'boolean'],
        ]);

        $query = Course::query();

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['status'])) {
            if ($validated['status'] === 'archived') {
                $query->whereNotNull('archived_at');
            } elseif ($validated['status'] !== 'all') {
                $query->where('status', $validated['status']);
            }
        } else {
            // Default: exclude archived
            $query->whereNull('archived_at');
        }

        if (isset($validated['is_free_class'])) {
            $query->where('is_free_class', $validated['is_free_class']);
        }

        if (isset($validated['is_featured'])) {
            $query->where('is_featured', $validated['is_featured']);
        }

        if (!empty($validated['search'])) {
            $search = '%' . $validated['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = $validated['per_page'] ?? 15;

        if (!empty($validated['with_lessons'])) {
            $query->with('lessons');
        }

        $courses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
            ],
            'message' => 'Courses retrieved successfully',
        ]);
    }

    /**
     * Free Class listing (public or gated).
     */
    public function freeClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'with_lessons' => ['nullable', 'boolean'],
        ]);

        $query = Course::published()
            ->where('is_free_class', true)
            ->orderBy('created_at', 'desc');

        if (!empty($validated['search'])) {
            $search = '%' . $validated['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $perPage = $validated['per_page'] ?? 15;

        if (!empty($validated['with_lessons'])) {
            $query->with('lessons');
        }

        $courses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
            ],
            'message' => 'Free class courses retrieved successfully',
        ]);
    }

    /**
     * Store a new course.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:courses,slug'],
            'description' => ['nullable', 'string'],
            'thumbnail_path' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'type' => ['nullable', Rule::in(Course::TYPES)],
            'is_free_class' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['title']) . '-' . Str::random(5);
        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['type'] = $validated['type'] ?? Course::TYPE_ANDROID;
        $validated['is_free_class'] = $validated['is_free_class'] ?? false;
        $validated['is_featured'] = $validated['is_featured'] ?? false;

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $course = Course::create($validated);

        return response()->json([
            'success' => true,
            'data' => $course,
            'message' => 'Course created successfully',
        ], 201);
    }

    /**
     * Display a specific course.
     */
    public function show(int $id): JsonResponse
    {
        $course = Course::with('lessons.learningMaterials')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $course,
            'message' => 'Course retrieved successfully',
        ]);
    }

    /**
     * Update a course.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('courses')->ignore($course->id)],
            'description' => ['nullable', 'string'],
            'thumbnail_path' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
            'type' => ['nullable', Rule::in(Course::TYPES)],
            'is_free_class' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'published' && $course->status !== 'published') {
                $validated['published_at'] = now();
            }
            if ($validated['status'] === 'archived' && $course->archived_at === null) {
                $validated['archived_at'] = now();
            }
            if ($validated['status'] === 'published' || $validated['status'] === 'draft') {
                $validated['archived_at'] = null;
            }
        }

        $course->update($validated);

        return response()->json([
            'success' => true,
            'data' => $course->fresh(),
            'message' => 'Course updated successfully',
        ]);
    }

    /**
     * Archive a course (soft-archive).
     */
    public function archive(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $course->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $course->fresh(),
            'message' => 'Course archived successfully',
        ]);
    }

    /**
     * Delete a course.
     */
    public function destroy(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Course deleted successfully',
        ]);
    }
}
