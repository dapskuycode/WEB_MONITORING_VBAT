<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    protected $model = Quiz::class;

    public function definition(): array
    {
        return [
            'learning_material_id' => LearningMaterial::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'max_attempts' => 5,
            'passing_score' => 70,
            'time_limit_minutes' => 30,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}