<?php

namespace Tests\Feature\Api;

use App\Livewire\ContentCms;
use App\Livewire\QuizManager;
use App\Models\Course;
use App\Models\LearningBookmark;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LmsBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function content(): array
    {
        $course = Course::create(['title' => 'Fixture', 'slug' => 'fixture', 'status' => 'published', 'type' => 'free_class', 'is_free_class' => true]);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Module']);
        $material = LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id, 'status' => 'published',
            'youtube_url' => 'https://youtu.be/SECRETvideo', 'content_text' => 'PRIVATE-BODY',
            'pdf_path' => 'PRIVATE-FILE.pdf', 'youtube_video_id' => 'SECRETvideo', 'thumbnail_path' => null,
        ]);
        $quiz = Quiz::create(['learning_material_id' => $material->id, 'title' => 'Quiz', 'is_active' => true]);
        QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Question', 'options' => ['A', 'B'], 'correct_answer' => 'SECRET-KEY', 'points' => 1]);

        return [$course, $lesson, $material, $quiz];
    }

    public function test_student_cannot_author_or_inspect_lms_analytics(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student);
        foreach ([
            ['POST', '/courses'], ['PUT', '/courses/123'], ['DELETE', '/courses/123'], ['POST', '/courses/123/archive'],
            ['POST', '/lessons'], ['PUT', '/lessons/123'], ['DELETE', '/lessons/123'],
            ['POST', '/learning-materials'], ['PUT', '/learning-materials/123'], ['DELETE', '/learning-materials/123'],
            ['POST', '/learning-materials/123/thumbnail'], ['POST', '/learning-materials/123/pdf'], ['GET', '/learning-materials/123/analytics'],
            ['POST', '/learning-materials/bulk-import'], ['GET', '/learning-materials/bulk-import/template'], ['POST', '/learning-materials/validate-youtube'],
            ['POST', '/quizzes'], ['PUT', '/quizzes/123'], ['DELETE', '/quizzes/123'],
            ['POST', '/quizzes/123/questions'], ['PUT', '/quiz-questions/123'], ['DELETE', '/quiz-questions/123'],
        ] as [$method, $path]) {
            $this->json($method, '/api/v1'.$path, ['role' => 'super_admin'])->assertForbidden();
        }
    }

    public function test_guest_cannot_author_but_admin_can(): void
    {
        $this->postJson('/api/v1/courses', ['title' => 'Unauthorized'])->assertUnauthorized();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->postJson('/api/v1/courses', ['title' => 'Authorized', 'type' => 'android'])->assertCreated();
    }

    public function test_livewire_authoring_rechecks_role_on_component_requests(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Livewire::actingAs($student)->test(ContentCms::class)->assertForbidden();
        Livewire::actingAs($student)->test(QuizManager::class)->assertForbidden();
    }

    public function test_catalog_strips_answers_and_media_even_through_nested_relations(): void
    {
        [$course, $lesson, $material, $quiz] = $this->content();
        foreach (["/courses/{$course->id}", "/lessons/{$lesson->id}", '/learning-materials', "/learning-materials/{$material->id}", '/quizzes?with_questions=1', "/quizzes/{$quiz->id}"] as $path) {
            $response = $this->getJson('/api/v1'.$path)->assertOk();
            $body = $response->getContent();
            foreach (['SECRET-KEY', 'SECRETvideo', 'PRIVATE-BODY', 'PRIVATE-FILE', 'correct_answer', 'youtube_url', 'pdf_path', 'content_text'] as $secret) {
                $this->assertStringNotContainsString($secret, $body, $path);
            }
        }
    }

    public function test_bookmarks_do_not_bypass_catalog_redaction_or_account_scope(): void
    {
        [, , $material] = $this->content();
        $a = User::factory()->create(['role' => 'student']);
        $b = User::factory()->create(['role' => 'student']);
        LearningBookmark::create(['user_id' => $a->id, 'bookmarkable_type' => LearningMaterial::class, 'bookmarkable_id' => $material->id]);
        $response = $this->actingAs($a)->getJson('/api/v1/bookmarks')->assertOk();
        $this->assertStringNotContainsString('PRIVATE-FILE', $response->getContent());
        $this->actingAs($b)->getJson('/api/v1/bookmarks?user_id='.$a->id)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_guest_cannot_select_drafts_and_admin_can(): void
    {
        [$course, $lesson] = $this->content();
        $draft = LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'status' => 'draft']);
        $this->getJson('/api/v1/learning-materials?status=all')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/learning-materials/'.$draft->id)->assertNotFound();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->getJson('/api/v1/learning-materials?status=draft')->assertOk()->assertJsonPath('data.0.id', $draft->id);
    }

    public function test_oauth_claims_never_issue_a_token(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/auth/oauth/callback', [
            'provider' => 'google', 'provider_user' => ['id' => 'forged', 'email' => $user->email],
        ])->assertStatus(503)->assertJsonPath('error.code', 'OAUTH_UNAVAILABLE');
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('oauth_providers', 0);
    }

    public function test_profile_mutation_requires_auth_and_cannot_choose_another_account(): void
    {
        $a = User::factory()->create(['gender' => 'female']);
        $b = User::factory()->create(['name' => 'Unchanged']);
        $this->postJson('/api/v1/user/demographics', ['email' => $b->email, 'name' => 'Stolen'])->assertUnauthorized();
        $this->actingAs($a)->postJson('/api/v1/user/demographics', ['user_id' => $b->id, 'name' => 'Stolen'])->assertUnprocessable();
        $this->postJson('/api/v1/user/demographics', ['name' => 'Own profile'])->assertOk();
        $this->assertSame('Unchanged', $b->fresh()->name);
        $this->assertSame('Own profile', $a->fresh()->name);
        $this->assertSame('female', $a->fresh()->gender);
    }

    public function test_student_cannot_submit_another_students_active_attempt(): void
    {
        [, , , $quiz] = $this->content();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $a->id, 'started_at' => now()]);
        $this->actingAs($b)->postJson('/api/v1/quizzes/'.$quiz->id.'/submit-attempt', ['answers' => []])->assertStatus(410);
        $this->assertNull($attempt->fresh()->submitted_at);
    }

    public function test_logout_revokes_only_the_callers_real_token(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $token = $a->createToken('a');
        $other = $b->createToken('b');
        $this->withToken($token->plainTextToken)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token->plainTextToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
