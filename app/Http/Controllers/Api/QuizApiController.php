<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\LmsQuizPlacementService;
use App\Services\QuizDefinitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizApiController extends Controller
{
    /**
     * List quizzes (optionally filtered by learning_material_id).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'learning_material_id' => ['nullable', 'integer', 'exists:learning_materials,id'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'with_questions' => ['nullable', 'boolean'],
        ]);

        $query = Quiz::with('learningMaterial.lesson.course');
        if (! auth('sanctum')->user()?->isSuperAdmin()) {
            $query->where('is_active', true)->whereHas('learningMaterial', fn ($q) => $q->published()
                ->whereHas('lesson', fn ($q) => $q->where('status', 'published')->whereHas('course', fn ($q) => $q->published())));
        }

        if (! empty($validated['learning_material_id'])) {
            $query->where('learning_material_id', $validated['learning_material_id']);
        }

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        if (! empty($validated['with_questions'])) {
            $query->with('questions');
        }

        $quizzes = $query->orderBy('sort_order')->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'data' => $quizzes->items(),
            'meta' => [
                'current_page' => $quizzes->currentPage(),
                'per_page' => $quizzes->perPage(),
                'total' => $quizzes->total(),
                'last_page' => $quizzes->lastPage(),
            ],
            'message' => 'Quizzes retrieved successfully',
        ]);
    }

    /**
     * Show a quiz with questions.
     */
    public function show(int $id): JsonResponse
    {
        $quiz = Quiz::with(['learningMaterial.lesson.course', 'questions'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $quiz,
            'message' => 'Quiz retrieved successfully',
        ]);
    }

    /**
     * Create a new quiz (Admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'learning_material_id' => ['required', 'integer', 'exists:learning_materials,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_attempts' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
            'grading_mode' => 'sometimes|in:auto,manual,hybrid', 'grading_policy' => 'sometimes|in:highest,latest,average',
            'shuffle_questions' => 'sometimes|boolean', 'shuffle_options' => 'sometimes|boolean', 'placement' => 'sometimes|nullable|in:after_material,end_of_module,end_of_course', 'is_required' => 'sometimes|boolean',
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated = app(LmsQuizPlacementService::class)->configure(new Quiz, $validated);
        $quiz = Quiz::create(['max_attempts' => 0, 'passing_score' => 70, 'time_limit_minutes' => null, ...$validated]);

        return response()->json([
            'success' => true,
            'data' => $quiz,
            'message' => 'Quiz created successfully',
        ], 201);
    }

    /**
     * Update a quiz.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'max_attempts' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
            'is_active' => ['nullable', 'boolean'],
            'grading_mode' => 'sometimes|in:auto,manual,hybrid', 'grading_policy' => 'sometimes|in:highest,latest,average',
            'shuffle_questions' => 'sometimes|boolean', 'shuffle_options' => 'sometimes|boolean', 'placement' => 'sometimes|nullable|in:after_material,end_of_module,end_of_course', 'is_required' => 'sometimes|boolean',
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated = app(LmsQuizPlacementService::class)->configure($quiz, $validated);
        $quiz->update($validated);

        return response()->json([
            'success' => true,
            'data' => $quiz->fresh(),
            'message' => 'Quiz updated successfully',
        ]);
    }

    /**
     * Delete a quiz.
     */
    public function destroy(int $id): JsonResponse
    {
        $quiz = Quiz::findOrFail($id);
        abort_if($quiz->attempts()->exists(), 409, 'Kuis memiliki riwayat attempt; nonaktifkan, jangan hapus.');
        $quiz->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Quiz deleted successfully',
        ]);
    }

    // ─── Quiz Questions ──────────────────────────────────────────────

    /**
     * Add question to a quiz.
     */
    public function addQuestion(Request $request, int $quizId): JsonResponse
    {
        $quiz = Quiz::findOrFail($quizId);

        $validated = app(QuizDefinitionService::class)->question(['points' => 1, 'sort_order' => 0, ...$request->all()]);

        $question = $quiz->questions()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $question,
            'message' => 'Question added to quiz',
        ], 201);
    }

    /**
     * Update a question.
     */
    public function updateQuestion(Request $request, int $questionId): JsonResponse
    {
        $question = QuizQuestion::findOrFail($questionId);

        $validated = app(QuizDefinitionService::class)->question([
            ...$question->only(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'sort_order', 'answer_aliases', 'rubric']), ...$request->all(),
        ]);

        $question->update($validated);

        return response()->json([
            'success' => true,
            'data' => $question->fresh(),
            'message' => 'Question updated',
        ]);
    }

    /**
     * Delete a question.
     */
    public function deleteQuestion(int $questionId): JsonResponse
    {
        $question = QuizQuestion::findOrFail($questionId);
        $question->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Question deleted',
        ]);
    }

    // ─── Quiz Attempts (Student) ─────────────────────────────────────

    /**
     * Start a quiz attempt.
     */
    public function startAttempt(int $id): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 'TRUSTED_ATTEMPT_REQUIRED', 'message' => 'Gunakan endpoint LMS attempt.'], 410);
    }

    public function submitAttempt(Request $request, int $id): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 'TRUSTED_ATTEMPT_REQUIRED', 'message' => 'Gunakan endpoint LMS attempt.'], 410);
    }
}
