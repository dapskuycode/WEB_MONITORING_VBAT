<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\LmsAssignmentSubmission;
use App\Models\QuizAttempt;
use App\Services\LmsAssignmentService;
use App\Services\LmsGradebookService;
use App\Services\LmsGradingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GradeReview extends Component
{
    use WithPagination;

    public string $mode = 'quizzes';

    #[Locked]
    public ?int $attemptId = null;

    #[Locked]
    public int $gradeVersion = 0;

    #[Locked]
    public ?array $review = null;

    #[Locked]
    public string $saveKey = '';

    #[Locked]
    public string $publishKey = '';

    public array $grades = [];

    public string $reason = '';

    public string $filter = 'pending_review';

    #[Locked]
    public ?int $submissionId = null;

    #[Locked]
    public int $submissionGradeVersion = 0;

    #[Locked]
    public ?array $submissionReview = null;

    #[Locked]
    public string $subSaveKey = '';

    #[Locked]
    public string $subPublishKey = '';

    #[Locked]
    public string $subReturnKey = '';

    public ?float $submissionScore = null;

    public string $submissionFeedback = '';

    public string $submissionReason = '';

    public ?string $resubmitDeadline = null;

    public ?int $gradebookCourseId = null;

    public function boot(): void
    {
        abort_unless((bool) auth()->user()?->isLmsManager(), 403);
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $attempt = QuizAttempt::whereIn('status', ['submitted', 'expired'])->findOrFail($id);
        app(LmsGradingService::class)->assertCanManageAttempt(auth()->user(), $attempt);
        $this->adopt(app(LmsGradingService::class)->detail($attempt));
        $this->reason = '';
        $this->resetValidation();
    }

    private function adopt(array $data): void
    {
        $this->review = $data;
        $this->attemptId = $data['id'];
        $this->gradeVersion = $data['grade_version'];
        $this->saveKey = (string) Str::uuid();
        $this->publishKey = (string) Str::uuid();
        $latest = collect($data['history'])->last();
        $marks = collect($latest['question_grades'] ?? [])->keyBy('question_id');
        $this->grades = [];
        foreach ($data['snapshot']['questions'] as $q) {
            $mark = $marks->get($q['id']);
            $criteria = [];
            foreach ($q['rubric'] ?? [] as $c) {
                $criteria[$c['id']] = $mark['criteria'][$c['id']] ?? null;
            }
            $this->grades[] = ['question_id' => $q['id'], 'points' => $mark['points'] ?? null, 'feedback' => $mark['feedback'] ?? '', 'criteria' => $criteria];
        }
    }

    public function saveDraft(): void
    {
        $this->change(false);
    }

    public function publish(): void
    {
        $this->change(true);
    }

    private function change(bool $publish): void
    {
        abort_unless($this->attemptId && $this->review, 422);
        $marks = $this->grades;
        if (! $publish) {
            foreach ($this->review['snapshot']['questions'] as $index => $q) {
                if (($q['question_type'] ?? 'mcq') === 'case_study') {
                    $values = $marks[$index]['criteria'];
                    $marks[$index]['points'] = in_array(null, $values, true) || in_array('', $values, true) ? null : array_sum($values);
                } elseif ($marks[$index]['points'] === '') {
                    $marks[$index]['points'] = null;
                }
            }
        }
        try {
            $input = ['request_id' => $publish ? $this->publishKey : $this->saveKey, 'expected_version' => $this->gradeVersion, 'reason' => $this->reason];
            if (! $publish) {
                $input['grades'] = $marks;
            }
            $this->adopt(app(LmsGradingService::class)->change(auth()->user(), $this->attemptId, $input, $publish));
            session()->flash('message', $publish ? 'Nilai dipublikasikan.' : 'Draft tersimpan; hasil siswa belum berubah.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, implode(' ', $messages));
            }
        } catch (HttpExceptionInterface $e) {
            $this->addError('review', $e->getStatusCode() === 409 ? 'Nilai berubah. Buka ulang attempt sebelum melanjutkan.' : $e->getMessage());
        }
    }

    public function openSubmission(int $id): void
    {
        $detail = app(LmsAssignmentService::class)->submissionDetail(auth()->user(), $id);
        $this->submissionReview = $detail;
        $this->submissionId = (int) $detail['id'];
        $this->submissionGradeVersion = (int) $detail['grade_version'];
        $this->subSaveKey = (string) Str::uuid();
        $this->subPublishKey = (string) Str::uuid();
        $this->subReturnKey = (string) Str::uuid();
        $latest = collect($detail['history'] ?? [])->last();
        $this->submissionScore = isset($latest['score']) && $latest['score'] !== null ? (float) $latest['score'] : ($detail['score'] ?? null);
        $this->submissionFeedback = (string) ($latest['feedback'] ?? '');
        $this->submissionReason = '';
        $this->resubmitDeadline = null;
        $this->resetValidation();
    }

    public function saveSubmissionDraft(): void
    {
        $this->changeSubmission(false);
    }

    public function publishSubmission(): void
    {
        $this->changeSubmission(true);
    }

    private function changeSubmission(bool $publish): void
    {
        abort_unless($this->submissionId && $this->submissionReview, 422);
        try {
            $detail = app(LmsAssignmentService::class)->gradeSubmission(auth()->user(), $this->submissionId, [
                'request_id' => $publish ? $this->subPublishKey : $this->subSaveKey,
                'expected_version' => $this->submissionGradeVersion,
                'score' => $this->submissionScore,
                'feedback' => $this->submissionFeedback,
                'reason' => $this->submissionReason,
            ], $publish);
            $this->openSubmission((int) $detail['id']);
            session()->flash('message', $publish ? 'Nilai tugas dipublikasikan.' : 'Draft review tugas tersimpan.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, implode(' ', $messages));
            }
        } catch (HttpExceptionInterface $e) {
            $this->addError('submission', $e->getStatusCode() === 409 ? 'Status submission berubah. Muat ulang review.' : $e->getMessage());
        }
    }

    public function returnSubmission(): void
    {
        abort_unless($this->submissionId && $this->submissionReview, 422);
        try {
            $detail = app(LmsAssignmentService::class)->returnSubmission(auth()->user(), $this->submissionId, [
                'request_id' => $this->subReturnKey,
                'expected_version' => $this->submissionGradeVersion,
                'feedback' => $this->submissionFeedback,
                'reason' => $this->submissionReason,
                'resubmit_deadline_at' => $this->resubmitDeadline ?: null,
            ]);
            $this->openSubmission((int) $detail['id']);
            session()->flash('message', 'Tugas dikembalikan ke siswa untuk direvisi.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, implode(' ', $messages));
            }
        } catch (HttpExceptionInterface $e) {
            $this->addError('submission', $e->getStatusCode() === 409 ? 'Status submission berubah. Muat ulang review.' : $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();
        $managedCourseIds = $user?->managedCourseIds() ?? [];

        $attemptsQuery = QuizAttempt::whereIn('status', ['submitted', 'expired'])
            ->when(! $user?->isSuperAdmin(), function ($q) use ($managedCourseIds) {
                $q->whereHas('quiz', function ($qq) use ($managedCourseIds) {
                    $qq->whereIn('target_course_id', $managedCourseIds)
                        ->orWhereHas('learningMaterial.lesson', fn ($lq) => $lq->whereIn('course_id', $managedCourseIds));
                });
            })
            ->when($this->filter !== 'all', fn ($q) => $q->where('grade_status', $this->filter));

        $submissionsQuery = LmsAssignmentSubmission::with(['assignment:id,course_id,title,max_score', 'user:id,name,email'])
            ->when(! $user?->isSuperAdmin(), fn ($q) => $q->whereHas('assignment', fn ($aq) => $aq->whereIn('course_id', $managedCourseIds)))
            ->when($this->filter !== 'all', fn ($q) => $this->filter === 'returned' ? $q->where('status', 'returned') : $q->where('grade_status', $this->filter));

        $courses = Course::query()
            ->when(! $user?->isSuperAdmin(), fn ($q) => $q->whereIn('id', $managedCourseIds))
            ->orderBy('title')
            ->get(['id', 'title', 'code', 'status']);

        $gradebook = null;
        if ($this->mode === 'gradebook' && $this->gradebookCourseId) {
            $course = $courses->firstWhere('id', $this->gradebookCourseId);
            if ($course) {
                $gradebook = app(LmsGradebookService::class)->courseGradebook($user, $course);
            }
        }

        return view('livewire.grade-review', [
            'attempts' => $attemptsQuery->latest('id')->paginate(20),
            'submissions' => $submissionsQuery->latest('id')->paginate(20, ['*'], 'subPage'),
            'courses' => $courses,
            'gradebook' => $gradebook,
        ])->layout('layouts.admin', [
            'title' => 'Review & Buku Nilai LMS',
        ]);
    }
}
