<?php

namespace App\Livewire;

use App\Models\QuizAttempt;
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

    public function boot(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $attempt = QuizAttempt::whereIn('status', ['submitted', 'expired'])->findOrFail($id);
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

    public function render()
    {
        return view('livewire.grade-review', ['attempts' => QuizAttempt::whereIn('status', ['submitted', 'expired'])->when($this->filter !== 'all', fn ($q) => $q->where('grade_status', $this->filter))->latest('id')->paginate(20)])
            ->layout('layouts.app');
    }
}
