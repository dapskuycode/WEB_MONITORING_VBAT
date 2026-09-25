<?php

namespace Database\Factories;

use App\Models\BenefitCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class BenefitCategoryFactory extends Factory
{
    protected $model = BenefitCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'slug' => \Illuminate\Support\Str::slug($name),
            'name' => $name,
            'description' => $this->faker->sentence(),
            'icon' => null,
            'value_type' => $this->faker->randomElement(['integer', 'boolean', 'string', 'json']),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
