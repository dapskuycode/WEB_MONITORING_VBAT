<?php

namespace Tests\Feature\Api;

use App\Livewire\ContentCms;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialView;
use App\Models\Lesson;
use App\Models\LmsUpload;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LmsAuthoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
    }

    private function tree(): array
    {
        $course = Course::create(['title' => 'Fixture', 'slug' => fake()->uuid(), 'type' => 'free_class', 'status' => 'draft', 'is_free_class' => true]);
        $lesson = Lesson::create(['course_id' => $course->id, 'title' => 'Module', 'status' => 'draft', 'sort_order' => 0]);
        $material = LearningMaterial::factory()->create(['lesson_id' => $lesson->id, 'material_type' => 'text_content', 'content_text' => 'Fixture content', 'status' => 'draft', 'sort_order' => 0]);

        return [$course, $lesson, $material];
    }

    private function youtube(bool $embeddable = true, string $privacy = 'unlisted'): void
    {
        config(['services.youtube.api_key' => 'fixture-key']);
        Http::fake(['www.googleapis.com/*' => Http::response(['items' => [['id' => 'dQw4w9WgXcQ', 'status' => ['embeddable' => $embeddable, 'privacyStatus' => $privacy, 'uploadStatus' => 'processed']]]])]);
    }

    public function test_cms_builds_and_publishes_a_pilot_using_real_models(): void
    {
        $this->youtube();
        $cms = Livewire::test(ContentCms::class)->assertSee('Konten belajar VBAT')
            ->set('courseTitle', 'Pilot reparasi')->set('courseType', 'free_class')->call('saveCourse')->assertHasNoErrors();
        $course = Course::firstOrFail();
        $cms->set('activeTab', 'lessons')->set('lessonTitle', 'Pengenalan alat')->call('saveLesson')->assertHasNoErrors();
        $lesson = Lesson::firstOrFail();
        $cms->set('activeTab', 'materials')->set('materialTitle', 'Penggunaan multimeter')
            ->set('materialCode', 'PILOT-VIDEO')->set('materialUnitTitle', 'Pengukuran dasar')
            ->set('materialYoutubeUrl', 'https://youtu.be/dQw4w9WgXcQ')->call('saveMaterial')->assertHasNoErrors();
        $material = LearningMaterial::firstOrFail();
        $this->assertSame('draft', $material->status);
        $cms->set('materialStatus', 'published')->call('saveMaterial')->assertHasNoErrors();
        $cms->call('selectLesson', $lesson->id)->set('lessonStatus', 'published')->call('saveLesson')->assertHasNoErrors();
        $cms->call('selectCourse', $course->id)->set('courseStatus', 'published')->call('saveCourse')->assertHasNoErrors();
        $this->assertSame('published', $course->fresh()->status);
        $this->assertSame('published', $lesson->fresh()->status);
        $this->assertSame('youtube_video', $material->fresh()->material_type);
        $this->assertNotNull($course->fresh()->code);
        $this->assertNotNull($lesson->fresh()->code);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => auth()->id(), 'auditable_type' => LearningMaterial::class, 'auditable_id' => $material->id]);
    }

    public function test_api_and_cms_share_validation_and_stale_revisions_cannot_overwrite(): void
    {
        [$course] = $this->tree();
        $cms = Livewire::test(ContentCms::class)->call('selectCourse', $course->id)->set('courseTitle', 'Stale CMS title');
        $this->putJson('/api/v1/courses/'.$course->id, ['title' => 'Latest API title', 'expected_revision' => 1])->assertOk()->assertJsonPath('data.revision', 2);
        $cms->call('saveCourse')->assertHasErrors('form')->assertSet('courseTitle', 'Stale CMS title');
        $this->assertSame('Latest API title', $course->fresh()->title);
        $this->putJson('/api/v1/courses/'.$course->id, ['title' => 'Stale API title', 'expected_revision' => 1])->assertConflict();
        $this->putJson('/api/v1/courses/'.$course->id, ['title' => 'No revision'])->assertUnprocessable()->assertJsonValidationErrors('expected_revision');
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_course_and_module_require_ready_children_before_publish(): void
    {
        [$course, $lesson] = $this->tree();
        $this->putJson('/api/v1/courses/'.$course->id, ['status' => 'published', 'expected_revision' => 1])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->putJson('/api/v1/lessons/'.$lesson->id, ['status' => 'published', 'expected_revision' => 1])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame('draft', $course->fresh()->status);
    }

    public function test_video_publish_rejects_missing_key_and_failed_embed_verification(): void
    {
        [, , $material] = $this->tree();
        config(['services.youtube.api_key' => null]);
        $payload = ['material_type' => 'youtube_video', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'status' => 'published', 'expected_revision' => 1, 'embed_verified' => true];
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('youtube_url');
        Http::assertNothingSent();
        $this->youtube(false);
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertUnprocessable();
        $this->assertSame('draft', $material->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_private_video_is_not_publishable_even_with_embeddable_flag(): void
    {
        [, , $material] = $this->tree();
        $this->youtube(true, 'private');
        $this->putJson('/api/v1/learning-materials/'.$material->id, ['material_type' => 'youtube_video', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'status' => 'published', 'expected_revision' => 1])->assertUnprocessable();
    }

    public function test_order_is_scoped_and_atomic_reorder_rejects_foreign_items_and_stale_parent(): void
    {
        [$course, $lesson] = $this->tree();
        $other = Lesson::create(['course_id' => $course->id, 'title' => 'Second', 'status' => 'draft', 'sort_order' => 1]);
        $this->postJson('/api/v1/lessons', ['course_id' => $course->id, 'title' => 'Duplicate', 'sort_order' => 0])->assertUnprocessable()->assertJsonValidationErrors('sort_order');
        $path = '/api/v1/lms-admin/courses/'.$course->id.'/order';
        $items = [['id' => $other->id, 'revision' => 1], ['id' => $lesson->id, 'revision' => 1]];
        $this->putJson($path, ['expected_revision' => 1, 'items' => $items])->assertOk();
        $this->assertSame(0, $other->fresh()->sort_order);
        $this->assertSame(1, $lesson->fresh()->sort_order);
        $this->putJson($path, ['expected_revision' => 1, 'items' => $items])->assertConflict();
        [$foreignCourse, $foreignLesson] = $this->tree();
        $this->putJson($path, ['expected_revision' => $course->fresh()->revision, 'items' => [['id' => $foreignLesson->id, 'revision' => 1]]])->assertUnprocessable();
        $this->assertSame(0, $foreignLesson->fresh()->sort_order);
    }

    public function test_child_edits_advance_parent_revisions_and_cannot_reparent(): void
    {
        [$course, $lesson, $material] = $this->tree();
        [$otherCourse, $otherLesson] = $this->tree();
        $this->putJson('/api/v1/learning-materials/'.$material->id, ['title' => 'Updated', 'expected_revision' => 1])->assertOk();
        $this->assertSame(2, $course->fresh()->revision);
        $this->assertSame(2, $lesson->fresh()->revision);
        $this->putJson('/api/v1/learning-materials/'.$material->id, ['lesson_id' => $otherLesson->id, 'expected_revision' => 2])->assertUnprocessable()->assertJsonValidationErrors('lesson_id');
        $this->putJson('/api/v1/lessons/'.$lesson->id, ['course_id' => $otherCourse->id, 'expected_revision' => 2])->assertUnprocessable();
    }

    public function test_draft_module_hides_its_published_material_from_students_and_media(): void
    {
        [$course, $lesson, $material] = $this->tree();
        $course->update(['status' => 'published']);
        $material->update(['status' => 'published']);
        $this->actingAs(User::factory()->create(['role' => 'student']));
        $this->getJson('/api/v1/lessons/'.$lesson->id)->assertNotFound();
        $this->getJson('/api/v1/learning-materials/'.$material->id)->assertNotFound();
        $this->getJson('/api/v1/learning-materials')->assertOk()->assertJsonCount(0, 'data');
        $this->postJson('/api/v1/media/access-check', ['material_ids' => [$material->id]])->assertOk()->assertJsonPath('results.'.$material->id.'.access', false);
    }

    public function test_archive_preserves_existing_progress_and_content_ids(): void
    {
        [$course, $lesson, $material] = $this->tree();
        $student = User::factory()->create();
        LearningMaterialView::create(['user_id' => $student->id, 'learning_material_id' => $material->id, 'viewed_at' => now(), 'progress_percent' => 40]);
        $this->deleteJson('/api/v1/courses/'.$course->id, ['expected_revision' => 1])->assertOk();
        $this->deleteJson('/api/v1/lessons/'.$lesson->id, ['expected_revision' => 1])->assertOk();
        $this->deleteJson('/api/v1/learning-materials/'.$material->id, ['expected_revision' => 1])->assertOk();
        $this->assertDatabaseCount('learning_material_views', 1);
        $this->assertDatabaseHas('learning_materials', ['id' => $material->id, 'deleted_at' => null, 'status' => 'archived']);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id, 'status' => 'archived']);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'status' => 'archived']);
    }

    public function test_preview_is_admin_only_read_only_and_escapes_text(): void
    {
        [$course, , $material] = $this->tree();
        $material->update(['content_text' => '<script>alert(1)</script>']);
        $this->get('/admin/lms/preview/'.$course->id)->assertOk()->assertSee('Mode baca saja')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('learning_material_views', 0);
        $this->assertDatabaseCount('quiz_attempts', 0);
        $this->assertDatabaseCount('entitlements', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertSame('draft', $course->fresh()->status);
        $this->actingAs(User::factory()->create(['role' => 'student']))->get('/admin/lms/preview/'.$course->id)->assertForbidden();
    }

    public function test_preview_pdf_grant_is_signed_bound_to_admin_and_expires(): void
    {
        Storage::fake('lms');
        [, , $material] = $this->tree();
        $material->update(['material_type' => 'pdf_document', 'pdf_path' => 'preview.pdf']);
        Storage::disk('lms')->put('preview.pdf', '%PDF-fixture');
        $admin = auth()->user();
        $url = URL::temporarySignedRoute('admin.lms.preview-media', now()->addMinutes(5), ['id' => $material->id, 'user' => $admin->id]);
        $this->get($url)->assertOk();
        $this->get($url.'&tampered=1')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'student']))->get($url)->assertForbidden();
        $this->actingAs($admin);
        $this->travel(6)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_failed_cms_save_preserves_input_and_does_not_report_success(): void
    {
        [$course, $lesson, $material] = $this->tree();
        Livewire::test(ContentCms::class)->call('selectCourse', $course->id)->call('selectLesson', $lesson->id)
            ->set('materialTitle', 'Keep this title')->set('materialUnitTitle', 'Unit')->set('materialCode', $material->unit_code)
            ->call('saveMaterial')->assertHasErrors('unit_code')->assertSet('materialTitle', 'Keep this title');
        $this->assertNull(session('message'));
        $this->assertDatabaseCount('learning_materials', 1);
    }

    public function test_failed_upload_save_cleans_new_file_and_preserves_previous_pdf(): void
    {
        Storage::fake('lms');
        [$course, $lesson, $material] = $this->tree();
        $material->update(['material_type' => 'pdf_document', 'pdf_path' => 'old.pdf']);
        Storage::disk('lms')->put('old.pdf', '%PDF-old');
        $cms = Livewire::test(ContentCms::class)->call('selectCourse', $course->id)->call('selectLesson', $lesson->id)->call('selectMaterial', $material->id);
        $material->update(['revision' => 2]);
        $cms->set('materialPdfFile', UploadedFile::fake()->createWithContent('new.pdf', '%PDF-new'))
            ->call('saveMaterial')->assertHasErrors('materialPdfFile');
        $this->assertSame(['old.pdf'], Storage::disk('lms')->allFiles());
        $this->assertSame('old.pdf', $material->fresh()->pdf_path);
        $this->assertNull(session('message'));
    }

    public function test_import_apply_cannot_bypass_authoring_and_published_upload_is_rejected(): void
    {
        Storage::fake('lms');
        $file = UploadedFile::fake()->createWithContent('fixture.csv', 'lesson_id,title');
        $this->postJson('/api/v1/learning-materials/bulk-import', ['file' => $file, 'preview' => false])->assertStatus(410);
        $this->assertDatabaseCount('learning_materials', 0);
        [, , $material] = $this->tree();
        $material->update(['status' => 'published']);
        $this->postJson('/api/v1/learning-materials/'.$material->id.'/pdf', ['expected_revision' => 1,
            'pdf' => UploadedFile::fake()->createWithContent('new.pdf', '%PDF-new'),
        ])->assertConflict();
        $this->assertSame([], Storage::disk('lms')->allFiles());
    }

    public function test_incomplete_quiz_blocks_publish_and_course_material_relation_uses_lessons(): void
    {
        [$course, , $material] = $this->tree();
        $quiz = Quiz::create(['learning_material_id' => $material->id, 'title' => 'Quiz', 'is_active' => true, 'max_attempts' => 3, 'passing_score' => 70]);
        $question = QuizQuestion::create(['quiz_id' => $quiz->id, 'question_text' => 'Fixture question', 'options' => [['label' => 'A', 'value' => 'a'], ['label' => 'B', 'value' => 'b']], 'correct_answer' => 'missing', 'points' => 1]);
        $payload = ['status' => 'published', 'quiz_required' => true, 'quiz_id' => $quiz->id, 'expected_revision' => 1];
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('quiz_id');
        $question->update(['correct_answer' => 'a']);
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertOk();
        $this->assertSame([$material->id], $course->learningMaterials()->pluck('learning_materials.id')->all());
    }

    public function test_pdf_publish_rejects_public_copy_and_missing_private_file(): void
    {
        Storage::fake('lms');
        Storage::fake('public');
        [, , $material] = $this->tree();
        $payload = ['material_type' => 'pdf_document', 'pdf_path' => 'learning/pdfs/a.pdf', 'status' => 'published', 'expected_revision' => 1];
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('pdf_upload_id');
        Storage::disk('lms')->put($payload['pdf_path'], '%PDF-fixture');
        $upload = LmsUpload::create(['id' => (string) Str::uuid(), 'user_id' => auth()->id(), 'purpose' => 'course_material', 'original_name' => 'a.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 12, 'sha256' => hash('sha256', '%PDF-fixture'), 'path' => $payload['pdf_path'], 'status' => 'ready', 'page_count' => 1, 'scanned_at' => now()]);
        $payload['pdf_upload_id'] = $upload->id;
        Storage::disk('public')->put($payload['pdf_path'], '%PDF-fixture');
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertUnprocessable();
        Storage::disk('public')->delete($payload['pdf_path']);
        $this->putJson('/api/v1/learning-materials/'.$material->id, $payload)->assertOk();
    }
}
