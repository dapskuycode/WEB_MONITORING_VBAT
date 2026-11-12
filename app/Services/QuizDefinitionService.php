<?php

namespace App\Services;

use App\Models\Quiz;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class QuizDefinitionService
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function question(array $input): array
    {
        $input['question_type'] ??= 'mcq';
        if ($input['question_type'] !== 'mcq') {
            return $this->textQuestion($input);
        }
        $data = Validator::make($input, ['question_text' => 'required|string|max:10000',
            'options' => 'required|array|min:2|max:6', 'correct_answer' => 'required|string|max:100',
            'points' => 'required|integer|min:1|max:1000', 'sort_order' => 'sometimes|integer|min:0'])->validate();
        $options = [];
        foreach (array_values($data['options']) as $index => $option) {
            // The legacy CMS stored labels with a numeric answer index; API used label/value objects.
            $entry = is_string($option) ? ['label' => $option, 'value' => (string) $index] : $option;
            $entry = Validator::make(is_array($entry) ? $entry : [], ['label' => 'required|string|max:500', 'value' => 'required|string|max:100'])->validate();
            $options[] = ['label' => trim($entry['label']), 'value' => trim($entry['value'])];
        }
        $values = array_column($options, 'value');
        if (is_string($data['options'][0] ?? null) && ! in_array($data['correct_answer'], $values, true)) {
            $labels = array_column($options, 'label');
            $matches = array_keys($labels, $data['correct_answer'], true);
            if (count($matches) === 1) {
                $data['correct_answer'] = (string) $matches[0];
            }
        }
        if (count(array_unique($values)) !== count($values) || ! in_array($data['correct_answer'], $values, true)) {
            throw ValidationException::withMessages(['correct_answer' => 'Kunci harus menunjuk satu opsi dengan value unik.']);
        }

        return ['question_type' => 'mcq', 'answer_aliases' => [], 'rubric' => [], 'question_text' => trim($data['question_text']), 'options' => $options,
            'correct_answer' => $data['correct_answer'], 'points' => (int) $data['points'], 'sort_order' => (int) ($data['sort_order'] ?? 0)];
    }

    /** @return array<string, mixed> */
    public function snapshot(Quiz $quiz): array
    {
        $quiz->loadMissing('questions');
        if ($quiz->questions->isEmpty() || $quiz->questions->count() > 100 || $quiz->passing_score < 0 || $quiz->passing_score > 100
            || $quiz->max_attempts < 0 || $quiz->max_attempts > 10 || ($quiz->time_limit_minutes !== null && ($quiz->time_limit_minutes < 1 || $quiz->time_limit_minutes > 240))) {
            throw ValidationException::withMessages(['quiz_id' => 'Kuis belum valid untuk pilot pilihan ganda.']);
        }
        Validator::make($quiz->getAttributes(), ['grading_mode' => 'sometimes|in:auto,manual,hybrid', 'grading_policy' => 'sometimes|in:highest,latest,average'])->validate();
        $questions = [];
        foreach ($quiz->questions->sortBy([['sort_order', 'asc'], ['id', 'asc']]) as $question) {
            $questions[] = ['id' => $question->id, ...$this->question($question->only(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'sort_order', 'answer_aliases', 'rubric']))];
        }

        if ($quiz->grading_mode === 'auto' && collect($questions)->contains('question_type', 'case_study')) {
            throw ValidationException::withMessages(['grading_mode' => 'Studi kasus memerlukan manual/hybrid dan review manusia.']);
        }
        if ($quiz->shuffle_questions) {
            shuffle($questions);
        }
        if ($quiz->shuffle_options) {
            foreach ($questions as &$question) {
                if ($question['question_type'] === 'mcq') {
                    shuffle($question['options']);
                }
            }
        }

        return ['grading_mode' => $quiz->grading_mode ?? 'hybrid', 'placement' => $quiz->placement ?? 'after_material', 'title' => $quiz->title, 'passing_score' => $quiz->passing_score, 'max_attempts' => $quiz->max_attempts,
            'time_limit_minutes' => $quiz->time_limit_minutes, 'grading_policy' => $quiz->grading_policy ?? 'highest', 'questions' => $questions];
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function textQuestion(array $input): array
    {
        $input['answer_aliases'] = ($input['question_type'] ?? '') === 'short_answer' ? ($input['answer_aliases'] ?? []) : [];
        $input['rubric'] = ($input['question_type'] ?? '') === 'case_study' ? ($input['rubric'] ?? []) : [];
        $d = Validator::make($input, ['question_type' => 'required|in:short_answer,case_study', 'question_text' => 'required|string|max:10000',
            'points' => 'required|integer|min:1|max:1000', 'sort_order' => 'sometimes|integer|min:0',
            'answer_aliases' => 'sometimes|array|max:20', 'answer_aliases.*.aliases' => 'required|array|min:1|max:10',
            'answer_aliases.*.aliases.*' => 'required|string|max:100', 'answer_aliases.*.weight' => 'required|numeric|gt:0|max:1000',
            'rubric' => 'sometimes|array|max:20', 'rubric.*.id' => 'required|alpha_dash|max:50|distinct', 'rubric.*.label' => 'required|string|max:500',
            'rubric.*.max_points' => 'required|numeric|gt:0|max:1000'])->validate();
        if ($d['question_type'] === 'short_answer' && empty($d['answer_aliases'])) {
            throw ValidationException::withMessages(['answer_aliases' => 'Isi kelompok keyword/alias dan bobot jawaban singkat.']);
        }
        if ($d['question_type'] === 'case_study' && (empty($d['rubric']) || abs(array_sum(array_column($d['rubric'], 'max_points')) - $d['points']) > .00001)) {
            throw ValidationException::withMessages(['rubric' => 'Rubrik studi kasus wajib; jumlah poin kriteria harus sama dengan bobot soal.']);
        }
        foreach ($d['answer_aliases'] ?? [] as $group) {
            foreach ($group['aliases'] as $alias) {
                if ($this->normalize($alias) === '') {
                    throw ValidationException::withMessages(['answer_aliases' => 'Alias harus memuat huruf atau angka.']);
                }
            }
        }

        return ['question_type' => $d['question_type'], 'question_text' => trim($d['question_text']), 'options' => [], 'correct_answer' => '',
            'points' => (int) $d['points'], 'sort_order' => (int) ($d['sort_order'] ?? 0), 'answer_aliases' => $d['answer_aliases'] ?? [], 'rubric' => $d['rubric'] ?? []];
    }

    public function normalize(string $value): string
    {
        $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
        if ($normalized === false) {
            throw ValidationException::withMessages(['answer' => 'Teks Unicode tidak valid.']);
        }

        return trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($normalized)) ?? '');
    }
}
