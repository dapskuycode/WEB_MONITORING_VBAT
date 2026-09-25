<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'with_questions' => ['nullable', 'boolean'],
        ]);

        $query = Quiz::with('learningMaterial.lesson.course');

        if (!empty($validated['learning_material_id'])) {
            $query->where('learning_material_id', $validated['learning_material_id']);
        }

        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        if (!empty($validated['with_questions'])) {
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

        // For student taking the quiz, hide correct answers
        $userId = auth()->id();
        if ($userId) {
            $attemptCount = $quiz->attemptCountBy($userId);
            $hasPassed = $quiz->hasPassedBy($userId);
            $exhausted = $quiz->attemptsExhaustedBy($userId);

            $data = $quiz->toArray();
            $data['_meta'] = [
                'user_attempt_count' => $attemptCount,
                'user_has_passed' => $hasPassed,
                'attempts_exhausted' => $exhausted,
                'can_take' => !$hasPassed && !$exhausted && $quiz->is_active,
            ];

            // Hide correct answers unless user has passed
            if (!$hasPassed) {
                foreach ($data['questions'] as &$q) {
                    unset($q['correct_answer']);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Quiz retrieved successfully',
            ]);
        }

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
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $quiz = Quiz::create($validated);

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
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:100'],
            'passing_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:300'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

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

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string'],
            'options.*.value' => ['required', 'string'],
            'correct_answer' => ['required', 'string'],
            'points' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

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

        $validated = $request->validate([
            'question_text' => ['nullable', 'string'],
            'options' => ['nullable', 'array', 'min:2'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
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
        $quiz = Quiz::findOrFail($id);

        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$quiz->is_active) {
            return response()->json(['success' => false, 'message' => 'Quiz is not active'], 422);
        }

        if ($quiz->hasPassedBy($userId)) {
            return response()->json(['success' => false, 'message' => 'You have already passed this quiz'], 409);
        }

        if ($quiz->attemptsExhaustedBy($userId)) {
            return response()->json(['success' => false, 'message' => 'No attempts remaining'], 409);
        }

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $userId,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $attempt,
            'message' => 'Quiz attempt started',
        ], 201);
    }

    /**
     * Submit a quiz attempt.
     */
    public function submitAttempt(Request $request, int $id): JsonResponse
    {
        $quiz = Quiz::with('questions')->findOrFail($id);

        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $userId)
            ->whereNull('submitted_at')
            ->firstOrFail();

        $validated = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        // Score calculation
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($quiz->questions as $question) {
            $totalPoints += $question->points;
            $userAnswer = $validated['answers'][$question->id] ?? null;

            if ($userAnswer === $question->correct_answer) {
                $earnedPoints += $question->points;
            }
        }

        $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $isPassed = $score >= $quiz->passing_score;

        $attempt->update([
            'score' => $score,
            'is_passed' => $isPassed,
            'answers' => $validated['answers'],
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                ...$attempt->toArray(),
                'passing_score' => $quiz->passing_score,
                'max_attempts' => $quiz->max_attempts,
                'remaining_attempts' => max(0, $quiz->max_attempts - $quiz->attemptCountBy($userId)),
            ],
            'message' => $isPassed ? 'Quiz passed!' : 'Quiz not passed',
        ]);
    }
}
