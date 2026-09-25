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
            'type' => Course::TYPE_ANDROID,
            'is_free_class' => false,
            'is_featured' => fake()->boolean(20),
            'published_at' => now(),
            'archived_at' => null,
        ];
    }

    public function freeClass(): static
    {
        return $this->state(fn () => [
            'type' => Course::TYPE_FREE_CLASS,
            'is_free_class' => true,
        ]);
    }

    public function iphone(): static
    {
        return $this->state(fn () => ['type' => Course::TYPE_IPHONE]);
    }

    public function hardwareSolution(): static
    {
        return $this->state(fn () => ['type' => Course::TYPE_HARDWARE_SOLUTION]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => 'archived',
            'archived_at' => now(),
        ]);
    }
}
