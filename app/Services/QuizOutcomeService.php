<?php

namespace App\Services;

use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

class QuizOutcomeService
{
    /**
     * @param  Collection<int, QuizAttempt>  $attempts
     * @return array<string, mixed>
     */
    public function summarize(Collection $attempts): array
    {
        $published = $attempts->filter(fn ($a) => $a->grade_status === 'published' && $a->score !== null && in_array($a->status, ['submitted', 'expired'], true));
        $latest = $published->sortByDesc('id')->first();
        if (! $latest) {
            return ['score' => null, 'passed' => false, 'attempt' => null, 'policy' => 'highest'];
        }
        $policy = $latest->snapshot['grading_policy'] ?? 'highest';
        $selected = $policy === 'latest' ? $latest : $published->sortByDesc('score')->first();
        $score = $policy === 'average' ? round($published->avg('score'), 2) : $selected->score;
        $passed = match ($policy) {
            'latest' => $latest->is_passed, 'average' => $score >= $latest->snapshot['passing_score'], default => $published->contains('is_passed', true)
        };

        return ['score' => $score, 'passed' => $passed, 'attempt' => $selected, 'policy' => $policy];
    }
}
