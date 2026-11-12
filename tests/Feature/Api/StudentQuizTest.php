<?php

namespace Tests\Feature\Api;

use App\Livewire\QuizManager;
use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\PlaybackProgress;
use App\Models\PlaybackSource;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StudentQuizTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private LearningMaterial $material;

    private Quiz $quiz;

    private QuizQuestion $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->student = User::factory()->create();
        $course = Course::create(['title' => 'Pilot B07', 'slug' => 'pilot', 'type' => 'free_class', 'status' => 'published']);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Modul', 'status' => 'published']);
        $this->material = LearningMaterial::factory()->youtube()->create(['lesson_id' => $lesson->id, 'sort_order' => 1, 'is_required' => true, 'quiz_required' => true]);
        $this->quiz = Quiz::create(['learning_material_id' => $this->material->id, 'title' => 'MCQ', 'max_attempts' => 0, 'passing_score' => 70, 'time_limit_minutes' => null, 'is_active' => true]);
        $this->material->update(['quiz_id' => $this->quiz->id]);
        $this->question = $this->quiz->questions()->create(['question_text' => 'Pilih satu', 'options' => [['label' => 'Pertama', 'value' => 'a'], ['label' => 'Kedua', 'value' => 'b']], 'correct_answer' => 'a', 'points' => 1]);
        $this->actingAs($this->student);
    }

    private function completeVideo(?LearningMaterial $material = null, string $hash = 'fixture-source'): void
    {
        $material ??= $this->material;
        PlaybackSource::updateOrCreate(['material_id' => $material->id], ['video_id' => $material->youtube_video_id, 'duration_ms' => 100000, 'content_hash' => $hash]);
        PlaybackProgress::create(['user_id' => $this->student->id, 'material_id' => $material->id, 'content_hash' => $hash,
            'ranges' => [[0, 95000]], 'covered_ms' => 95000, 'max_position_ms' => 95000, 'resume_ms' => 95000, 'completed_at' => now()]);
    }

    private function start(?string $key = null): array
    {
        return $this->postJson('/api/v1/lms/quizzes/'.$this->quiz->id.'/attempts', ['request_id' => $key ?? (string) Str::uuid()])->assertOk()->json('data');
    }

    private function save(array $attempt, string $value = 'a', ?string $key = null)
    {
        return $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/answers', ['request_id' => $key ?? (string) Str::uuid(),
            'expected_version' => $attempt['version'], 'answers' => [['question_id' => $this->question->id, 'value' => $value]]]);
    }

    private function submit(array $attempt, ?string $key = null)
    {
        return $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/submit', ['request_id' => $key ?? (string) Str::uuid(), 'expected_version' => $attempt['version']]);
    }

    public function test_prerequisite_legacy_pass_and_forged_identity_cannot_open_quiz(): void
    {
        QuizAttempt::create(['quiz_id' => $this->quiz->id, 'user_id' => $this->student->id, 'score' => 100, 'is_passed' => true, 'submitted_at' => now()]);
        $this->postJson('/api/v1/lms/quizzes/'.$this->quiz->id.'/attempts', ['request_id' => (string) Str::uuid()])->assertForbidden();
        $this->completeVideo();
        $this->getJson('/api/v1/lms/quizzes/'.$this->quiz->id)->assertOk()->assertJsonPath('data.passed', false)->assertJsonPath('data.can_start', true);
        $this->postJson('/api/v1/lms/quizzes/'.$this->quiz->id.'/attempts', ['request_id' => (string) Str::uuid(), 'user_id' => 999])->assertUnprocessable();
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_resume_autosave_retries_version_conflict_and_ownership(): void
    {
        $this->completeVideo();
        $key = (string) Str::uuid();
        $attempt = $this->start($key);
        $this->assertSame($attempt['id'], $this->start($key)['id']);
        $alias = (string) Str::uuid();
        $this->assertSame($attempt['id'], $this->start($alias)['id']);
        $saveKey = (string) Str::uuid();
        $saved = $this->save($attempt, key: $saveKey)->assertOk()->assertJsonPath('data.version', 1)->json('data');
        $this->save($attempt, key: $saveKey)->assertOk()->assertJsonPath('data.version', 1);
        $this->save($attempt, 'b', $saveKey)->assertConflict();
        $this->save($attempt, 'b')->assertConflict();
        $this->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertOk()->assertJsonPath('data.answers.'.$this->question->id, 'a');
        $this->actingAs(User::factory()->create());
        $this->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertNotFound();
        $this->save($saved)->assertNotFound();
        $this->submit($saved)->assertNotFound();
        $this->actingAs($this->student);
        $wrong = $this->save($saved, 'b')->assertOk()->json('data');
        $this->submit($wrong)->assertOk()->assertJsonPath('data.score', 0);
        $this->assertSame('submitted', $this->start($alias)['status']);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_review_flags_are_saved_and_validated_with_the_attempt_version(): void
    {
        $this->completeVideo();
        $attempt = $this->start();
        $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/answers', ['request_id' => (string) Str::uuid(), 'expected_version' => 0,
            'answers' => [], 'flagged_question_ids' => [$this->question->id]])->assertOk()->assertJsonPath('data.flagged_question_ids.0', $this->question->id);
        $this->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertOk()->assertJsonPath('data.flagged_question_ids.0', $this->question->id);
        $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/answers', ['request_id' => (string) Str::uuid(), 'expected_version' => 1,
            'answers' => [], 'flagged_question_ids' => [999]])->assertUnprocessable();
    }

    public function test_snapshot_grading_is_server_only_immutable_and_submit_idempotent(): void
    {
        $this->completeVideo();
        $attempt = $this->start();
        $this->quiz->update(['passing_score' => 100, 'time_limit_minutes' => 1]);
        $this->question->update(['correct_answer' => 'b', 'points' => 100]);
        $saved = $this->save($attempt)->assertOk()->json('data');
        $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/submit', ['request_id' => (string) Str::uuid(), 'expected_version' => 1, 'score' => 100])->assertUnprocessable();
        $key = (string) Str::uuid();
        $result = $this->submit($saved, $key)->assertOk()->assertJsonPath('data.score', 100)->assertJsonPath('data.passed', true)->assertJsonPath('data.passing_score', 70)->json('data');
        $this->submit($saved, $key)->assertOk()->assertJsonPath('data.version', $result['version']);
        $this->save($saved, 'b')->assertOk()->assertJsonPath('data.score', 100);
        $this->assertStringNotContainsString('correct_answer', json_encode($result));
        $this->assertNull($result['deadline_at']);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_deadline_finalizes_saved_answers_and_late_answers_cannot_change_grade(): void
    {
        $this->completeVideo();
        $this->quiz->update(['time_limit_minutes' => 1]);
        $attempt = $this->start();
        $saved = $this->save($attempt, 'b')->assertOk()->json('data');
        $this->travel(60)->seconds();
        $this->save($saved, 'a')->assertOk()->assertJsonPath('data.status', 'expired')->assertJsonPath('data.score', 0)->assertJsonPath('data.answers.'.$this->question->id, 'b');
        $this->submit($saved)->assertOk()->assertJsonPath('data.score', 0);
        $this->assertFalse(QuizAttempt::find($attempt['id'])->is_passed);
    }

    public function test_scheduler_expires_without_open_app_and_repeats_safely(): void
    {
        $this->completeVideo();
        $this->quiz->update(['time_limit_minutes' => 1]);
        $attempt = $this->start();
        $this->save($attempt)->assertOk();
        $this->travel(61)->seconds();
        $this->artisan('lms:expire-quiz-attempts')->expectsOutput('Expired attempts processed: 1')->assertSuccessful();
        $this->artisan('lms:expire-quiz-attempts')->expectsOutput('Expired attempts processed: 0')->assertSuccessful();
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['id'], 'status' => 'expired', 'score' => 100, 'is_passed' => true]);
    }

    public function test_attempt_limit_and_unlimited_testing_default_with_highest_result(): void
    {
        $this->completeVideo();
        $this->quiz->update(['max_attempts' => 2]);
        for ($i = 0; $i < 2; $i++) {
            $attempt = $this->start();
            $saved = $this->save($attempt, 'b')->assertOk()->json('data');
            $this->submit($saved)->assertOk()->assertJsonPath('data.passed', false);
        }
        $this->postJson('/api/v1/lms/quizzes/'.$this->quiz->id.'/attempts', ['request_id' => (string) Str::uuid()])->assertConflict();
        $this->quiz->update(['max_attempts' => 0]);
        $attempt = $this->start();
        $saved = $this->save($attempt)->assertOk()->json('data');
        $this->submit($saved)->assertOk()->assertJsonPath('data.score', 100);
        $this->getJson('/api/v1/lms/quizzes/'.$this->quiz->id)->assertOk()->assertJsonPath('data.remaining_attempts', null)->assertJsonPath('data.best_score', 100)->assertJsonPath('data.can_start', false);
        $this->assertDatabaseCount('quiz_attempts', 3);
    }

    public function test_invalid_options_foreign_questions_and_revoked_access_cannot_write(): void
    {
        $this->completeVideo();
        $attempt = $this->start();
        $this->save($attempt, 'forged')->assertUnprocessable();
        $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/answers', ['request_id' => (string) Str::uuid(), 'expected_version' => 0,
            'answers' => [['question_id' => 999, 'value' => 'a']]])->assertUnprocessable();
        $this->material->lesson->course->update(['type' => 'android']);
        $grant = Entitlement::create(['user_id' => $this->student->id, 'package_type' => 'android', 'status' => 'active', 'starts_at' => now()->subDay()]);
        $saved = $this->save($attempt)->assertOk()->json('data');
        $grant->update(['status' => 'revoked']);
        $this->submit($saved)->assertForbidden();
        $this->assertNull(QuizAttempt::find($attempt['id'])->submitted_at);
    }

    public function test_admin_cms_and_api_use_same_valid_option_format(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin);
        Livewire::test(QuizManager::class)->set('managingQuizId', $this->quiz->id)->set('questionText', 'CMS fixture')
            ->set('questionOptions', ['Ya', 'Tidak'])->set('questionCorrectAnswer', 1)->call('saveQuestion')->assertHasNoErrors();
        $cms = $this->quiz->questions()->where('question_text', 'CMS fixture')->firstOrFail();
        $this->assertSame([['label' => 'Ya', 'value' => '0'], ['label' => 'Tidak', 'value' => '1']], $cms->options);
        $this->assertSame('1', $cms->correct_answer);
        $this->postJson('/api/v1/quizzes/'.$this->quiz->id.'/questions', ['question_text' => 'API', 'options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']], 'correct_answer' => 'missing'])->assertUnprocessable();
        $this->actingAs($this->student);
        $this->completeVideo();
        $attempt = $this->start();
        $saved = $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/answers', ['request_id' => (string) Str::uuid(), 'expected_version' => 0,
            'answers' => [['question_id' => $this->question->id, 'value' => 'a'], ['question_id' => $cms->id, 'value' => '1']]])->assertOk()->json('data');
        $this->submit($saved)->assertOk()->assertJsonPath('data.score', 100)->assertJsonPath('data.passed', true);
    }

    public function test_source_change_invalidates_active_attempt_without_reusing_old_pass(): void
    {
        $this->completeVideo();
        $old = $this->start();
        $this->completeVideo(hash: 'new-source');
        $new = $this->start();
        $this->assertNotSame($old['id'], $new['id']);
        $this->assertDatabaseHas('quiz_attempts', ['id' => $old['id'], 'status' => 'invalidated', 'final_reason' => 'source_changed']);
        $this->submit($old)->assertOk()->assertJsonPath('data.status', 'invalidated')->assertJsonPath('data.passed', false);
    }

    public function test_two_video_mcq_pilot_uses_real_playback_and_unlocks_only_after_server_pass(): void
    {
        config(['services.youtube.api_key' => 'fixture']);
        Http::preventStrayRequests();
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => [['id' => 'dQw4w9WgXcQ', 'status' => ['embeddable' => true, 'privacyStatus' => 'unlisted', 'uploadStatus' => 'processed'], 'contentDetails' => ['duration' => 'PT1M40S']]]])]);
        $this->material->update(['quiz_id' => null, 'quiz_required' => false]);
        $second = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->material->lesson_id, 'sort_order' => 2, 'is_required' => true, 'quiz_required' => true, 'quiz_id' => $this->quiz->id]);
        $this->quiz->update(['learning_material_id' => $second->id]);
        foreach ([$this->material, $second] as $video) {
            $session = $this->postJson('/api/v1/lms/materials/'.$video->id.'/playback', ['request_id' => (string) Str::uuid()])->assertOk()->json('data.session_id');
            for ($i = 0; $i <= 19; $i++) {
                if ($i > 0) {
                    $this->travel(5)->seconds();
                }
                $this->postJson('/api/v1/lms/playback/'.$session.'/events', ['event_id' => (string) Str::uuid(), 'sequence' => $i + 1, 'position_seconds' => $i * 5, 'state' => 'playing'])->assertOk();
            }
        }
        $this->getJson('/api/v1/lms/courses/'.$this->material->lesson->course_id)->assertOk()->assertJsonPath('data.next_unit.kind', 'quiz')->assertJsonPath('data.course.progress.completed', false)
            ->assertJsonPath('data.modules.0.activities.2.can_open', true);
        $attempt = $this->start();
        $saved = $this->save($attempt)->assertOk()->json('data');
        $this->submit($saved)->assertOk()->assertJsonPath('data.passed', true);
        $this->getJson('/api/v1/lms/courses/'.$this->material->lesson->course_id)->assertOk()->assertJsonPath('data.course.progress.percent', 100)->assertJsonPath('data.next_unit', null)->assertJsonPath('data.course.progress.completed', true);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }
}
