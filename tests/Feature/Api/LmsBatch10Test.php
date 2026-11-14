<?php

namespace Tests\Feature\Api;

use App\Livewire\ContentCms;
use App\Livewire\GradeReview;
use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsAssignment;
use App\Models\LmsUpload;
use App\Models\User;
use App\Services\LmsDocumentInspection;
use App\Services\LmsUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LmsBatch10Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $instructorA;

    private User $instructorB;

    private User $studentA;

    private User $studentB;

    private Course $courseA;

    private Course $courseB;

    private Lesson $lessonA;

    private Lesson $lessonB;

    private LearningMaterial $materialA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        Storage::fake('lms');
        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->instructorA = User::factory()->create(['role' => 'instructor', 'name' => 'Instruktur A']);
        $this->instructorB = User::factory()->create(['role' => 'instructor', 'name' => 'Instruktur B']);
        $this->studentA = User::factory()->create(['role' => 'teknisi', 'name' => 'Siswa A']);
        $this->studentB = User::factory()->create(['role' => 'teknisi', 'name' => 'Siswa B']);

        $this->courseA = Course::create([
            'code' => 'CRS-B10-A',
            'title' => 'Kelas Diagnosa iPhone B10',
            'slug' => 'kelas-diagnosa-iphone-b10',
            'type' => Course::TYPE_IPHONE,
            'status' => 'published',
            'instructor_id' => $this->instructorA->id,
            'published_at' => now(),
        ]);
        $this->courseB = Course::create([
            'code' => 'CRS-B10-B',
            'title' => 'Kelas Android Lanjut B10',
            'slug' => 'kelas-android-lanjut-b10',
            'type' => Course::TYPE_ANDROID,
            'status' => 'published',
            'instructor_id' => $this->instructorB->id,
            'published_at' => now(),
        ]);

        $this->lessonA = Lesson::create([
            'course_id' => $this->courseA->id,
            'code' => 'MOD-B10-A1',
            'title' => 'Modul Pengukuran Jalur',
            'sort_order' => 1,
            'status' => 'published',
        ]);
        $this->lessonB = Lesson::create([
            'course_id' => $this->courseB->id,
            'code' => 'MOD-B10-B1',
            'title' => 'Modul Emmc Android',
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $this->materialA = LearningMaterial::factory()->create([
            'lesson_id' => $this->lessonA->id,
            'unit_code' => 'MAT-B10-A1',
            'unit_title' => 'Unit 1',
            'title' => 'Bacaan Skema',
            'material_type' => 'text_content',
            'content_text' => 'Panduan pengukuran hambatan dalam.',
            'status' => 'published',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        foreach ([$this->studentA, $this->studentB] as $student) {
            Entitlement::create([
                'user_id' => $student->id,
                'package_type' => Course::TYPE_IPHONE,
                'source' => 'manual',
                'granted_at' => now(),
            ]);
        }
    }

    private function uploadStudentFile(User $student, string $name = 'laporan.pdf', string $type = 'pdf'): LmsUpload
    {
        $content = match ($type) {
            'pdf' => "%PDF-1.4\nlaporan praktikum vbat",
            'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true),
            'jpg' => "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xD9",
            default => 'invalid',
        };

        $file = UploadedFile::fake()->createWithContent($name, (string) $content);

        $res = $this->actingAs($student)->postJson('/api/v1/lms/uploads', [
            'purpose' => 'assignment_submission',
            'file' => $file,
        ])->assertStatus(202);

        $uploadId = $res->json('data.id');
        if ($type === 'pdf') {
            $this->mock(LmsDocumentInspection::class, fn ($m) => $m->shouldReceive('inspect')->andReturn(2));
        } else {
            $this->mock(LmsDocumentInspection::class, fn ($m) => $m->shouldReceive('inspectImage')->andReturnNull());
        }

        return app(LmsUploadService::class)->scan($uploadId);
    }

    public function test_t10_01_assignment_authoring_rubric_validation_and_syllabus_exposure(): void
    {
        $this->actingAs($this->instructorA);

        // Rubric sum mismatch is rejected with 422
        $this->postJson('/api/v1/admin/lms/assignments', [
            'lesson_id' => $this->lessonA->id,
            'title' => 'Tugas Ukur Tegangan',
            'instructions' => 'Lampirkan hasil ukur jalur VDD_MAIN.',
            'max_score' => 100,
            'rubric' => [
                ['id' => 'c1', 'label' => 'Ketepatan titik ukur', 'max_points' => 40],
                ['id' => 'c2', 'label' => 'Analisis kesimpulan', 'max_points' => 40],
            ],
        ])->assertUnprocessable();

        // Create valid draft assignment
        $created = $this->postJson('/api/v1/admin/lms/assignments', [
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-B10-01',
            'title' => 'Tugas Ukur Tegangan VDD_MAIN',
            'instructions' => 'Unggah foto pengukuran multimeter dan analisis singkat.',
            'status' => 'draft',
            'is_required' => true,
            'max_score' => 100,
            'passing_score' => 75,
            'weight' => 2,
            'requires_text' => true,
            'requires_attachment' => true,
            'due_at' => now()->addDays(2)->toIso8601String(),
            'late_submission_allowed' => true,
            'late_until' => now()->addDays(3)->toIso8601String(),
            'rubric' => [
                ['id' => 'c1', 'label' => 'Ketepatan titik ukur', 'max_points' => 60],
                ['id' => 'c2', 'label' => 'Analisis kesimpulan', 'max_points' => 40],
            ],
        ])->assertCreated()->json('data');

        $assignmentId = $created['id'];
        $this->assertSame('draft', $created['status']);

        // Draft assignment is hidden from student syllabus & detail endpoint (404)
        $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/assignments/'.$assignmentId)
            ->assertNotFound();

        $syllabusDraft = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/courses/'.$this->courseA->id)
            ->assertOk()
            ->json('data');
        $kindsDraft = array_column($syllabusDraft['modules'][0]['activities'], 'kind');
        $this->assertNotContains('assignment', $kindsDraft);

        // Stale revision on publish is rejected with 409
        $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/assignments/'.$assignmentId.'/status', [
                'status' => 'published',
                'expected_revision' => 99,
            ])->assertStatus(409);

        // Publish assignment with matching expected_revision
        $published = $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/assignments/'.$assignmentId.'/status', [
                'status' => 'published',
                'expected_revision' => $created['revision'],
            ])->assertOk()->json('data');
        $this->assertSame('published', $published['status']);

        // Student now sees assignment in syllabus and detail, without internal rubric leak
        $syllabusPub = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/courses/'.$this->courseA->id)
            ->assertOk()
            ->json('data');
        $activities = $syllabusPub['modules'][0]['activities'];
        $asgActivity = collect($activities)->firstWhere('kind', 'assignment');
        $this->assertNotNull($asgActivity);
        $this->assertSame($assignmentId, $asgActivity['id']);
        $this->assertTrue($asgActivity['can_open']);

        $studentDetail = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/assignments/'.$assignmentId)
            ->assertOk()
            ->assertJsonMissingPath('data.rubric')
            ->json('data');
        $this->assertSame('not_started', $studentDetail['student_state']);
        $this->assertStringContainsString('WIB', $studentDetail['due_at_wib']);
    }

    public function test_t10_02_and_t10_03_student_draft_upload_scan_immutable_submit_and_idempotency(): void
    {
        $assignment = LmsAssignment::create([
            'course_id' => $this->courseA->id,
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-B10-02',
            'title' => 'Tugas Praktik Reballing IC',
            'instructions' => 'Kirim laporan teks dan lampiran foto/PDF.',
            'status' => 'published',
            'is_required' => true,
            'max_score' => 100,
            'passing_score' => 70,
            'weight' => 1,
            'requires_text' => true,
            'requires_attachment' => true,
            'max_files' => 2,
            'published_at' => now(),
        ]);

        // Spoofed PDF content is rejected at upload
        $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/uploads', [
                'purpose' => 'assignment_submission',
                'file' => UploadedFile::fake()->createWithContent('evil.pdf', '<script>alert(1)</script>'),
            ])->assertUnprocessable();

        // Upload valid PDF and PNG for studentA
        $pdfUpload = $this->uploadStudentFile($this->studentA, 'laporan-ic.pdf', 'pdf');
        $pngUpload = $this->uploadStudentFile($this->studentA, 'foto-ic.png', 'png');
        $this->assertSame('ready', $pdfUpload->status);
        $this->assertSame('ready', $pngUpload->status);

        // StudentB cannot attach StudentA's upload (403)
        $this->actingAs($this->studentB)
            ->putJson('/api/v1/lms/assignments/'.$assignment->id.'/draft', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Mencoba pakai file siswa lain',
                'upload_ids' => [$pdfUpload->id],
            ])->assertForbidden();

        // Client-spoofed score or user_id is prohibited (422)
        $this->actingAs($this->studentA)
            ->putJson('/api/v1/lms/assignments/'.$assignment->id.'/draft', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Draft',
                'score' => 100,
            ])->assertUnprocessable();

        // Save draft v1
        $draftRes = $this->actingAs($this->studentA)
            ->putJson('/api/v1/lms/assignments/'.$assignment->id.'/draft', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Draft analisis reballing tahap 1.',
                'upload_ids' => [$pdfUpload->id, $pngUpload->id],
            ])->assertOk()->json('data');

        $this->assertSame('draft', $draftRes['student_state']);
        $this->assertSame(1, $draftRes['draft']['version']);
        $this->assertCount(2, $draftRes['draft']['files']);

        // Stale expected_version on draft update is rejected with 409
        $this->actingAs($this->studentA)
            ->putJson('/api/v1/lms/assignments/'.$assignment->id.'/draft', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 99,
                'text_body' => 'Stale update',
                'upload_ids' => [$pdfUpload->id],
            ])->assertStatus(409);

        // Submit assignment with idempotency request_id
        $submitReqId = (string) Str::uuid();
        $submitRes = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$assignment->id.'/submit', [
                'request_id' => $submitReqId,
            ])->assertOk()->json('data');

        $this->assertSame('submitted', $submitRes['student_state']);
        $this->assertFalse($submitRes['can_submit']);
        $this->assertSame(1, $submitRes['latest_submission']['version']);
        $this->assertStringStartsWith('SUB-', $submitRes['latest_submission']['receipt_code']);
        $this->assertSame('pending_review', $submitRes['latest_submission']['grade_status']);
        $this->assertNull($submitRes['latest_submission']['score']);

        // Retrying with same request_id + same payload returns same receipt without creating v2
        $retryRes = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$assignment->id.'/submit', [
                'request_id' => $submitReqId,
            ])->assertOk()->json('data');
        $this->assertSame($submitRes['latest_submission']['receipt_code'], $retryRes['latest_submission']['receipt_code']);
        $this->assertCount(1, $retryRes['submission_history']);

        // Retrying with same request_id + different payload returns 409
        $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$assignment->id.'/submit', [
                'request_id' => $submitReqId,
                'text_body' => 'Muatan berbeda',
            ])->assertStatus(409);

        // Second submission with new request_id is blocked (409) because submission is locked
        $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$assignment->id.'/submit', [
                'request_id' => (string) Str::uuid(),
            ])->assertStatus(409);

        // Editing draft while submitted is also blocked (409)
        $this->actingAs($this->studentA)
            ->putJson('/api/v1/lms/assignments/'.$assignment->id.'/draft', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Coba ubah setelah kirim',
            ])->assertStatus(409);
    }

    public function test_t10_04_deadline_and_late_submission_server_enforcement(): void
    {
        $strictAssignment = LmsAssignment::create([
            'course_id' => $this->courseA->id,
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-STRICT',
            'title' => 'Tugas Tenggat Ketat',
            'instructions' => 'Kirim sebelum tenggat.',
            'status' => 'published',
            'requires_text' => true,
            'due_at' => now()->addHour(),
            'late_submission_allowed' => false,
            'published_at' => now(),
        ]);

        $lateAssignment = LmsAssignment::create([
            'course_id' => $this->courseA->id,
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-LATE',
            'title' => 'Tugas Toleransi Terlambat',
            'instructions' => 'Boleh terlambat sampai batas akhir.',
            'status' => 'published',
            'requires_text' => true,
            'due_at' => now()->addHour(),
            'late_submission_allowed' => true,
            'late_until' => now()->addHours(3),
            'published_at' => now(),
        ]);

        // Advance time 2 hours past due_at (within late_until for lateAssignment)
        $this->travel(2)->hours();

        // Strict assignment rejects late submission with 422
        $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$strictAssignment->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Kirim terlambat pada tugas ketat.',
            ])->assertUnprocessable();

        // Late-allowed assignment accepts submission and marks is_late = true
        $lateSub = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$lateAssignment->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Kirim dalam masa toleransi keterlambatan.',
            ])->assertOk()->json('data.latest_submission');
        $this->assertTrue($lateSub['is_late']);

        // Advance time past late_until (4 hours total) -> Student B is rejected with 422
        $this->travel(2)->hours();
        $this->actingAs($this->studentB)
            ->postJson('/api/v1/lms/assignments/'.$lateAssignment->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Lewat batas late_until.',
            ])->assertUnprocessable();
    }

    public function test_t10_05_reviewer_draft_publish_return_resubmit_and_attachment_privacy(): void
    {
        $assignment = LmsAssignment::create([
            'course_id' => $this->courseA->id,
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-REV-01',
            'title' => 'Tugas Pengukuran Arus Bocor',
            'instructions' => 'Lampirkan PDF laporan arus bocor.',
            'status' => 'published',
            'is_required' => true,
            'max_score' => 100,
            'passing_score' => 75,
            'weight' => 1,
            'requires_text' => true,
            'requires_attachment' => true,
            'published_at' => now(),
        ]);

        $pdf = $this->uploadStudentFile($this->studentA, 'arus-bocor-v1.pdf', 'pdf');
        $subV1 = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$assignment->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Laporan versi 1 (masih kurang grafik).',
                'upload_ids' => [$pdf->id],
            ])->assertOk()->json('data.latest_submission');

        $submissionId = $subV1['id'];

        // Attachment download privacy: owner and instructorA can download; studentB and instructorB get 403
        $this->actingAs($this->studentA)
            ->get('/api/v1/lms/submissions/'.$submissionId.'/files/'.$pdf->id)
            ->assertOk();
        $this->actingAs($this->instructorA)
            ->get('/api/v1/lms/submissions/'.$submissionId.'/files/'.$pdf->id)
            ->assertOk();
        $this->actingAs($this->studentB)
            ->get('/api/v1/lms/submissions/'.$submissionId.'/files/'.$pdf->id)
            ->assertForbidden();
        $this->actingAs($this->instructorB)
            ->get('/api/v1/lms/submissions/'.$submissionId.'/files/'.$pdf->id)
            ->assertForbidden();

        // Reviewer returns submission v1 for revision
        $returned = $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/submissions/'.$submissionId.'/return', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 0,
                'feedback' => 'Mohon lengkapi grafik konsumsi arus sebelum dan sesudah injeksi tegangan.',
                'reason' => 'Lampiran belum memuat grafik.',
                'resubmit_deadline_at' => now()->addDays(1)->toIso8601String(),
            ])->assertOk()->json('data');

        $this->assertSame('returned', $returned['status']);
        $this->assertTrue($returned['resubmit_allowed']);

        // Student sees returned state, feedback, and can_submit = true
        $studentAfterReturn = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/assignments/'.$assignment->id)
            ->assertOk()
            ->json('data');
        $this->assertSame('returned', $studentAfterReturn['student_state']);
        $this->assertTrue($studentAfterReturn['can_submit']);
        $this->assertStringContainsString('grafik konsumsi arus', $studentAfterReturn['latest_submission']['feedback']);

        // Student submits v2
        $pdfV2 = $this->uploadStudentFile($this->studentA, 'arus-bocor-v2.pdf', 'pdf');
        $subV2Data = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$assignment->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Laporan versi 2 lengkap dengan grafik DC power supply.',
                'upload_ids' => [$pdfV2->id],
            ])->assertOk()->json('data');

        $this->assertSame('submitted', $subV2Data['student_state']);
        $this->assertSame(2, $subV2Data['latest_submission']['version']);
        $this->assertCount(2, $subV2Data['submission_history']);
        $this->assertNotSame($subV1['receipt_code'], $subV2Data['latest_submission']['receipt_code']);

        $subV2Id = $subV2Data['latest_submission']['id'];

        // Reviewer saves draft grade on v2 -> student still sees pending_review and null score
        $draftGrade = $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/submissions/'.$subV2Id.'/draft', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 0,
                'score' => 88,
                'feedback' => 'Grafik dan analisis arus bocor sangat tepat.',
                'reason' => 'Simpan draft penilaian.',
            ])->assertOk()->json('data');
        $this->assertSame(1, $draftGrade['grade_version']);
        $this->assertSame('pending_review', $draftGrade['grade_status']);

        $studentWhileDraft = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/assignments/'.$assignment->id)
            ->assertOk()
            ->json('data.latest_submission');
        $this->assertSame('pending_review', $studentWhileDraft['grade_status']);
        $this->assertNull($studentWhileDraft['score']);
        $this->assertNull($studentWhileDraft['feedback']);

        // Stale expected_version on publish fails with 409
        $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/submissions/'.$subV2Id.'/publish', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 0,
                'reason' => 'Versi salah',
            ])->assertStatus(409);

        // Publish draft grade with matching expected_version = 1
        $publishedGrade = $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/submissions/'.$subV2Id.'/publish', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 1,
                'reason' => 'Publikasi nilai akhir v2.',
            ])->assertOk()->json('data');
        $this->assertSame('published', $publishedGrade['grade_status']);
        $this->assertSame(88.0, (float) $publishedGrade['score']);
        $this->assertTrue($publishedGrade['is_passed']);

        // Student now sees published score, feedback, and 100% course completion
        $studentAfterPub = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/assignments/'.$assignment->id)
            ->assertOk()
            ->json('data');
        $this->assertSame('graded', $studentAfterPub['student_state']);
        $this->assertSame(88.0, (float) $studentAfterPub['latest_submission']['score']);
        $this->assertSame('Grafik dan analisis arus bocor sangat tepat.', $studentAfterPub['latest_submission']['feedback']);

        $courseShow = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/courses/'.$this->courseA->id)
            ->assertOk()
            ->json('data.course.progress');
        $this->assertTrue($courseShow['completed']);
        $this->assertSame(100.0, (float) $courseShow['percent']);
    }

    public function test_t10_06_gradebook_aggregation_and_bulk_publish(): void
    {
        $asg1 = LmsAssignment::create([
            'course_id' => $this->courseA->id,
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-GB-1',
            'title' => 'Tugas Bobot 2',
            'instructions' => 'Instruksi 1',
            'status' => 'published',
            'is_required' => true,
            'max_score' => 100,
            'passing_score' => 70,
            'weight' => 2,
            'requires_text' => true,
            'published_at' => now(),
        ]);

        $asg2 = LmsAssignment::create([
            'course_id' => $this->courseA->id,
            'lesson_id' => $this->lessonA->id,
            'code' => 'ASG-GB-2',
            'title' => 'Tugas Bobot 1',
            'instructions' => 'Instruksi 2',
            'status' => 'published',
            'is_required' => false,
            'max_score' => 50,
            'passing_score' => 70,
            'weight' => 1,
            'requires_text' => true,
            'published_at' => now(),
        ]);

        // Student A submits both assignments; Student B submits none
        $subA1 = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$asg1->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Jawaban A1',
            ])->assertOk()->json('data.latest_submission');

        $subA2 = $this->actingAs($this->studentA)
            ->postJson('/api/v1/lms/assignments/'.$asg2->id.'/submit', [
                'request_id' => (string) Str::uuid(),
                'text_body' => 'Jawaban A2',
            ])->assertOk()->json('data.latest_submission');

        // Instructor A saves draft grades for both (90/100 = 90% on weight 2; 30/50 = 60% on weight 1)
        $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/submissions/'.$subA1['id'].'/draft', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 0,
                'score' => 90,
                'feedback' => 'Bagus A1',
                'reason' => 'Draft A1',
            ])->assertOk();

        $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/submissions/'.$subA2['id'].'/draft', [
                'request_id' => (string) Str::uuid(),
                'expected_version' => 0,
                'score' => 30,
                'feedback' => 'Cukup A2',
                'reason' => 'Draft A2',
            ])->assertOk();

        // Before publish: gradebook shows pending_review with has_draft_grade = true, and weighted_score_percent = null (not 0!)
        $gbBefore = $this->actingAs($this->instructorA)
            ->getJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/gradebook')
            ->assertOk()
            ->json('data');

        $rowABefore = collect($gbBefore['rows'])->first(fn ($r) => $r['student']['id'] === $this->studentA->id);
        $rowBBefore = collect($gbBefore['rows'])->first(fn ($r) => $r['student']['id'] === $this->studentB->id);

        $this->assertNull($rowABefore['summary']['weighted_score_percent']);
        $this->assertSame(2, $rowABefore['summary']['pending_count']);
        $this->assertTrue($rowABefore['cells']['assignment:'.$asg1->id]['has_draft_grade']);
        $this->assertSame('not_submitted', $rowBBefore['cells']['assignment:'.$asg1->id]['status']);
        $this->assertNull($rowBBefore['summary']['weighted_score_percent']);

        // Student A's own grades endpoint does NOT leak draft_score or has_draft_grade
        $studentGradesBefore = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/courses/'.$this->courseA->id.'/grades')
            ->assertOk()
            ->assertJsonMissingPath('data.items.0.draft_score')
            ->assertJsonMissingPath('data.items.0.has_draft_grade')
            ->json('data');
        $this->assertNull($studentGradesBefore['summary']['weighted_score_percent']);
        $this->assertNull($studentGradesBefore['items'][0]['score']);

        // Bulk publish both draft grades atomically
        $bulkRes = $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/gradebook/bulk-publish', [
                'request_id' => (string) Str::uuid(),
                'reason' => 'Publikasi serentak nilai modul 1.',
                'items' => [
                    ['kind' => 'assignment', 'id' => $subA1['id'], 'expected_version' => 1],
                    ['kind' => 'assignment', 'id' => $subA2['id'], 'expected_version' => 1],
                ],
            ])->assertOk()->json('data');
        $this->assertSame(2, $bulkRes['published_count']);

        // Weighted average for Student A: (90% * 2 + 60% * 1) / (2 + 1) = 240 / 3 = 80.0%
        $studentGradesAfter = $this->actingAs($this->studentA)
            ->getJson('/api/v1/lms/courses/'.$this->courseA->id.'/grades')
            ->assertOk()
            ->json('data');
        $this->assertSame(80.0, (float) $studentGradesAfter['summary']['weighted_score_percent']);
        $this->assertSame(2, $studentGradesAfter['summary']['published_count']);
    }

    public function test_t10_07_instructor_role_course_scoping_and_revocation(): void
    {
        // Instructor A can manage Course A (create lesson, assignment, view gradebook)
        $this->actingAs($this->instructorA)
            ->getJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/gradebook')
            ->assertOk();

        // Instructor A is forbidden (403) from Course B
        $this->actingAs($this->instructorA)
            ->getJson('/api/v1/admin/lms/courses/'.$this->courseB->id.'/gradebook')
            ->assertForbidden();

        $this->actingAs($this->instructorA)
            ->postJson('/api/v1/admin/lms/assignments', [
                'lesson_id' => $this->lessonB->id,
                'title' => 'Tugas Ilegal di Kelas B',
                'instructions' => 'Tidak boleh tembus.',
            ])->assertForbidden();

        // Instructor A cannot create/delete courses or access super_admin endpoints (403)
        $this->actingAs($this->instructorA)
            ->postJson('/api/v1/courses', ['title' => 'Kelas Baru', 'type' => 'android'])
            ->assertForbidden();
        $this->actingAs($this->instructorA)
            ->deleteJson('/api/v1/courses/'.$this->courseA->id)
            ->assertForbidden();
        $this->actingAs($this->instructorA)
            ->getJson('/api/v1/lms/imports/template')
            ->assertForbidden();
        $this->actingAs($this->instructorA)
            ->putJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/instructors', [
                'instructor_id' => $this->instructorA->id,
            ])->assertForbidden();

        // Super Admin assigns Instructor B as co-instructor on Course A
        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/instructors', [
                'instructor_id' => $this->instructorA->id,
                'co_instructor_ids' => [$this->instructorB->id],
            ])->assertOk();

        // Now Instructor B can access Course A gradebook
        $this->actingAs($this->instructorB)
            ->getJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/gradebook')
            ->assertOk();

        // Livewire ContentCms and GradeReview work for scoped instructor
        $this->actingAs($this->instructorA);
        Livewire::test(ContentCms::class)
            ->call('selectCourse', $this->courseA->id)
            ->assertSet('selectedCourseId', $this->courseA->id);

        Livewire::test(GradeReview::class)
            ->set('mode', 'gradebook')
            ->set('gradebookCourseId', $this->courseA->id)
            ->assertSee('Kelas Diagnosa iPhone B10');

        // Super Admin revokes Instructor B from Course A -> immediate 403
        $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/instructors', [
                'instructor_id' => $this->instructorA->id,
                'co_instructor_ids' => [],
            ])->assertOk();

        $this->actingAs($this->instructorB)
            ->getJson('/api/v1/admin/lms/courses/'.$this->courseA->id.'/gradebook')
            ->assertForbidden();
    }
}
