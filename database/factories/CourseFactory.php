<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);
        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->randomNumber(4),
            'description' => fake()->paragraph(3),
            'thumbnail_path' => 'learning/thumbnails/' . fake()->uuid() . '.jpg',
            'status' => 'published',
            'is_featured' => fake()->boolean(20),
        ];
    }
}
