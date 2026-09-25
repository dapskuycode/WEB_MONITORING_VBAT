<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LessonApiController extends Controller
{
    /**
     * List lessons for a course.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $lessons = Lesson::where('course_id', $validated['course_id'])
            ->orderBy('sort_order')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $lessons->items(),
            'meta' => [
                'current_page' => $lessons->currentPage(),
                'per_page' => $lessons->perPage(),
                'total' => $lessons->total(),
                'last_page' => $lessons->lastPage(),
            ],
            'message' => 'Lessons retrieved successfully',
        ]);
    }

    /**
     * Store a new lesson.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $lesson = Lesson::create($validated);

        return response()->json([
            'success' => true,
            'data' => $lesson->load('course'),
            'message' => 'Lesson created successfully',
        ], 201);
    }

    /**
     * Display a specific lesson.
     */
    public function show(int $id): JsonResponse
    {
        $lesson = Lesson::with(['course', 'learningMaterials', 'videos'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $lesson,
            'message' => 'Lesson retrieved successfully',
        ]);
    }

    /**
     * Update a lesson.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::findOrFail($id);

        $validated = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $lesson->update($validated);

        return response()->json([
            'success' => true,
            'data' => $lesson->fresh()->load('course'),
            'message' => 'Lesson updated successfully',
        ]);
    }

    /**
     * Delete a lesson.
     */
    public function destroy(int $id): JsonResponse
    {
        $lesson = Lesson::findOrFail($id);
        $lesson->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Lesson deleted successfully',
        ]);
    }
}
