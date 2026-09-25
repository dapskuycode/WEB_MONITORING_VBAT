<?php

namespace Database\Factories;

use App\Models\QuizQuestion;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        $options = [
            ['label' => 'Option A', 'value' => 'a'],
            ['label' => 'Option B', 'value' => 'b'],
            ['label' => 'Option C', 'value' => 'c'],
            ['label' => 'Option D', 'value' => 'd'],
        ];

        return [
            'quiz_id' => Quiz::factory(),
            'question_text' => $this->faker->sentence() . '?',
            'options' => $options,
            'correct_answer' => 'a',
            'points' => 1,
            'sort_order' => 0,
        ];
    }
}