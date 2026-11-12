<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\LmsAuthoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->when(! auth('sanctum')->user()?->isSuperAdmin(), fn ($q) => $q->where('status', 'published')->whereHas('course', fn ($q) => $q->published()))
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
        $input = $request->all();
        $input['status'] = $input['status'] ?? 'draft';
        $lesson = app(LmsAuthoringService::class)->saveLesson($input);

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

        $input = [...$lesson->getAttributes(), ...$request->all()];
        $lesson = app(LmsAuthoringService::class)->saveLesson($input, $id);

        return response()->json([
            'success' => true,
            'data' => $lesson->fresh()->load('course'),
            'message' => 'Lesson updated successfully',
        ]);
    }

    /**
     * Delete a lesson.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::findOrFail($id);
        app(LmsAuthoringService::class)->archive($lesson, $request->integer('expected_revision'));

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Lesson archived; learning history retained',
        ]);
    }
}
