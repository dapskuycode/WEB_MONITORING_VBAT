<?php

namespace Tests\Feature\Api;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialView;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseTest extends TestCase
{
    use RefreshDatabase;

    private function course(string $type = 'android'): Course
    {
        return Course::create(['title' => 'Fixture '.$type, 'slug' => fake()->uuid(), 'type' => $type, 'status' => 'published', 'is_free_class' => $type === 'free_class']);
    }

    private function grant(User $user, string $package, array $attributes = []): Entitlement
    {
        return Entitlement::create(['user_id' => $user->id, 'package_type' => $package, 'status' => 'active', 'starts_at' => now()->subDay(), 'expires_at' => now()->addDay(), ...$attributes]);
    }

    public function test_mine_is_scoped_and_empty_is_not_filled_from_catalog(): void
    {
        $android = $this->course();
        $iphone = $this->course('iphone');
        $this->course('free_class');
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->grant($a, 'android');
        $this->grant($b, 'iphone');
        $this->getJson('/api/v1/lms/courses')->assertUnauthorized();
        $this->actingAs($a)->getJson('/api/v1/lms/courses')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $android->id);
        $this->actingAs($b)->getJson('/api/v1/lms/courses')->assertOk()->assertJsonPath('data.0.id', $iphone->id);
        $this->actingAs(User::factory()->create())->getJson('/api/v1/lms/courses')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/lms/courses?scope=catalog')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/lms/courses?user_id='.$a->id)->assertUnprocessable();
        $this->getJson('/api/v1/lms/courses?email='.$a->email)->assertUnprocessable();
    }

    public function test_expired_revoked_future_and_bundling_are_consistent_with_existing_access_endpoint(): void
    {
        $this->freezeTime();
        $android = $this->course();
        $this->course('iphone');
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([['active', now()->subDay(), now(), 'expired'], ['revoked', now()->subDay(), null, 'revoked'], ['active', now()->addDay(), now()->addDays(2), 'not_started']] as [$status, $start, $end, $state]) {
            Entitlement::query()->delete();
            $this->grant($user, 'android', ['status' => $status, 'starts_at' => $start, 'expires_at' => $end]);
            $this->getJson('/api/v1/lms/courses')->assertOk()->assertJsonPath('data.0.access.state', $state)->assertJsonPath('data.0.access.has_access', false);
            $this->getJson('/api/v1/entitlements/courses/'.$android->id.'/access')->assertOk()->assertJsonPath('data.state', $state)->assertJsonPath('data.has_access', false);
        }
        $this->grant($user, 'bundling', ['expires_at' => null]);
        $this->getJson('/api/v1/lms/courses')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.access.has_access', true)->assertJsonPath('data.0.access.expires_at', null);
    }

    public function test_syllabus_is_sorted_published_only_and_explicitly_serialized(): void
    {
        $course = $this->course('free_class');
        $later = Lesson::create(['course_id' => $course->id, 'title' => 'Later', 'status' => 'published', 'sort_order' => 3]);
        $first = Lesson::create(['course_id' => $course->id, 'title' => 'First', 'status' => 'published', 'sort_order' => 0]);
        $hidden = Lesson::create(['course_id' => $course->id, 'title' => 'DRAFT-MODULE', 'status' => 'draft']);
        LearningMaterial::factory()->create(['lesson_id' => $hidden->id, 'status' => 'published', 'title' => 'HIDDEN-CHILD']);
        LearningMaterial::factory()->create(['lesson_id' => $first->id, 'status' => 'draft', 'title' => 'DRAFT-MATERIAL']);
        $material = LearningMaterial::factory()->create(['lesson_id' => $first->id, 'status' => 'published', 'material_type' => 'youtube_video', 'youtube_video_id' => 'SECRETvideo', 'content_text' => 'SECRET-TEXT', 'pdf_path' => 'SECRET.pdf']);
        $quiz = Quiz::create(['learning_material_id' => $material->id, 'title' => 'Quiz', 'is_active' => true]);
        QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Q', 'options' => ['a', 'b'], 'correct_answer' => 'SECRET-KEY', 'points' => 1]);
        $material->update(['quiz_id' => $quiz->id]);
        $this->actingAs(User::factory()->create());
        $response = $this->getJson('/api/v1/lms/courses/'.$course->id)->assertOk()->assertJsonPath('data.modules.0.id', $first->id)->assertJsonPath('data.modules.1.id', $later->id)
            ->assertJsonPath('data.modules.0.activities.0.kind', 'material')->assertJsonPath('data.modules.0.activities.1.kind', 'quiz')->assertJsonPath('data.modules.0.activities.1.can_open', false)
            ->assertJsonPath('data.first_activity.id', $material->id)->assertJsonPath('data.course.material_count', 1);
        foreach (['SECRETvideo', 'SECRET-TEXT', 'SECRET.pdf', 'SECRET-KEY', 'DRAFT-MODULE', 'DRAFT-MATERIAL', 'HIDDEN-CHILD', 'youtube_url', 'correct_answer'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
        $course->update(['status' => 'draft']);
        $this->getJson('/api/v1/lms/courses/'.$course->id)->assertNotFound();
    }

    public function test_legacy_views_are_account_scoped_participation_not_trusted_completion(): void
    {
        $course = $this->course('free_class');
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Module', 'status' => 'published']);
        $material = LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'published']);
        $a = User::factory()->create();
        $b = User::factory()->create();
        LearningMaterialView::create(['user_id' => $a->id, 'learning_material_id' => $material->id, 'progress_percent' => 100, 'viewed_at' => now(), 'completed_at' => now()]);
        $this->actingAs($a)->getJson('/api/v1/lms/courses')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.progress.available', true)->assertJsonPath('data.0.progress.percent', 0);
        $this->getJson('/api/v1/lms/courses/'.$course->id)->assertOk()->assertJsonPath('data.modules.0.activities.0.progress.completed', false);
        $this->actingAs($b)->getJson('/api/v1/lms/courses')->assertOk()->assertJsonCount(0, 'data');
        $this->assertDatabaseCount('learning_material_views', 1);
        $this->assertDatabaseCount('entitlements', 0);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_pagination_validation_and_locked_metadata_do_not_issue_media_grants(): void
    {
        $one = $this->course();
        $this->course();
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/v1/lms/courses?scope=catalog&per_page=1&page=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.last_page', 2);
        $this->getJson('/api/v1/lms/courses?scope=catalog&per_page=1&page=2')->assertOk()->assertJsonPath('meta.current_page', 2);
        $this->getJson('/api/v1/lms/courses?per_page=51')->assertUnprocessable();
        $this->getJson('/api/v1/lms/courses/'.$one->id)->assertOk()->assertJsonPath('data.course.access.state', 'locked_entitlement')->assertJsonPath('data.first_activity', null);
        $this->assertDatabaseCount('entitlements', 0);
    }
}
