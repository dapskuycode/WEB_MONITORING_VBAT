<?php

namespace Tests\Feature\Api;

use App\Livewire\BulkUpload;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsUpload;
use App\Models\User;
use App\Services\BulkImportService;
use App\Services\LmsDocumentInspection;
use App\Services\LmsUploadService;
use App\Services\MediaProxyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class LmsDocumentsImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Storage::fake('lms');
        Storage::fake('public');
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $course = Course::create(['code' => 'C01', 'title' => 'Fixture B08', 'slug' => 'b08', 'type' => 'free_class', 'status' => 'published']);
        $this->lesson = Lesson::create(['code' => 'M01', 'title' => 'Modul', 'sort_order' => 1, 'course_id' => $course->id, 'status' => 'published']);
        $this->actingAs($this->admin);
    }

    private function upload(): LmsUpload
    {
        $id = $this->postJson('/api/v1/lms/uploads', ['purpose' => 'course_material', 'file' => UploadedFile::fake()->createWithContent('board.pdf', "%PDF-1.4\nfixture B08")])->assertStatus(202)->json('data.id');

        return LmsUpload::findOrFail($id);
    }

    private function ready(): LmsUpload
    {
        $upload = $this->upload();
        $this->mock(LmsDocumentInspection::class, fn ($mock) => $mock->shouldReceive('inspect')->andReturn(3));

        return app(LmsUploadService::class)->scan($upload->id);
    }

    private function material(LmsUpload $upload): LearningMaterial
    {
        return LearningMaterial::factory()->create(['lesson_id' => $this->lesson->id, 'unit_code' => 'PDF-01', 'title' => 'PDF', 'material_type' => 'pdf_document',
            'pdf_upload_id' => $upload->id, 'pdf_path' => $upload->path, 'status' => 'published', 'is_required' => true, 'sort_order' => 1]);
    }

    private function row(array $changes = []): array
    {
        return [...array_combine(BulkImportService::TEMPLATE_COLUMNS, ['C01', 'M01', 'Modul', 1, 'MAT-01', 'Materi 1', 1, 'youtube_video', 'https://youtu.be/dQw4w9WgXcQ', '', 100, 'TRUE', 'FALSE', '', '', '']), ...$changes];
    }

    private function csv(array $rows): UploadedFile
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, BulkImportService::TEMPLATE_COLUMNS, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ',', '"', '');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return UploadedFile::fake()->createWithContent('import.csv', $csv);
    }

    private function preview(array $rows)
    {
        return $this->postJson('/api/v1/lms/imports', ['file' => $this->csv($rows)]);
    }

    private function event(string $session, int $sequence, int $page, bool $confirm = false, ?string $eventId = null)
    {
        return $this->postJson('/api/v1/lms/reading/'.$session.'/events', ['event_id' => $eventId ?? (string) Str::uuid(), 'sequence' => $sequence, 'page' => $page, 'confirm_read' => $confirm]);
    }

    public function test_pdf_is_private_quarantined_and_only_ready_after_successful_scan(): void
    {
        $upload = $this->upload();
        $this->assertSame('quarantine', $upload->status);
        Storage::disk('public')->assertMissing($upload->path);
        $this->mock(LmsDocumentInspection::class, fn ($m) => $m->shouldReceive('inspect')->andThrow(new \RuntimeException('unavailable')));
        $this->assertSame('quarantine', app(LmsUploadService::class)->scan($upload->id)->status);
        $this->mock(LmsDocumentInspection::class, fn ($m) => $m->shouldReceive('inspect')->andReturn(3));
        $ready = app(LmsUploadService::class)->scan($upload->id);
        $this->assertSame('ready', $ready->status);
        $this->assertSame(3, $ready->page_count);
        $this->getJson('/api/v1/lms/uploads/'.$upload->id)->assertOk()->assertJsonMissingPath('data.path');
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->getJson('/api/v1/lms/uploads/'.$upload->id)->assertNotFound();
    }

    public function test_malware_bad_mime_owner_purpose_and_unready_files_are_rejected(): void
    {
        $this->postJson('/api/v1/lms/uploads', ['purpose' => 'course_material', 'file' => UploadedFile::fake()->createWithContent('fake.pdf', '<script>bad</script>')])->assertUnprocessable();
        $this->postJson('/api/v1/lms/uploads', ['purpose' => 'assignment_submission', 'file' => UploadedFile::fake()->createWithContent('a.pdf', '%PDF-1.4')])->assertUnprocessable();
        $upload = $this->upload();
        $this->mock(LmsDocumentInspection::class, fn ($m) => $m->shouldReceive('inspect')->andThrow(new \DomainException('malware_detected')));
        $this->assertSame('rejected', app(LmsUploadService::class)->scan($upload->id)->status);
        $this->actingAs(User::factory()->create())->postJson('/api/v1/lms/uploads', ['purpose' => 'course_material'])->assertForbidden();
    }

    public function test_reader_requires_last_page_and_explicit_confirmation_then_unlocks_next(): void
    {
        $upload = $this->ready();
        $material = $this->material($upload);
        $next = LearningMaterial::factory()->youtube()->create(['lesson_id' => $this->lesson->id, 'sort_order' => 2, 'is_required' => true]);
        $student = User::factory()->create();
        $this->actingAs($student);
        $key = (string) Str::uuid();
        $start = $this->postJson('/api/v1/lms/materials/'.$material->id.'/reading', ['request_id' => $key])->assertOk()->json('data');
        $this->get($start['media_url'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson('/api/v1/lms/materials/'.$material->id.'/reading', ['request_id' => $key])->assertOk()->assertJsonPath('data.session_id', $start['session_id']);
        $this->event($start['session_id'], 1, 3, true)->assertUnprocessable();
        $this->event($start['session_id'], 1, 4)->assertUnprocessable();
        $event = (string) Str::uuid();
        $this->event($start['session_id'], 1, 3, eventId: $event)->assertOk()->assertJsonPath('data.progress.completed', false)->assertJsonPath('data.progress.reached_end', true);
        $this->event($start['session_id'], 1, 3, eventId: $event)->assertOk()->assertJsonPath('data.sequence', 1);
        $this->event($start['session_id'], 1, 2, eventId: $event)->assertConflict();
        $this->event($start['session_id'], 3, 3)->assertConflict();
        $this->getJson('/api/v1/lms/courses/'.$this->lesson->course_id)->assertOk()->assertJsonPath('data.modules.0.activities.1.can_open', false);
        $this->event($start['session_id'], 2, 3, true)->assertOk()->assertJsonPath('data.progress.completed', true);
        $this->getJson('/api/v1/lms/courses/'.$this->lesson->course_id)->assertOk()->assertJsonPath('data.course.progress.percent', 50)->assertJsonPath('data.next_unit.id', $next->id)->assertJsonPath('data.modules.0.activities.1.can_open', true);
        $this->postJson('/api/v1/lms/materials/'.$material->id.'/reading', ['request_id' => (string) Str::uuid()])->assertOk()->assertJsonPath('data.resume_page', 3)->assertJsonPath('data.progress.completed', true);
        $this->actingAs(User::factory()->create());
        $this->event($start['session_id'], 3, 3)->assertNotFound();
        $this->get($start['media_url'])->assertForbidden();
    }

    public function test_legacy_signed_media_cannot_bypass_scan_status(): void
    {
        $upload = $this->ready();
        $material = $this->material($upload);
        $student = User::factory()->create();
        $this->actingAs($student);
        $url = app(MediaProxyService::class)->generateProxyUrl('pdf', $material->id, $student->id);
        $material->update(['pdf_upload_id' => null]);
        $this->get($url)->assertForbidden();
        $material->update(['pdf_upload_id' => $upload->id]);
        $upload->update(['status' => 'quarantine']);
        $this->get($url)->assertForbidden();
    }

    public function test_reader_expiry_revision_access_and_changed_bytes_are_checked_on_replay(): void
    {
        $upload = $this->ready();
        $material = $this->material($upload);
        $this->actingAs(User::factory()->create());
        $session = $this->postJson('/api/v1/lms/materials/'.$material->id.'/reading', ['request_id' => (string) Str::uuid()])->assertOk()->json('data.session_id');
        $event = (string) Str::uuid();
        $this->event($session, 1, 2, eventId: $event)->assertOk();
        $material->update(['revision' => 2]);
        $this->event($session, 1, 2, eventId: $event)->assertConflict();
        $session = $this->postJson('/api/v1/lms/materials/'.$material->id.'/reading', ['request_id' => (string) Str::uuid()])->assertOk()->json('data.session_id');
        $this->travel(31)->minutes();
        $this->event($session, 1, 2)->assertConflict();
        Storage::disk('lms')->put($upload->path, '%PDF-changed');
        $this->postJson('/api/v1/lms/materials/'.$material->id.'/reading', ['request_id' => (string) Str::uuid()])->assertUnprocessable();
        $material->update(['status' => 'draft']);
        $this->event($session, 1, 2)->assertForbidden();
    }

    public function test_all_rows_checked_and_any_error_prevents_all_writes(): void
    {
        $rows = [];
        for ($i = 1; $i <= 25; $i++) {
            $rows[] = $this->row(['material_code' => 'MAT-'.$i, 'material_order' => $i]);
        }
        $rows[24]['youtube_url'] = 'https://example.test/not-youtube';
        $preview = $this->preview($rows)->assertOk()->assertJsonPath('data.is_valid', false)->assertJsonPath('data.errors.0.row', 26)->json('data.id');
        $this->assertDatabaseCount('learning_materials', 0);
        $this->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertUnprocessable();
        $this->assertDatabaseCount('learning_materials', 0);
        $this->get('/api/v1/lms/imports/'.$preview.'/errors')->assertOk()->assertDownload('laporan-import.xlsx');
    }

    public function test_apply_is_atomic_actor_bound_idempotent_draft_and_audited(): void
    {
        $preview = $this->preview([$this->row(['quiz_code' => 'Q-01', 'quiz_position' => 'end_of_module'])])->assertOk()->assertJsonPath('data.is_valid', true)->json('data.id');
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertNotFound();
        $this->actingAs($this->admin);
        $this->postJson('/api/v1/lms/imports/'.$preview.'/apply', ['rows' => [['title' => 'forged']]])->assertUnprocessable();
        $first = $this->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertOk()->assertJsonPath('data.status', 'draft')->json('data');
        $this->assertSame($first, $this->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertOk()->json('data'));
        $this->assertDatabaseCount('learning_materials', 1);
        $this->assertDatabaseHas('learning_materials', ['status' => 'draft', 'quiz_required' => true]);
        $this->assertDatabaseHas('quizzes', ['code' => 'Q-01', 'is_active' => false]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'lms.import_applied', 'actor_id' => $this->admin->id]);
    }

    public function test_stale_preview_duplicate_code_position_and_expiry_are_rejected(): void
    {
        $preview = $this->preview([$this->row()])->assertOk()->json('data.id');
        $this->lesson->update(['revision' => 2]);
        $this->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertConflict();
        $preview = $this->preview([$this->row()])->assertOk()->json('data.id');
        $this->travel(31)->minutes();
        $this->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertConflict();
        $this->preview([$this->row(), $this->row()])->assertOk()->assertJsonPath('data.is_valid', false);
        $this->assertDatabaseCount('learning_materials', 0);
    }

    public function test_file_references_are_owned_ready_unambiguous_and_hash_bound(): void
    {
        $upload = $this->ready();
        $row = $this->row(['material_type' => 'pdf', 'youtube_url' => '', 'file_name' => 'board.pdf', 'file_upload_id' => $upload->id]);
        $preview = $this->preview([$row])->assertOk()->assertJsonPath('data.is_valid', true)->json('data.id');
        Storage::disk('lms')->put($upload->path, '%PDF-changed');
        $this->postJson('/api/v1/lms/imports/'.$preview.'/apply')->assertUnprocessable();
        $this->assertDatabaseCount('learning_materials', 0);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->preview([$row])->assertOk()->assertJsonPath('data.is_valid', false);
    }

    public function test_workbook_formula_multiple_sheets_and_unknown_headers_are_rejected(): void
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([BulkImportService::TEMPLATE_COLUMNS, array_values($this->row())]);
        $book->getActiveSheet()->setCellValue('F2', '=1+1');
        $path = tempnam(sys_get_temp_dir(), 'b08-');
        (new Xlsx($book))->save($path);
        try {
            $this->postJson('/api/v1/lms/imports', ['file' => new UploadedFile($path, 'formula.xlsx', null, null, true)])->assertUnprocessable();
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([BulkImportService::TEMPLATE_COLUMNS, array_values($this->row())]);
        $book->createSheet();
        $path = tempnam(sys_get_temp_dir(), 'b08-extra-');
        (new Xlsx($book))->save($path);
        try {
            $this->postJson('/api/v1/lms/imports', ['file' => new UploadedFile($path, 'multi.xlsx', null, null, true)])->assertUnprocessable();
            $book->removeSheetByIndex(1);
            $book->getSheet(0)->setCellValue('A1', 'lesson_id');
            (new Xlsx($book))->save($path);
            $this->postJson('/api/v1/lms/imports', ['file' => new UploadedFile($path, 'headers.xlsx', null, null, true)])->assertUnprocessable();
        } finally {
            unlink($path);
            $book->disconnectWorksheets();
        }
        $this->get('/api/v1/lms/imports/template')->assertOk()->assertDownload('template-lms.xlsx');
        $this->assertDatabaseCount('learning_materials', 0);
    }

    public function test_livewire_uses_bound_preview_and_invalidates_it_when_file_changes(): void
    {
        $ui = Livewire::test(BulkUpload::class)->set('file', $this->csv([$this->row()]))->call('previewFile')
            ->assertHasNoErrors()->assertSet('previewId', fn ($id) => is_string($id));
        $ui->call('importData')->assertHasNoErrors()->assertSet('importResult.imported', 1);
        $ui->set('file', $this->csv([$this->row(['material_code' => 'MAT-02', 'material_order' => 2])]))->assertSet('previewId', null)->assertSet('importResult', null);
        $this->assertDatabaseCount('learning_materials', 1);
    }

    public function test_bulk_page_has_no_demo_side_effects_and_students_cannot_import(): void
    {
        $this->get('/admin/bulk-upload')->assertOk()->assertSee('Import materi LMS');
        $this->assertDatabaseCount('courses', 1);
        $this->assertDatabaseCount('videos', 0);
        $this->actingAs(User::factory()->create())->postJson('/api/v1/lms/imports')->assertForbidden();
    }
}
