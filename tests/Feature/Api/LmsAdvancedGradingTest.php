<?php

namespace Tests\Feature\Api;

use App\Livewire\ContentCms;
use App\Livewire\GradeReview;
use App\Livewire\QuizManager;
use App\Models\Certificate;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LmsAdvancedGradingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $admin;

    private Course $course;

    private Lesson $lesson;

    private LearningMaterial $material;

    private Quiz $quiz;

    private QuizQuestion $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Storage::fake('lms');
        $this->student = User::factory()->create(['name' => 'Fixture B09 Rafi Café']);
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->course = Course::create(['title' => 'Fixture B09 Pengukuran', 'slug' => 'b09', 'type' => 'android', 'status' => 'published']);
        $this->lesson = Lesson::create(['course_id' => $this->course->id, 'title' => 'Modul', 'status' => 'published']);
        $this->material = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->lesson->id, 'status' => 'published', 'sort_order' => 1, 'is_required' => true, 'quiz_required' => true]);
        $this->quiz = Quiz::create(['learning_material_id' => $this->material->id, 'title' => 'Kuis B09', 'is_active' => true]);
        $this->material->update(['quiz_id' => $this->quiz->id]);
        $this->question = $this->quiz->questions()->create(['question_text' => 'Pilih A', 'options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']], 'correct_answer' => 'a', 'points' => 10]);
        $this->grant('android');
        $this->complete($this->material);
        $this->actingAs($this->student);
    }

    private function grant(string $package): Entitlement
    {
        return Entitlement::create(['user_id' => $this->student->id, 'package_type' => $package, 'status' => 'active', 'starts_at' => now()->subDay(), 'expires_at' => now()->addDay()]);
    }

    private function complete(LearningMaterial $m): void
    {
        PlaybackSource::create(['material_id' => $m->id, 'video_id' => $m->youtube_video_id, 'duration_ms' => 100000, 'content_hash' => 'source-'.$m->id]);
        PlaybackProgress::create(['user_id' => $this->student->id, 'material_id' => $m->id, 'content_hash' => 'source-'.$m->id, 'ranges' => [[0, 95000]], 'covered_ms' => 95000, 'max_position_ms' => 95000, 'resume_ms' => 95000, 'completed_at' => now()]);
    }

    private function finish(array $answers): array
    {
        $attempt = $this->postJson('/api/v1/lms/quizzes/'.$this->quiz->id.'/attempts', ['request_id' => (string) Str::uuid()])->assertOk()->json('data');
        $saved = $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/answers', ['request_id' => (string) Str::uuid(), 'expected_version' => 0, 'answers' => $answers])->assertOk()->json('data');

        return $this->postJson('/api/v1/lms/attempts/'.$attempt['id'].'/submit', ['request_id' => (string) Str::uuid(), 'expected_version' => $saved['version']])->assertOk()->json('data');
    }

    private function caseQuestion(): QuizQuestion
    {
        return $this->quiz->questions()->create(['question_type' => 'case_study', 'question_text' => 'Analisis jalur suplai', 'options' => [], 'correct_answer' => '', 'points' => 10,
            'rubric' => [['id' => 'analysis', 'label' => 'Kualitas analisis dan bukti', 'max_points' => 10]], 'sort_order' => 1]);
    }

    private function review(int $id, int $version, array $grades, bool $publish = false, ?string $key = null)
    {
        return $this->postJson('/api/v1/admin/lms/reviews/'.$id.'/'.($publish ? 'publish' : 'draft'), ['request_id' => $key ?? (string) Str::uuid(), 'expected_version' => $version, 'reason' => 'Review bukti dan rubrik.', ...($publish ? [] : ['grades' => $grades])]);
    }

    public function test_case_study_draft_is_hidden_until_published_and_keys_never_leak(): void
    {
        $case = $this->caseQuestion();
        $attempt = $this->finish([['question_id' => $this->question->id, 'value' => 'a'], ['question_id' => $case->id, 'value' => 'Analisis VBAT belum membuktikan kualitas.']]);
        $this->assertNull($attempt['score']);
        $this->assertFalse($attempt['passed']);
        $this->assertSame('pending_review', $attempt['grade_status']);
        foreach (['correct_answer', 'answer_aliases', 'rubric'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($attempt));
        }
        $this->getJson('/api/v1/lms/courses/'.$this->course->id)->assertOk()->assertJsonPath('data.course.progress.completed', false);
        $this->getJson('/api/v1/admin/lms/reviews/'.$attempt['id'])->assertForbidden();
        $this->actingAs($this->admin);
        $detail = $this->getJson('/api/v1/admin/lms/reviews/'.$attempt['id'])->assertOk()->json('data');
        $draft = $this->review($attempt['id'], $detail['grade_version'], [['question_id' => $this->question->id, 'points' => 10, 'feedback' => 'Tepat'], ['question_id' => $case->id, 'points' => 6, 'criteria' => ['analysis' => 6], 'feedback' => 'Analisis cukup, tambah pengukuran.']])->assertOk()->json('data');
        $this->actingAs($this->student)->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertOk()->assertJsonPath('data.score', null)->assertJsonPath('data.feedback', []);
        $this->actingAs($this->admin);
        $this->review($attempt['id'], $draft['grade_version'], [], true)->assertOk();
        $this->actingAs($this->student)->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertOk()->assertJsonPath('data.score', 80)->assertJsonPath('data.grade_status', 'published')->assertJsonPath('data.feedback.1.feedback', 'Analisis cukup, tambah pengukuran.');
        $this->getJson('/api/v1/lms/courses/'.$this->course->id)->assertOk()->assertJsonPath('data.course.progress.completed', true);
    }

    public function test_grade_retry_conflicts_bounds_and_revision_preserve_published_result(): void
    {
        $attempt = $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $this->actingAs($this->admin);
        $version = QuizAttempt::find($attempt['id'])->grade_version;
        $this->review($attempt['id'], $version, [['question_id' => $this->question->id, 'points' => 11]])->assertUnprocessable();
        $key = (string) Str::uuid();
        $grades = [['question_id' => $this->question->id, 'points' => 0, 'feedback' => 'Revisi bukti.']];
        $draft = $this->review($attempt['id'], $version, $grades, key: $key)->assertOk()->json('data');
        $this->review($attempt['id'], $version, $grades, key: $key)->assertOk()->assertJsonPath('data.grade_version', $draft['grade_version']);
        $this->review($attempt['id'], $version, $grades)->assertConflict();
        $this->actingAs($this->student)->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertOk()->assertJsonPath('data.score', 100)->assertJsonPath('data.passed', true);
        $this->actingAs($this->admin);
        $this->review($attempt['id'], $draft['grade_version'], [], true)->assertOk();
        $this->actingAs($this->student)->getJson('/api/v1/lms/attempts/'.$attempt['id'])->assertOk()->assertJsonPath('data.score', 0)->assertJsonPath('data.passed', false);
        $this->assertDatabaseCount('lms_grade_revisions', 3);
    }

    public function test_short_answer_normalizes_nfc_case_and_matches_whole_tokens(): void
    {
        $this->question->update(['question_type' => 'short_answer', 'options' => [], 'correct_answer' => '', 'answer_aliases' => [['aliases' => ['café'], 'weight' => 1], ['aliases' => ['VBAT', 'tegangan suplai'], 'weight' => 1]]]);
        $this->quiz->update(['grading_policy' => 'latest']);
        $a = $this->finish([['question_id' => $this->question->id, 'value' => "CAFE\u{0301}, VBAT!"]]);
        $this->assertEquals(100, $a['score']);
        $b = $this->finish([['question_id' => $this->question->id, 'value' => 'cafeteria vbatt']]);
        $this->assertEquals(0, $b['score']);
        $this->getJson('/api/v1/lms/quizzes/'.$this->quiz->id)->assertOk()->assertJsonPath('data.best_score', 0);
        $this->getJson('/api/v1/quizzes/'.$this->quiz->id)->assertOk()->assertJsonMissingPath('data.questions.0.answer_aliases');
    }

    public function test_average_only_uses_published_scores_and_pending_study_does_not_pass(): void
    {
        $this->quiz->update(['grading_policy' => 'average']);
        $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $this->finish([['question_id' => $this->question->id, 'value' => 'b']]);
        $this->getJson('/api/v1/lms/quizzes/'.$this->quiz->id)->assertOk()->assertJsonPath('data.best_score', 50)->assertJsonPath('data.passed', false);
    }

    public function test_manual_mode_and_incomplete_rubric_cannot_publish(): void
    {
        $case = $this->caseQuestion();
        $this->quiz->update(['grading_mode' => 'manual']);
        $attempt = $this->finish([]);
        $this->actingAs($this->admin);
        $version = QuizAttempt::find($attempt['id'])->grade_version;
        $this->review($attempt['id'], $version, [], true)->assertUnprocessable();
        $this->review($attempt['id'], $version, [['question_id' => $case->id, 'points' => 5, 'criteria' => ['wrong' => 5]]])->assertUnprocessable();
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['id'], 'score' => null, 'grade_status' => 'pending_review']);
    }

    public function test_end_module_and_end_course_positions_use_server_scope(): void
    {
        $last = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->lesson->id, 'sort_order' => 2, 'is_required' => true]);
        $this->material->update(['quiz_required' => false, 'quiz_id' => null]);
        $this->quiz->update(['placement' => 'end_of_module', 'target_course_id' => $this->course->id, 'target_module_id' => $this->lesson->id, 'is_required' => true]);
        $this->getJson('/api/v1/lms/courses/'.$this->course->id)->assertOk()->assertJsonPath('data.modules.0.activities.2.kind', 'quiz')->assertJsonPath('data.modules.0.activities.2.can_open', false);
        $this->complete($last);
        $this->quiz->update(['placement' => 'end_of_course']);
        $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $this->getJson('/api/v1/lms/courses/'.$this->course->id)->assertOk()->assertJsonPath('data.course.progress.completed', true);
    }

    public function test_shuffle_order_is_frozen_across_resume_and_grade_keys_follow_values(): void
    {
        $this->quiz->update(['shuffle_questions' => true, 'shuffle_options' => true]);
        $second = $this->quiz->questions()->create(['question_text' => 'Kedua', 'options' => [['label' => 'Ya', 'value' => 'yes'], ['label' => 'Tidak', 'value' => 'no']], 'correct_answer' => 'yes', 'points' => 10, 'sort_order' => 1]);
        $start = $this->postJson('/api/v1/lms/quizzes/'.$this->quiz->id.'/attempts', ['request_id' => (string) Str::uuid()])->assertOk()->json('data');
        $read = $this->getJson('/api/v1/lms/attempts/'.$start['id'])->assertOk()->json('data');
        $this->assertSame($start['questions'], $read['questions']);
        $result = $this->finish([['question_id' => $this->question->id, 'value' => 'a'], ['question_id' => $second->id, 'value' => 'yes']]);
        $this->assertEquals(100, $result['score']);
    }

    public function test_hardware_needs_related_completion_package_and_premium(): void
    {
        $target = Course::create(['title' => 'HS B09', 'slug' => 'hs-b09', 'type' => 'hardware_solution', 'status' => 'published', 'prerequisite_course_id' => $this->course->id]);
        $this->getJson('/api/v1/lms/courses/'.$this->course->id.'/outcome')->assertOk()->assertJsonPath('data.hardware.has_access', false);
        $this->grant('premium');
        $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $this->getJson('/api/v1/lms/courses/'.$target->id)->assertOk()->assertJsonPath('data.course.access.has_access', true);
        $this->getJson('/api/v1/lms/courses/'.$this->course->id.'/outcome')->assertOk()->assertJsonPath('data.hardware.has_access', true)->assertJsonPath('data.hardware.courses.0.id', $target->id);
        Entitlement::where('package_type', 'premium')->update(['expires_at' => now()]);
        $this->getJson('/api/v1/lms/courses/'.$target->id)->assertOk()->assertJsonPath('data.course.access.has_access', false);
    }

    public function test_certificate_is_idempotent_private_snapshot_and_survives_expiry(): void
    {
        $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $first = $this->postJson('/api/v1/lms/courses/'.$this->course->id.'/certificate')->assertOk()->json('data');
        $this->postJson('/api/v1/lms/courses/'.$this->course->id.'/certificate')->assertOk()->assertJsonPath('data.id', $first['id']);
        $c = Certificate::find($first['id']);
        $this->assertDatabaseCount('certificates', 1);
        $this->get($first['verification_url'])->assertNotFound();
        $this->postJson('/api/v1/lms/credentials/'.$c->id.'/visibility', ['is_public' => true])->assertOk();
        $this->student->update(['name' => 'Nama terbaru']);
        Entitlement::query()->update(['status' => 'expired']);
        $this->getJson('/api/v1/lms/credentials')->assertOk()->assertJsonPath('data.items.0.learner_name', 'Fixture B09 Rafi Café');
        $this->get('/api/v1/lms/credentials/'.$c->id.'/document')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get($first['verification_url'])->assertOk()->assertJsonPath('data.is_valid', true);
        $artifact = base_path('docs/lms/screenshots/B09-certificate-fixture.pdf');
        if (! is_dir(dirname($artifact))) {
            mkdir(dirname($artifact), 0775, true);
        }
        file_put_contents($artifact, Storage::disk('lms')->get($c->document_path));
        $this->actingAs(User::factory()->create())->get('/api/v1/lms/credentials/'.$c->id.'/document')->assertNotFound();
        $this->actingAs($this->admin)->postJson('/api/v1/admin/lms/credentials/'.$c->id.'/revoke', ['reason' => 'Koreksi hasil melalui review.'])->assertOk();
        $this->get($first['verification_url'])->assertOk()->assertJsonPath('data.is_valid', false);
    }

    public function test_history_is_account_scoped_and_excludes_draft_feedback(): void
    {
        $this->caseQuestion();
        $attempt = $this->finish([]);
        $this->getJson('/api/v1/lms/history')->assertOk()->assertJsonPath('data.items.0.id', $attempt['id'])->assertJsonPath('data.items.0.score', null);
        $this->actingAs(User::factory()->create())->getJson('/api/v1/lms/history')->assertOk()->assertJsonCount(0, 'data.items');
    }

    public function test_review_livewire_preserves_student_result_until_publish(): void
    {
        $attempt = $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $this->actingAs($this->admin);
        Livewire::test(GradeReview::class)->call('open', $attempt['id'])->set('grades.0.points', 5)->set('reason', 'Meninjau bukti jawaban.')->call('saveDraft')->assertHasNoErrors()->call('publish')->assertHasNoErrors();
        $this->assertDatabaseHas('quiz_attempts', ['id' => $attempt['id'], 'score' => 50, 'is_passed' => false]);
    }

    public function test_cms_authors_text_aliases_and_case_rubric_without_json_and_maps_hs(): void
    {
        $this->actingAs($this->admin);
        $form = Livewire::test(QuizManager::class)->call('manageQuestions', $this->quiz->id)
            ->set('questionType', 'short_answer')->set('questionText', 'Tulis nilai nol pada pengukuran.')->set('questionPoints', 10)
            ->set('questionAliasGroups', [['text' => '0, nol', 'weight' => 1]])->call('saveQuestion')->assertHasNoErrors();
        $this->assertSame(['0', 'nol'], $this->quiz->questions()->latest('id')->first()->answer_aliases[0]['aliases']);
        $form->set('questionType', 'case_study')->set('questionText', 'Analisis tegangan berdasarkan bukti.')->set('questionPoints', 10)
            ->set('questionRubric', [['id' => 'evidence', 'label' => 'Bukti pengukuran', 'max_points' => 10]])->call('saveQuestion')->assertHasNoErrors();
        $this->assertSame('case_study', $this->quiz->questions()->latest('id')->first()->question_type);
        Livewire::test(ContentCms::class)->set('courseType', 'hardware_solution')->assertSee('Kelas prasyarat HS');
        $this->get('/admin/lms/reviews')->assertOk()->assertSee('Review nilai kuis');
        $this->actingAs($this->student)->get('/admin/lms/reviews')->assertForbidden();
    }

    public function test_inactive_required_quiz_on_unpublished_module_does_not_block_live_curriculum(): void
    {
        $this->finish([['question_id' => $this->question->id, 'value' => 'a']]);
        $draft = Lesson::create(['course_id' => $this->course->id, 'title' => 'Modul draft', 'status' => 'draft']);
        Quiz::create(['learning_material_id' => $this->material->id, 'title' => 'Draft kuis', 'is_active' => false, 'is_required' => true, 'placement' => 'end_of_module', 'target_course_id' => $this->course->id, 'target_module_id' => $draft->id]);
        $this->getJson('/api/v1/lms/courses/'.$this->course->id)->assertOk()->assertJsonPath('data.course.progress.completed', true);
    }
}
