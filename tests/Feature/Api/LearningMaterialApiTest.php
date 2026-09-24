<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningMaterialApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCourseAndLesson(): array
    {
        $course = Course::create([
            'title' => 'Basic Phone Repair',
            'slug' => 'basic-phone-repair-001',
            'description' => 'Learn basic phone repair techniques',
            'status' => 'published',
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Introduction to Tools',
            'sort_order' => 1,
        ]);

        return [$course, $lesson];
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_can_list_learning_materials(): void
    {
        [$course, $lesson] = $this->createCourseAndLesson();

        LearningMaterial::factory()->count(3)->create([
            'lesson_id' => $lesson->id,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/learning-materials');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_by_material_type(): void
    {
        [$course, $lesson] = $this->createCourseAndLesson();

        LearningMaterial::factory()->youtube()->create(['lesson_id' => $lesson->id]);
        LearningMaterial::factory()->pdf()->create(['lesson_id' => $lesson->id]);

        $response = $this->getJson('/api/v1/learning-materials?material_type=youtube_video');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.material_type', 'youtube_video');
    }

    public function test_can_search_learning_materials(): void
    {
        [$course, $lesson] = $this->createCourseAndLesson();

        LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'How to Replace iPhone Screen',
            'unit_code' => 'UNIT-0001',
            'status' => 'published',
        ]);

        LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Android Battery Replacement Guide',
            'unit_code' => 'UNIT-0002',
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/learning-materials?search=iPhone');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'How to Replace iPhone Screen');
    }

    public function test_can_create_youtube_material(): void
    {
        $this->actingAs($this->adminUser());

        [$course, $lesson] = $this->createCourseAndLesson();

        $payload = [
            'lesson_id' => $lesson->id,
            'unit_code' => 'UNIT-YT001',
            'unit_title' => 'Video Tutorial',
            'material_type' => 'youtube_video',
            'title' => 'iPhone Screen Repair Video',
            'description' => 'Step-by-step video guide',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration_seconds' => 600,
            'is_required' => true,
            'status' => 'published',
        ];

        $response = $this->postJson('/api/v1/learning-materials', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.unit_code', 'UNIT-YT001')
            ->assertJsonPath('data.youtube_video_id', 'dQw4w9WgXcQ')
            ->assertJsonPath('data.material_type', 'youtube_video');

        $this->assertDatabaseHas('learning_materials', [
            'unit_code' => 'UNIT-YT001',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);
    }

    public function test_rejects_invalid_youtube_url(): void
    {
        $this->actingAs($this->adminUser());

        [$course, $lesson] = $this->createCourseAndLesson();

        $payload = [
            'lesson_id' => $lesson->id,
            'unit_code' => 'UNIT-BAD001',
            'unit_title' => 'Bad URL',
            'material_type' => 'youtube_video',
            'title' => 'Bad YouTube',
            'youtube_url' => 'https://not-a-youtube-url.com/video',
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/v1/learning-materials', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_show_material_with_computed_attributes(): void
    {
        [$course, $lesson] = $this->createCourseAndLesson();

        $material = LearningMaterial::factory()->youtube()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Sample Video',
            'thumbnail_path' => null,
        ]);

        $response = $this->getJson("/api/v1/learning-materials/{$material->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $material->id)
            ->assertJsonPath('data.thumbnail_url', fn ($v) => str_contains($v, 'img.youtube.com'))
            ->assertJsonPath('data.embed_url', fn ($v) => str_contains($v, 'youtube.com/embed'));
    }

    public function test_can_update_material(): void
    {
        $this->actingAs($this->adminUser());

        [$course, $lesson] = $this->createCourseAndLesson();

        $material = LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Old Title',
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/v1/learning-materials/{$material->id}", [
            'title' => 'Updated Title',
            'description' => 'New description here',
            'is_required' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.is_required', false);
    }

    public function test_can_delete_material(): void
    {
        $this->actingAs($this->adminUser());

        [$course, $lesson] = $this->createCourseAndLesson();

        $material = LearningMaterial::factory()->create(['lesson_id' => $lesson->id]);

        $response = $this->deleteJson("/api/v1/learning-materials/{$material->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('learning_materials', ['id' => $material->id]);
    }

    public function test_unit_code_must_be_unique(): void
    {
        $this->actingAs($this->adminUser());

        [$course, $lesson] = $this->createCourseAndLesson();

        LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id,
            'unit_code' => 'UNIT-DUP',
            'status' => 'published',
        ]);

        $payload = [
            'lesson_id' => $lesson->id,
            'unit_code' => 'UNIT-DUP',
            'unit_title' => 'Duplicate',
            'material_type' => 'text_content',
            'title' => 'Duplicate Unit',
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/v1/learning-materials', $payload);

        $response->assertStatus(422);
    }

    public function test_archived_materials_excluded_from_default_list(): void
    {
        [$course, $lesson] = $this->createCourseAndLesson();

        LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'published']);
        LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'archived']);
        LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'draft']);

        $response = $this->getJson('/api/v1/learning-materials');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'published');
    }

    public function test_can_filter_by_explicit_status(): void
    {
        [$course, $lesson] = $this->createCourseAndLesson();

        LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'published']);
        LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'draft']);

        $response = $this->getJson('/api/v1/learning-materials?status=draft');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'draft');
    }
}
