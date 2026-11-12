<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LmsGradingService
{
    public function automated(QuizAttempt $attempt): void
    {
        $grades = [];
        $pending = false;
        foreach ($attempt->snapshot['questions'] as $q) {
            $type = $q['question_type'] ?? 'mcq';
            $manual = ($attempt->snapshot['grading_mode'] ?? 'hybrid') === 'manual' || $type === 'case_study';
            $points = null;
            if (! $manual) {
                $answer = $attempt->answers[$q['id']] ?? null;
                if ($type === 'mcq') {
                    $points = $answer === $q['correct_answer'] ? $q['points'] : 0;
                } else {
                    $text = ' '.app(QuizDefinitionService::class)->normalize($answer ?? '').' ';
                    $earned = 0;
                    $total = 0;
                    foreach ($q['answer_aliases'] as $group) {
                        $total += $group['weight'];
                        foreach ($group['aliases'] as $alias) {
                            if (str_contains($text, ' '.app(QuizDefinitionService::class)->normalize($alias).' ')) {
                                $earned += $group['weight'];
                                break;
                            }
                        }
                    }
                    $points = $total > 0 ? $q['points'] * $earned / $total : 0;
                }
            } else {
                $pending = true;
            }
            $grades[] = ['question_id' => $q['id'], 'points' => $points, 'feedback' => '', 'criteria' => []];
        }
        $this->revision($attempt, $grades, $pending ? 'draft' : 'published', null, 'Penilaian otomatis snapshot; jawaban manual menunggu reviewer.');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function change(User $actor, int $id, array $input, bool $publish): array
    {
        abort_unless($actor->isSuperAdmin(), 403);
        $input = Validator::make($input, ['request_id' => 'required|uuid', 'expected_version' => 'required|integer|min:0',
            'reason' => 'required|string|min:5|max:2000', 'grades' => $publish ? 'prohibited' : 'required|array|min:1|max:100',
            'grades.*.question_id' => 'required|integer|min:1', 'grades.*.points' => 'present|nullable|numeric|min:0|max:1000',
            'grades.*.feedback' => 'sometimes|nullable|string|max:5000', 'grades.*.criteria' => 'sometimes|array|max:20'])->validate();
        $input['expected_version'] = (int) $input['expected_version'];

        return DB::transaction(function () use ($actor, $id, $input, $publish) {
            $owner = QuizAttempt::whereKey($id)->firstOrFail();
            User::whereKey($owner->user_id)->lockForUpdate()->firstOrFail();
            Quiz::whereKey($owner->quiz_id)->lockForUpdate()->firstOrFail();
            $attempt = QuizAttempt::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($attempt->status, ['submitted', 'expired'], true), 409, 'Attempt belum diserahkan.');
            $payload = hash('sha256', json_encode([$publish, $input['expected_version'], $input['reason'], $input['grades'] ?? null], JSON_THROW_ON_ERROR));
            $replay = DB::table('lms_grade_requests')->where('attempt_id', $id)->where('request_id', $input['request_id'])->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $payload, 409);

                return $this->detail($attempt);
            }
            abort_unless($attempt->grade_version === $input['expected_version'], 409, 'Nilai telah berubah. Muat ulang review.');
            if ($publish) {
                $draft = DB::table('lms_grade_revisions')->where('attempt_id', $id)->where('revision', $attempt->grade_version)->where('status', 'draft')->first();
                abort_unless($draft !== null, 409, 'Simpan draft review dahulu.');
                $grades = json_decode($draft->question_grades, true, 512, JSON_THROW_ON_ERROR);
            } else {
                $grades = $input['grades'];
            }
            $grades = $this->validateGrades($attempt, $grades, $publish);
            $this->revision($attempt, $grades, $publish ? 'published' : 'draft', $actor->id, $input['reason']);
            DB::table('lms_grade_requests')->insert(['attempt_id' => $id, 'request_id' => $input['request_id'], 'payload_hash' => $payload, 'created_at' => now(), 'updated_at' => now()]);

            return $this->detail($attempt);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $grades
     * @return list<array<string, mixed>>
     */
    private function validateGrades(QuizAttempt $attempt, array $grades, bool $complete): array
    {
        $questions = array_column($attempt->snapshot['questions'], null, 'id');
        $seen = [];
        $result = [];
        foreach ($grades as $g) {
            $q = ($questions[$g['question_id']] ?? null);
            if (! $q || isset($seen[$q['id']]) || ($g['points'] !== null && ($g['points'] < 0 || $g['points'] > $q['points']))) {
                throw ValidationException::withMessages(['grades' => 'Soal/skor duplikat, asing atau di luar bobot.']);
            }
            $seen[$q['id']] = true;
            $criteria = $g['criteria'] ?? [];
            if (($q['question_type'] ?? 'mcq') === 'case_study' && $g['points'] !== null) {
                $allowed = array_column($q['rubric'], 'max_points', 'id');
                if (array_diff(array_keys($criteria), array_keys($allowed)) || count($criteria) !== count($allowed)) {
                    throw ValidationException::withMessages(['criteria' => 'Nilai seluruh kriteria rubrik diperlukan.']);
                }
                foreach ($criteria as $key => $points) {
                    if (! is_numeric($points) || $points < 0 || $points > $allowed[$key]) {
                        throw ValidationException::withMessages(['criteria' => 'Poin kriteria melampaui rubrik.']);
                    }
                }
                if (abs(array_sum($criteria) - $g['points']) > .00001) {
                    throw ValidationException::withMessages(['criteria' => 'Jumlah rubrik tidak cocok dengan poin soal.']);
                }
            }
            if ($complete && $g['points'] === null) {
                throw ValidationException::withMessages(['grades' => 'Nilai seluruh soal sebelum publikasi.']);
            }
            $result[] = ['question_id' => $q['id'], 'points' => $g['points'] === null ? null : (float) $g['points'], 'feedback' => $g['feedback'] ?? '', 'criteria' => $criteria];
        }
        if ($complete && count($seen) !== count($questions)) {
            throw ValidationException::withMessages(['grades' => 'Ada soal yang belum dinilai.']);
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $grades
     */
    private function revision(QuizAttempt $attempt, array $grades, string $status, ?int $actor, string $reason): void
    {
        $score = null;
        $passed = false;
        if ($status === 'published') {
            $earned = array_sum(array_column($grades, 'points'));
            $total = array_sum(array_column($attempt->snapshot['questions'], 'points'));
            $raw = $total > 0 ? $earned * 100 / $total : 0;
            $score = round($raw, 2);
            $passed = $score >= $attempt->snapshot['passing_score'];
        }
        $next = $attempt->grade_version + 1;
        DB::table('lms_grade_revisions')->insert(['attempt_id' => $attempt->id, 'revision' => $next, 'actor_id' => $actor, 'status' => $status,
            'question_grades' => json_encode($grades, JSON_THROW_ON_ERROR), 'score' => $score, 'passed' => $passed, 'reason' => $reason,
            'published_at' => $status === 'published' ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
        $before = $attempt->only(['score', 'is_passed', 'grade_status', 'grade_version', 'published_revision']);
        $attempt->grade_version = $next;
        if ($status === 'published') {
            $attempt->score = $score;
            $attempt->is_passed = $passed;
            $attempt->grade_status = 'published';
            $attempt->published_revision = $next;
        } elseif ($attempt->grade_status !== 'published') {
            $attempt->grade_status = 'pending_review';
            $attempt->score = null;
            $attempt->is_passed = false;
        }
        $attempt->save();
        AuditLog::create(['actor_type' => $actor === null ? 'system' : 'admin', 'actor_id' => $actor, 'action' => 'lms.grade.'.$status,
            'auditable_type' => QuizAttempt::class, 'auditable_id' => $attempt->id, 'old_values' => $before,
            'new_values' => [...$attempt->only(['score', 'is_passed', 'grade_status', 'grade_version', 'published_revision']), 'reason' => $reason], 'ip_address' => request()->ip()]);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(QuizAttempt $attempt): array
    {
        return ['id' => $attempt->id, 'quiz_id' => $attempt->quiz_id, 'user_id' => $attempt->user_id, 'grade_version' => $attempt->grade_version,
            'grade_status' => $attempt->grade_status, 'published_revision' => $attempt->published_revision, 'snapshot' => $attempt->snapshot,
            'answers' => (object) ($attempt->answers ?? []), 'history' => DB::table('lms_grade_revisions')->where('attempt_id', $attempt->id)->orderBy('revision')->get()->map(fn ($r) => [...(array) $r, 'question_grades' => json_decode($r->question_grades, true)])->all()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function feedback(QuizAttempt $attempt): array
    {
        if (! $attempt->published_revision) {
            return [];
        }
        $revision = DB::table('lms_grade_revisions')->where('attempt_id', $attempt->id)->where('revision', $attempt->published_revision)->where('status', 'published')->first();

        return $revision ? array_values(array_map(fn ($g) => ['question_id' => $g['question_id'], 'points' => $g['points'] === null ? null : (float) $g['points'], 'feedback' => $g['feedback']], json_decode($revision->question_grades, true))) : [];
    }
}
