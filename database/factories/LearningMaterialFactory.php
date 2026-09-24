<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LearningMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningMaterialFactory extends Factory
{
    protected $model = LearningMaterial::class;

    public function definition(): array
    {
        $types = ['youtube_video', 'pdf_document', 'text_content', 'external_link'];
        $type = fake()->randomElement($types);

        return [
            'lesson_id' => Lesson::factory(),
            'unit_code' => strtoupper(fake()->bothify('UNIT-####')),
            'unit_title' => fake()->sentence(3),
            'material_type' => $type,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(2),
            'youtube_url' => $type === 'youtube_video' ? 'https://www.youtube.com/watch?v=' . fake()->regexify('[a-zA-Z0-9_-]{11}') : null,
            'youtube_video_id' => $type === 'youtube_video' ? fake()->regexify('[a-zA-Z0-9_-]{11}') : null,
            'pdf_path' => $type === 'pdf_document' ? 'learning/pdfs/' . fake()->uuid() . '.pdf' : null,
            'thumbnail_path' => 'learning/thumbnails/' . fake()->uuid() . '.jpg',
            'content_text' => $type === 'text_content' ? fake()->paragraphs(3, true) : null,
            'external_url' => $type === 'external_link' ? fake()->url() : null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_required' => fake()->boolean(80),
            'quiz_required' => false,
            'quiz_id' => null,
            'status' => 'published',
            'duration_seconds' => $type === 'youtube_video' ? fake()->numberBetween(300, 7200) : 0,
            'view_count' => fake()->numberBetween(0, 500),
        ];
    }

    public function youtube(): static
    {
        return $this->state(fn () => [
            'material_type' => 'youtube_video',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 212,
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn () => [
            'material_type' => 'pdf_document',
            'pdf_path' => 'learning/pdfs/sample.pdf',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
