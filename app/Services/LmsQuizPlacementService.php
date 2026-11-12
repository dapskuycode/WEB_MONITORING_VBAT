<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LmsQuizPlacementService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function configure(Quiz $quiz, array $input): array
    {
        $placement = $input['placement'] ?? $quiz->placement;
        if ($placement === null) {
            return $input;
        }
        if (! in_array($placement, ['after_material', 'end_of_module', 'end_of_course'], true)) {
            throw ValidationException::withMessages(['placement' => 'Posisi kuis tidak valid.']);
        }
        $material = LearningMaterial::with('lesson.course')->whereKey($input['learning_material_id'] ?? $quiz->learning_material_id)->firstOrFail();

        return [...$input, 'placement' => $placement, 'target_course_id' => $material->lesson->course_id,
            'target_module_id' => $placement === 'end_of_course' ? null : $material->lesson_id];
    }

    /**
     * @param  Collection<int, LearningMaterial>  $materials
     * @return array<string, mixed>
     */
    public function slots(Course $course, Collection $materials): array
    {
        $legacyIds = $materials->pluck('quiz_id')->filter();
        $quizzes = Quiz::where(function ($q) use ($course, $legacyIds) {
            $q->where('target_course_id', $course->id)->orWhereIn('id', $legacyIds);
        })->orderBy('sort_order')->orderBy('id')->get();
        $slots = [];
        $missing = 0;
        foreach ($quizzes as $quiz) {
            $legacyRequiredMaterial = $materials->first(fn ($material) => $material->quiz_id === $quiz->id && $material->quiz_required);
            if (! $quiz->is_active && ! $legacyRequiredMaterial) {
                continue;
            }
            $kind = $quiz->placement ?? 'after_material';
            $scope = $kind === 'end_of_module' ? $materials->where('lesson_id', $quiz->target_module_id) : $materials;
            $anchor = $kind === 'after_material' ? $materials->firstWhere('id', $quiz->learning_material_id) : $scope->last();
            if (! $anchor) {
                if ($quiz->is_required) {
                    $missing++;
                }

                continue;
            }
            $legacyRequired = $anchor->quiz_id === $quiz->id && $anchor->quiz_required;
            $required = (bool) $quiz->is_required || $legacyRequired;
            if (! $quiz->is_active && ! $legacyRequired) {
                continue;
            }
            $slots[$anchor->id][] = ['quiz' => $quiz, 'placement' => $kind, 'required' => $required,
                'scope_ids' => ($kind === 'after_material' ? collect([$anchor]) : $scope->where('is_required', true))->pluck('id')->all()];
        }
        foreach ($slots as &$slot) {
            usort($slot, fn ($a, $b) => ['after_material' => 0, 'end_of_module' => 1, 'end_of_course' => 2][$a['placement']] <=> ['after_material' => 0, 'end_of_module' => 1, 'end_of_course' => 2][$b['placement']]);
        }

        return ['slots' => $slots, 'missing' => $missing];
    }
}
