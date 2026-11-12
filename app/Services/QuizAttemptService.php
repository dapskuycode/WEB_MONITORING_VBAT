<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(private EntitlementService $access, private LmsProgressService $progress, private QuizDefinitionService $definition) {}

    /** @return array<string, mixed> */
    public function overview(User $user, int $quizId): array
    {
        return DB::transaction(function () use ($user, $quizId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $quiz = Quiz::lockForUpdate()->findOrFail($quizId);
            $source = $this->authorize($user, $quiz);
            $this->expireFor($user, $quiz);
            $this->invalidateStale($user, $quiz, $source);
            $attempts = $this->attempts($user, $quiz)->get();
            $active = $attempts->first(fn ($a) => $a->status === 'active' && $a->source_hash === $source);
            $finished = $attempts->filter(fn ($a) => in_array($a->status, ['submitted', 'expired'], true) && $a->source_hash === $source);
            $outcome = app(QuizOutcomeService::class)->summarize($finished);
            $pending = $finished->sortByDesc('id')->first(fn ($a) => $a->grade_status === 'pending_review');
            $best = $pending ?? $outcome['attempt'] ?? $finished->sortByDesc('id')->first();
            $passed = $outcome['passed'];
            $remaining = $quiz->max_attempts === 0 ? null : max(0, $quiz->max_attempts - $attempts->count());

            return ['quiz' => ['id' => $quiz->id, 'title' => $quiz->title, 'description' => $quiz->description,
                'passing_score' => $quiz->passing_score, 'max_attempts' => $quiz->max_attempts, 'time_limit_minutes' => $quiz->time_limit_minutes, 'grading_policy' => $quiz->grading_policy ?? 'highest'],
                'remaining_attempts' => $remaining, 'passed' => $passed, 'best_score' => $outcome['score'],
                'can_start' => (! $passed || $quiz->grading_policy !== 'highest') && ($remaining === null || $remaining > 0),
                'attempt' => $active ? $this->serialize($active) : ($best ? $this->serialize($best) : null), 'server_time' => now()->toIso8601String()];
        });
    }

    /** @return array<string, mixed> */
    public function start(User $user, int $quizId, string $requestId): array
    {
        return DB::transaction(function () use ($user, $quizId, $requestId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $quiz = Quiz::lockForUpdate()->findOrFail($quizId);
            $source = $this->authorize($user, $quiz);
            $this->expireFor($user, $quiz);
            $this->invalidateStale($user, $quiz, $source);
            $start = DB::table('lms_quiz_starts')->where('user_id', $user->id)->where('request_id', $requestId)->first();
            $replay = $start ? QuizAttempt::whereKey($start->attempt_id)->firstOrFail() : null;
            if ($replay) {
                abort_unless($replay->quiz_id === $quizId && $replay->source_hash === $source, 409, 'Identitas permintaan sudah digunakan.');

                return $this->serialize($replay);
            }
            $active = $this->attempts($user, $quiz)->where('status', 'active')->first();
            if ($active) {
                abort_unless($active->source_hash === $source, 409, 'Sumber materi berubah; pengelola perlu meninjau attempt aktif.');

                $this->rememberStart($user, $active, $requestId);

                return $this->serialize($active);
            }
            $outcome = app(QuizOutcomeService::class)->summarize($this->attempts($user, $quiz)->where('source_hash', $source)->get());
            abort_if($quiz->grading_policy === 'highest' && $outcome['passed'], 409, 'Kuis sudah lulus.');
            abort_if($quiz->max_attempts > 0 && $this->attempts($user, $quiz)->count() >= $quiz->max_attempts, 409, 'Batas percobaan tercapai.');
            $snapshot = $this->definition->snapshot($quiz);
            $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $user->id, 'status' => 'active', 'request_id' => $requestId,
                'source_hash' => $source, 'snapshot' => $snapshot, 'version' => 0, 'answers' => [], 'started_at' => now(),
                'deadline_at' => $snapshot['time_limit_minutes'] === null ? null : now()->addMinutes($snapshot['time_limit_minutes'])]);

            $this->rememberStart($user, $attempt, $requestId);

            return $this->serialize($attempt);
        });
    }

    /** @return array<string, mixed> */
    public function read(User $user, int $id): array
    {
        return DB::transaction(function () use ($user, $id) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $attempt = QuizAttempt::where('user_id', $user->id)->whereIn('status', ['active', 'submitted', 'expired', 'invalidated'])->findOrFail($id);
            $quiz = Quiz::lockForUpdate()->findOrFail($attempt->quiz_id);
            $source = $this->authorize($user, $quiz);
            $this->expireFor($user, $quiz);
            $this->invalidateStale($user, $quiz, $source);
            $attempt = QuizAttempt::lockForUpdate()->findOrFail($id);
            $this->expire($attempt);

            return $this->serialize($attempt);
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function mutate(User $user, int $id, array $input, bool $submit): array
    {
        return DB::transaction(function () use ($user, $id, $input, $submit) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $attempt = QuizAttempt::where('user_id', $user->id)->whereIn('status', ['active', 'submitted', 'expired', 'invalidated'])->findOrFail($id);
            $quiz = Quiz::lockForUpdate()->findOrFail($attempt->quiz_id);
            $source = $this->authorize($user, $quiz);
            $attempt = QuizAttempt::lockForUpdate()->findOrFail($id);
            $this->expire($attempt);
            if ($attempt->source_hash !== $source && $attempt->status === 'active') {
                $attempt->update(['status' => 'invalidated', 'final_reason' => 'source_changed', 'submitted_at' => now(), 'version' => $attempt->version + 1]);
            }
            $this->expire($attempt);
            $answers = $submit ? [] : $this->validateAnswers($attempt, $input['answers']);
            $flags = $submit ? [] : ($input['flagged_question_ids'] ?? []);
            $validIds = array_column($attempt->snapshot['questions'], 'id');
            if (array_diff($flags, $validIds) !== []) {
                throw ValidationException::withMessages(['flagged_question_ids' => 'Soal yang ditandai tidak ada dalam attempt.']);
            }
            sort($flags);
            $hash = hash('sha256', json_encode([$submit, $input['expected_version'], $answers, $flags], JSON_THROW_ON_ERROR));
            $replay = DB::table('lms_quiz_requests')->where('attempt_id', $id)->where('request_id', $input['request_id'])->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $hash, 409, 'Identitas permintaan sudah digunakan.');

                return $this->serialize($attempt);
            }
            if ($attempt->status !== 'active') {
                return $this->serialize($attempt);
            }
            abort_unless($attempt->version === $input['expected_version'], 409, 'Jawaban sudah berubah di sesi lain. Muat ulang attempt.');
            if ($submit) {
                $this->finalize($attempt, 'submitted');
            } else {
                $attempt->update(['answers' => $answers, 'flagged_question_ids' => $flags, 'version' => $attempt->version + 1]);
            }
            DB::table('lms_quiz_requests')->insert(['attempt_id' => $id, 'request_id' => $input['request_id'], 'payload_hash' => $hash, 'created_at' => now(), 'updated_at' => now()]);

            return $this->serialize($attempt);
        });
    }

    public function expireById(int $id): void
    {
        DB::transaction(function () use ($id) {
            $owner = QuizAttempt::find($id);
            if (! $owner) {
                return;
            }
            User::whereKey($owner->user_id)->lockForUpdate()->firstOrFail();
            Quiz::whereKey($owner->quiz_id)->lockForUpdate()->firstOrFail();
            $attempt = QuizAttempt::lockForUpdate()->findOrFail($id);
            $this->expire($attempt);
        });
    }

    private function rememberStart(User $user, QuizAttempt $attempt, string $key): void
    {
        DB::table('lms_quiz_starts')->insert(['user_id' => $user->id, 'attempt_id' => $attempt->id, 'request_id' => $key, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function authorize(User $user, Quiz $quiz): string
    {
        $course = $quiz->target_course_id ? Course::find($quiz->target_course_id) : $quiz->learningMaterial?->lesson?->course;
        abort_unless($quiz->is_active && $course && $this->access->canAccessCourse($user, $course), 403);
        $unit = $this->progress->curriculum($user, $course)['quizzes'][$quiz->id] ?? null;
        abort_unless($unit && $unit['unlocked'] && $unit['source_hash'], 403, 'Tuntaskan aktivitas wajib sebelum kuis.');

        return $unit['source_hash'];
    }

    /** @return Builder<QuizAttempt> */
    private function attempts(User $user, Quiz $quiz): Builder
    {
        return QuizAttempt::where('user_id', $user->id)->where('quiz_id', $quiz->id)->whereIn('status', ['active', 'submitted', 'expired']);
    }

    private function invalidateStale(User $user, Quiz $quiz, string $source): void
    {
        foreach ($this->attempts($user, $quiz)->where('status', 'active')->where('source_hash', '!=', $source)->lockForUpdate()->get() as $attempt) {
            $attempt->update(['status' => 'invalidated', 'final_reason' => 'source_changed', 'submitted_at' => now(), 'version' => $attempt->version + 1]);
        }
    }

    private function expireFor(User $user, Quiz $quiz): void
    {
        foreach ($this->attempts($user, $quiz)->where('status', 'active')->lockForUpdate()->get() as $attempt) {
            $this->expire($attempt);
        }
    }

    private function expire(QuizAttempt $attempt): void
    {
        if ($attempt->status === 'active' && $attempt->deadline_at && $attempt->deadline_at->lessThanOrEqualTo(now())) {
            $this->finalize($attempt, 'expired');
        }
    }

    private function finalize(QuizAttempt $attempt, string $status): void
    {
        $attempt->update(['status' => $status, 'submitted_at' => $status === 'expired' ? $attempt->deadline_at : now(), 'version' => $attempt->version + 1]);
        app(LmsGradingService::class)->automated($attempt);
    }

    /**
     * @param  list<array{question_id: int, value: string|null}>  $answers
     * @return array<int, string|null>
     */
    private function validateAnswers(QuizAttempt $attempt, array $answers): array
    {
        $questions = array_column($attempt->snapshot['questions'], null, 'id');
        $result = [];
        foreach ($answers as $entry) {
            $question = ($questions[$entry['question_id']] ?? null);
            if (! $question || ($entry['value'] !== null && ($question['question_type'] ?? 'mcq') === 'mcq' && ! in_array($entry['value'], array_column($question['options'], 'value'), true)) || array_key_exists($entry['question_id'], $result)) {
                throw ValidationException::withMessages(['answers' => 'Soal atau opsi jawaban tidak valid.']);
            }
            $result[$entry['question_id']] = $entry['value'];
        }
        ksort($result);

        return $result;
    }

    /** @return array<string, mixed> */
    private function serialize(QuizAttempt $attempt): array
    {
        return ['id' => $attempt->id, 'quiz_id' => $attempt->quiz_id, 'title' => $attempt->snapshot['title'], 'status' => $attempt->status,
            'version' => $attempt->version, 'final_reason' => $attempt->final_reason, 'started_at' => $attempt->started_at->toIso8601String(), 'deadline_at' => $attempt->deadline_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(), 'answers' => (object) ($attempt->answers ?? []), 'flagged_question_ids' => $attempt->flagged_question_ids ?? [],
            'passing_score' => $attempt->snapshot['passing_score'], 'grading_policy' => $attempt->snapshot['grading_policy'],
            'max_attempts' => $attempt->snapshot['max_attempts'], 'time_limit_minutes' => $attempt->snapshot['time_limit_minutes'],
            'grade_status' => $attempt->grade_status, 'feedback' => app(LmsGradingService::class)->feedback($attempt),
            'score' => $attempt->status === 'active' || $attempt->grade_status !== 'published' ? null : $attempt->score,
            'passed' => $attempt->status !== 'active' && $attempt->grade_status === 'published' && $attempt->is_passed,
            'questions' => array_map(fn ($q) => array_intersect_key($q, array_flip(['id', 'question_type', 'question_text', 'options', 'points'])), $attempt->snapshot['questions'])];
    }
}
