<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentSubmission;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LmsGradebookService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function courseGradebook(User $actor, Course $course, array $filters = []): array
    {
        abort_unless($actor->isLmsManager() && $actor->canManageCourse($course), 403, 'Anda tidak memiliki akses ke gradebook kelas ini.');

        $columns = $this->buildColumns($course);
        $quizIds = array_values(array_map(fn (array $c) => (int) $c['id'], array_filter($columns, fn (array $c) => $c['kind'] === 'quiz')));
        $assignmentIds = array_values(array_map(fn (array $c) => (int) $c['id'], array_filter($columns, fn (array $c) => $c['kind'] === 'assignment')));

        $studentQuery = $this->enrolledStudentsQuery($course, $quizIds, $assignmentIds);
        if (! empty($filters['q'])) {
            $search = trim((string) $filters['q']);
            $studentQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $students = $studentQuery->orderBy('id')->get(['id', 'name', 'email']);
        $studentIds = $students->pluck('id')->all();

        $quizAttempts = $studentIds !== [] && $quizIds !== []
            ? QuizAttempt::whereIn('user_id', $studentIds)
                ->whereIn('quiz_id', $quizIds)
                ->whereIn('status', ['submitted', 'expired'])
                ->orderBy('id')
                ->get()
                ->groupBy(fn (QuizAttempt $a) => $a->user_id.':'.$a->quiz_id)
            : collect();

        $attemptIds = $quizAttempts->flatten()->pluck('id')->all();
        $draftAttemptMap = $attemptIds !== []
            ? DB::table('lms_grade_revisions')
                ->whereIn('attempt_id', $attemptIds)
                ->where('status', 'draft')
                ->get()
                ->keyBy(fn ($r) => $r->attempt_id.':'.$r->revision)
            : collect();

        $submissions = $studentIds !== [] && $assignmentIds !== []
            ? LmsAssignmentSubmission::whereIn('user_id', $studentIds)
                ->whereIn('assignment_id', $assignmentIds)
                ->orderByDesc('version')
                ->get()
                ->groupBy(fn (LmsAssignmentSubmission $s) => $s->user_id.':'.$s->assignment_id)
            : collect();

        $submissionIds = $submissions->map(fn (Collection $group) => $group->first()?->id)->filter()->values()->all();
        $draftSubMap = $submissionIds !== []
            ? DB::table('lms_submission_grade_revisions')
                ->whereIn('submission_id', $submissionIds)
                ->where('status', 'draft')
                ->get()
                ->keyBy(fn ($r) => $r->submission_id.':'.$r->revision)
            : collect();

        $rows = [];
        $statusFilter = $filters['status'] ?? null;
        foreach ($students as $student) {
            $row = $this->buildStudentRow($student, $columns, $quizAttempts, $draftAttemptMap, $submissions, $draftSubMap, true);
            if ($statusFilter && $statusFilter !== 'all') {
                $hasMatchingCell = collect($row['cells'])->contains(fn (array $c) => $c['status'] === $statusFilter);
                if (! $hasMatchingCell) {
                    continue;
                }
            }
            $rows[] = $row;
        }

        return [
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'type' => $course->type,
                'status' => $course->status,
            ],
            'columns' => $columns,
            'rows' => array_values($rows),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function studentGrades(User $student, Course $course): array
    {
        abort_unless($course->status === 'published' && ! $course->isArchived(), 404);
        $access = app(EntitlementService::class)->courseAccess($student, $course);
        abort_unless((bool) ($access['has_access'] ?? false), 403, 'Kelas belum terbuka untuk akun Anda.');

        $columns = $this->buildColumns($course);
        $quizIds = array_values(array_map(fn (array $c) => (int) $c['id'], array_filter($columns, fn (array $c) => $c['kind'] === 'quiz')));
        $assignmentIds = array_values(array_map(fn (array $c) => (int) $c['id'], array_filter($columns, fn (array $c) => $c['kind'] === 'assignment')));

        $quizAttempts = $quizIds !== []
            ? QuizAttempt::where('user_id', $student->id)
                ->whereIn('quiz_id', $quizIds)
                ->whereIn('status', ['submitted', 'expired'])
                ->orderBy('id')
                ->get()
                ->groupBy(fn (QuizAttempt $a) => $a->user_id.':'.$a->quiz_id)
            : collect();

        $submissions = $assignmentIds !== []
            ? LmsAssignmentSubmission::where('user_id', $student->id)
                ->whereIn('assignment_id', $assignmentIds)
                ->orderByDesc('version')
                ->get()
                ->groupBy(fn (LmsAssignmentSubmission $s) => $s->user_id.':'.$s->assignment_id)
            : collect();

        $row = $this->buildStudentRow($student, $columns, $quizAttempts, collect(), $submissions, collect(), false);

        $items = [];
        foreach ($columns as $col) {
            $cell = $row['cells'][$col['key']];
            $items[] = [
                'key' => $col['key'],
                'kind' => $col['kind'],
                'id' => $col['id'],
                'module_id' => $col['module_id'],
                'title' => $col['title'],
                'is_required' => $col['is_required'],
                'max_score' => $col['max_score'],
                'passing_score' => $col['passing_score'],
                'weight' => $col['weight'],
                'status' => $cell['status'],
                'score' => $cell['score'],
                'score_percent' => $cell['score_percent'],
                'is_passed' => $cell['is_passed'],
                'is_late' => $cell['is_late'],
            ];
        }

        return [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'summary' => $row['summary'],
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function bulkPublish(User $actor, array $input): array
    {
        abort_unless($actor->isLmsManager(), 403);
        $data = Validator::make($input, [
            'request_id' => 'required|uuid',
            'reason' => 'required|string|min:5|max:2000',
            'items' => 'required|array|min:1|max:100',
            'items.*.kind' => ['required', Rule::in(['quiz', 'assignment'])],
            'items.*.id' => 'required|integer|min:1',
            'items.*.expected_version' => 'required|integer|min:0',
        ])->validate();

        return DB::transaction(function () use ($actor, $data) {
            $scopeKey = 'bulk_publish:'.$actor->id;
            $payloadHash = hash('sha256', json_encode([
                'reason' => $data['reason'],
                'items' => $data['items'],
            ], JSON_THROW_ON_ERROR));

            $replay = DB::table('lms_submission_requests')
                ->where('scope_key', $scopeKey)
                ->where('request_id', $data['request_id'])
                ->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $payloadHash, 409, 'Request ID sudah digunakan dengan muatan berbeda.');

                return json_decode((string) $replay->response_json, true, 512, JSON_THROW_ON_ERROR);
            }

            $published = [];
            foreach ($data['items'] as $idx => $item) {
                $childReqId = $this->deterministicUuid($data['request_id'].':'.$idx.':'.$item['kind'].':'.$item['id']);
                if ($item['kind'] === 'quiz') {
                    $detail = app(LmsGradingService::class)->change($actor, (int) $item['id'], [
                        'request_id' => $childReqId,
                        'expected_version' => (int) $item['expected_version'],
                        'reason' => $data['reason'],
                    ], true);
                    $published[] = [
                        'kind' => 'quiz',
                        'id' => (int) $detail['id'],
                        'grade_status' => $detail['grade_status'],
                        'grade_version' => (int) $detail['grade_version'],
                    ];
                } else {
                    $detail = app(LmsAssignmentService::class)->gradeSubmission($actor, (int) $item['id'], [
                        'request_id' => $childReqId,
                        'expected_version' => (int) $item['expected_version'],
                        'reason' => $data['reason'],
                    ], true);
                    $published[] = [
                        'kind' => 'assignment',
                        'id' => (int) $detail['id'],
                        'grade_status' => $detail['grade_status'],
                        'grade_version' => (int) $detail['grade_version'],
                        'score' => $detail['score'],
                        'score_percent' => $detail['score_percent'],
                    ];
                }
            }

            $result = [
                'published_count' => count($published),
                'items' => $published,
            ];

            DB::table('lms_submission_requests')->insert([
                'scope_key' => $scopeKey,
                'request_id' => $data['request_id'],
                'payload_hash' => $payloadHash,
                'response_json' => json_encode($result, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $result;
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildColumns(Course $course): array
    {
        $lessons = $course->lessons()->where('status', '!=', 'archived')->orderBy('sort_order')->orderBy('id')->get();
        $lessonIds = $lessons->pluck('id')->all();
        $materials = $lessonIds !== []
            ? DB::table('learning_materials')->whereIn('lesson_id', $lessonIds)->whereNull('deleted_at')->get(['id', 'lesson_id', 'quiz_id'])
            : collect();
        $materialIds = $materials->pluck('id')->all();
        $legacyQuizIds = $materials->pluck('quiz_id')->filter()->all();
        $materialLessonMap = $materials->pluck('lesson_id', 'id')->all();

        $quizzes = Quiz::where('is_active', true)
            ->where(function ($q) use ($course, $materialIds, $legacyQuizIds) {
                $q->where('target_course_id', $course->id);
                if ($materialIds !== []) {
                    $q->orWhereIn('learning_material_id', $materialIds);
                }
                if ($legacyQuizIds !== []) {
                    $q->orWhereIn('id', $legacyQuizIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $assignments = LmsAssignment::where('course_id', $course->id)
            ->whereIn('status', ['published', 'closed'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $columns = [];
        foreach ($quizzes as $quiz) {
            $moduleId = $quiz->target_module_id ?: ($materialLessonMap[$quiz->learning_material_id] ?? null);
            $columns[] = [
                'key' => 'quiz:'.$quiz->id,
                'kind' => 'quiz',
                'id' => (int) $quiz->id,
                'module_id' => $moduleId ? (int) $moduleId : null,
                'title' => $quiz->title,
                'is_required' => (bool) $quiz->is_required,
                'max_score' => 100.0,
                'passing_score' => (float) $quiz->passing_score,
                'weight' => (float) ($quiz->weight ?? 1),
                'grading_policy' => $quiz->grading_policy ?? 'highest',
            ];
        }

        foreach ($assignments as $assignment) {
            $columns[] = [
                'key' => 'assignment:'.$assignment->id,
                'kind' => 'assignment',
                'id' => (int) $assignment->id,
                'module_id' => (int) $assignment->lesson_id,
                'title' => $assignment->title,
                'is_required' => (bool) $assignment->is_required,
                'max_score' => (float) $assignment->max_score,
                'passing_score' => (float) $assignment->passing_score,
                'weight' => (float) ($assignment->weight ?? 1),
                'due_at' => $assignment->due_at?->toIso8601String(),
            ];
        }

        return $columns;
    }

    /**
     * @param  list<int>  $quizIds
     * @param  list<int>  $assignmentIds
     */
    private function enrolledStudentsQuery(Course $course, array $quizIds, array $assignmentIds)
    {
        $packageTypes = [$course->type];
        if (in_array($course->type, ['android', 'iphone'], true)) {
            $packageTypes[] = Entitlement::PACKAGE_BUNDLING;
        }

        return User::query()
            ->whereNotIn('role', ['super_admin', 'instructor', 'sponsor'])
            ->where(function ($q) use ($course, $packageTypes, $quizIds, $assignmentIds) {
                $q->whereHas('entitlements', fn ($eq) => $eq->whereIn('package_type', $packageTypes));
                if ($quizIds !== []) {
                    $q->orWhereIn('id', QuizAttempt::whereIn('quiz_id', $quizIds)->select('user_id'));
                }
                if ($assignmentIds !== []) {
                    $q->orWhereIn('id', LmsAssignmentSubmission::whereIn('assignment_id', $assignmentIds)->select('user_id'))
                        ->orWhereIn('id', DB::table('lms_assignment_drafts')->whereIn('assignment_id', $assignmentIds)->select('user_id'));
                }
                $q->orWhereIn('id', DB::table('lms_playback_progress')
                    ->join('learning_materials', 'learning_materials.id', '=', 'lms_playback_progress.material_id')
                    ->join('lessons', 'lessons.id', '=', 'learning_materials.lesson_id')
                    ->where('lessons.course_id', $course->id)
                    ->select('lms_playback_progress.user_id'));
            });
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @return array<string, mixed>
     */
    private function buildStudentRow(
        User $student,
        array $columns,
        Collection $quizAttempts,
        Collection $draftAttemptMap,
        Collection $submissions,
        Collection $draftSubMap,
        bool $includeReviewerMeta
    ): array {
        $cells = [];
        $weightedEarned = 0.0;
        $weightedTotal = 0.0;
        $publishedCount = 0;
        $pendingCount = 0;
        $returnedCount = 0;
        $notSubmittedCount = 0;

        foreach ($columns as $col) {
            if ($col['kind'] === 'quiz') {
                $attempts = $quizAttempts->get($student->id.':'.$col['id'], collect());
                if ($attempts->isEmpty()) {
                    $notSubmittedCount++;
                    $cell = [
                        'status' => 'not_submitted',
                        'score' => null,
                        'score_percent' => null,
                        'is_passed' => false,
                        'is_late' => false,
                    ];
                    if ($includeReviewerMeta) {
                        $cell['attempt_id'] = null;
                        $cell['grade_version'] = null;
                        $cell['has_draft_grade'] = false;
                    }
                } else {
                    $outcome = app(QuizOutcomeService::class)->summarize($attempts);
                    $pendingAttempt = $attempts->where('grade_status', 'pending_review')->last();
                    $latestAttempt = $attempts->last();
                    if ($outcome['score'] !== null) {
                        $publishedCount++;
                        $scorePercent = round((float) $outcome['score'], 2);
                        $weight = (float) $col['weight'];
                        if ($weight > 0) {
                            $weightedEarned += $scorePercent * $weight;
                            $weightedTotal += $weight;
                        }
                        $cell = [
                            'status' => 'published',
                            'score' => $scorePercent,
                            'score_percent' => $scorePercent,
                            'is_passed' => (bool) $outcome['passed'],
                            'is_late' => false,
                        ];
                        if ($includeReviewerMeta) {
                            $cell['attempt_id'] = $latestAttempt?->id;
                            $cell['grade_version'] = $latestAttempt?->grade_version;
                            $cell['has_draft_grade'] = false;
                        }
                    } else {
                        $pendingCount++;
                        $targetAttempt = $pendingAttempt ?? $latestAttempt;
                        $hasDraft = $targetAttempt && $draftAttemptMap->has($targetAttempt->id.':'.$targetAttempt->grade_version);
                        $cell = [
                            'status' => 'pending_review',
                            'score' => null,
                            'score_percent' => null,
                            'is_passed' => false,
                            'is_late' => false,
                        ];
                        if ($includeReviewerMeta) {
                            $cell['attempt_id'] = $targetAttempt?->id;
                            $cell['grade_version'] = $targetAttempt ? (int) $targetAttempt->grade_version : null;
                            $cell['has_draft_grade'] = (bool) $hasDraft;
                        }
                    }
                }
                $cells[$col['key']] = $cell;
            } else {
                /** @var LmsAssignmentSubmission|null $latestSub */
                $latestSub = ($submissions->get($student->id.':'.$col['id']) ?? collect())->first();
                if (! $latestSub) {
                    $notSubmittedCount++;
                    $cell = [
                        'status' => 'not_submitted',
                        'score' => null,
                        'score_percent' => null,
                        'is_passed' => false,
                        'is_late' => false,
                    ];
                    if ($includeReviewerMeta) {
                        $cell['submission_id'] = null;
                        $cell['version'] = null;
                        $cell['grade_version'] = null;
                        $cell['has_draft_grade'] = false;
                        $cell['draft_score'] = null;
                    }
                } elseif ($latestSub->status === 'returned') {
                    $returnedCount++;
                    $cell = [
                        'status' => 'returned',
                        'score' => null,
                        'score_percent' => null,
                        'is_passed' => false,
                        'is_late' => (bool) $latestSub->is_late,
                    ];
                    if ($includeReviewerMeta) {
                        $cell['submission_id'] = $latestSub->id;
                        $cell['version'] = (int) $latestSub->version;
                        $cell['grade_version'] = (int) $latestSub->grade_version;
                        $cell['has_draft_grade'] = false;
                        $cell['draft_score'] = null;
                    }
                } elseif ($latestSub->grade_status === 'published' && $latestSub->score_percent !== null) {
                    $publishedCount++;
                    $scorePercent = round((float) $latestSub->score_percent, 2);
                    $weight = (float) $col['weight'];
                    if ($weight > 0) {
                        $weightedEarned += $scorePercent * $weight;
                        $weightedTotal += $weight;
                    }
                    $cell = [
                        'status' => 'published',
                        'score' => (float) $latestSub->score,
                        'score_percent' => $scorePercent,
                        'is_passed' => (bool) $latestSub->is_passed,
                        'is_late' => (bool) $latestSub->is_late,
                    ];
                    if ($includeReviewerMeta) {
                        $cell['submission_id'] = $latestSub->id;
                        $cell['version'] = (int) $latestSub->version;
                        $cell['grade_version'] = (int) $latestSub->grade_version;
                        $cell['has_draft_grade'] = false;
                        $cell['draft_score'] = null;
                    }
                } else {
                    $pendingCount++;
                    $draftRev = $draftSubMap->get($latestSub->id.':'.$latestSub->grade_version);
                    $cell = [
                        'status' => 'pending_review',
                        'score' => null,
                        'score_percent' => null,
                        'is_passed' => false,
                        'is_late' => (bool) $latestSub->is_late,
                    ];
                    if ($includeReviewerMeta) {
                        $cell['submission_id'] = $latestSub->id;
                        $cell['version'] = (int) $latestSub->version;
                        $cell['grade_version'] = (int) $latestSub->grade_version;
                        $cell['has_draft_grade'] = $draftRev !== null;
                        $cell['draft_score'] = $draftRev ? (float) $draftRev->score : null;
                    }
                }
                $cells[$col['key']] = $cell;
            }
        }

        $weightedAverage = $weightedTotal > 0 ? round($weightedEarned / $weightedTotal, 2) : null;

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'cells' => $cells,
            'summary' => [
                'weighted_score_percent' => $weightedAverage,
                'published_count' => $publishedCount,
                'pending_count' => $pendingCount,
                'returned_count' => $returnedCount,
                'not_submitted_count' => $notSubmittedCount,
                'total_assessments' => count($columns),
            ],
        ];
    }

    private function deterministicUuid(string $seed): string
    {
        $hex = substr(hash('sha256', $seed), 0, 32);
        $hex[12] = '4';
        $hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
