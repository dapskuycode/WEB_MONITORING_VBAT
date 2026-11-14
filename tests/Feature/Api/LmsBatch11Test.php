<?php

namespace Tests\Feature\Api;

use App\Jobs\DispatchLmsNotificationJob;
use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsAssignment;
use App\Models\Notification;
use App\Models\User;
use App\Services\LmsDocumentInspection;
use App\Services\LmsNotificationService;
use App\Services\LmsRedactionService;
use App\Services\LmsUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LmsBatch11Test extends TestCase
{
    use RefreshDatabase;

    private const TINY_PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 2/Kids[3 0 R 4 0 R]>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n4 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('lms');
        Storage::fake('public');
    }

    private function createPublishedPdfCourse(User $admin): array
    {
        $course = Course::create([
            'code' => 'CRS-B11',
            'title' => 'Kelas Diagnostik B11',
            'slug' => 'kelas-diagnostik-b11',
            'type' => Course::TYPE_ANDROID,
            'status' => 'published',
            'sort_order' => 1,
            'revision' => 1,
            'published_at' => now(),
        ]);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'code' => 'MOD-B11-1',
            'title' => 'Modul 1 B11',
            'status' => 'published',
            'sort_order' => 1,
            'revision' => 1,
        ]);

        $pdfFile = UploadedFile::fake()->createWithContent('modul-b11.pdf', self::TINY_PDF);
        $uploadRes = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/lms/uploads', [
            'purpose' => 'course_material',
            'file' => $pdfFile,
        ])->assertStatus(202);
        $uploadId = $uploadRes->json('data.id');
        $this->mock(LmsDocumentInspection::class, fn ($m) => $m->shouldReceive('inspect')->andReturn(2));
        app(LmsUploadService::class)->scan($uploadId);

        $uploadRow = DB::table('lms_uploads')->where('id', $uploadId)->first();

        $pdfMaterial = LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id,
            'unit_code' => 'MAT-PDF-B11',
            'unit_title' => 'Unit 1 PDF',
            'title' => 'E-Book Skema Jalur B11',
            'material_type' => 'pdf_document',
            'pdf_upload_id' => $uploadId,
            'pdf_path' => $uploadRow->path,
            'sort_order' => 1,
            'is_required' => true,
            'status' => 'published',
            'revision' => 1,
        ]);

        $videoMaterial = LearningMaterial::factory()->create([
            'lesson_id' => $lesson->id,
            'unit_code' => 'MAT-VID-B11',
            'unit_title' => 'Unit 2 Video',
            'title' => 'Video YouTube B11',
            'material_type' => 'youtube_video',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'duration_seconds' => 120,
            'sort_order' => 2,
            'is_required' => true,
            'status' => 'published',
            'revision' => 1,
        ]);

        return [$course, $lesson, $pdfMaterial, $videoMaterial, $uploadRow];
    }

    public function test_t11_01_and_t11_02_offline_license_download_device_limit_expiry_and_revocation(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $studentA = User::factory()->create(['role' => 'teknisi', 'name' => 'Student A']);
        $studentB = User::factory()->create(['role' => 'teknisi', 'name' => 'Student B']);

        [$course, $lesson, $pdfMaterial, $videoMaterial, $uploadRow] = $this->createPublishedPdfCourse($admin);

        $entitlementExpiry = now()->addDays(3);
        $entitlement = Entitlement::create([
            'user_id' => $studentA->id,
            'package_type' => Course::TYPE_ANDROID,
            'status' => 'active',
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'expires_at' => $entitlementExpiry,
        ]);

        // 1. YouTube video cannot be licensed for offline download
        $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/materials/{$videoMaterial->id}/offline-license", [
            'request_id' => (string) Str::uuid(),
            'device_id' => 'device-a1',
            'device_name' => 'Ponsel A1',
            'platform' => 'android',
        ])->assertStatus(422);

        // 2. Issue license for eligible PDF material on device-a1 (idempotent on request_id)
        $reqId = (string) Str::uuid();
        $issue1 = $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/materials/{$pdfMaterial->id}/offline-license", [
            'request_id' => $reqId,
            'device_id' => 'device-a1',
            'device_name' => 'Ponsel A1',
            'platform' => 'android',
        ])->assertStatus(201);

        $licenseId = $issue1->json('data.license_id');
        $this->assertSame($uploadRow->sha256, $issue1->json('data.source_hash'));
        $this->assertSame((int) $uploadRow->size_bytes, $issue1->json('data.size_bytes'));

        // Replay with same request_id returns identical license_id
        $replay = $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/materials/{$pdfMaterial->id}/offline-license", [
            'request_id' => $reqId,
            'device_id' => 'device-a1',
        ])->assertStatus(201);
        $this->assertSame($licenseId, $replay->json('data.license_id'));

        // 3. Download protected file with matching device_id and verify SHA-256 checksum
        $download = $this->actingAs($studentA, 'sanctum')
            ->withHeader('X-LMS-Device-Id', 'device-a1')
            ->get("/api/v1/lms/offline/licenses/{$licenseId}/file")
            ->assertOk();
        $this->assertSame($uploadRow->sha256, $download->headers->get('X-LMS-SHA256'));
        $this->assertSame($uploadRow->sha256, hash('sha256', $download->streamedContent()));

        // Wrong device or wrong account cannot download file
        $this->actingAs($studentA, 'sanctum')
            ->withHeader('X-LMS-Device-Id', 'device-other')
            ->get("/api/v1/lms/offline/licenses/{$licenseId}/file")
            ->assertStatus(403);

        $this->actingAs($studentB, 'sanctum')
            ->withHeader('X-LMS-Device-Id', 'device-a1')
            ->get("/api/v1/lms/offline/licenses/{$licenseId}/file")
            ->assertStatus(404);

        // 4. Device limit enforcement: 2nd device succeeds, 3rd device rejected (409)
        $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/materials/{$pdfMaterial->id}/offline-license", [
            'request_id' => (string) Str::uuid(),
            'device_id' => 'device-a2',
            'device_name' => 'Tablet A2',
            'platform' => 'android',
        ])->assertStatus(201);

        $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/materials/{$pdfMaterial->id}/offline-license", [
            'request_id' => (string) Str::uuid(),
            'device_id' => 'device-a3',
            'device_name' => 'Ponsel A3',
            'platform' => 'ios',
        ])->assertStatus(409);

        // 5. Revoke device-a2 -> active licenses on device-a2 become revoked
        $this->actingAs($studentA, 'sanctum')
            ->postJson('/api/v1/lms/offline/devices/device-a2/revoke')
            ->assertOk()
            ->assertJsonPath('data.revoked_licenses', 1);

        // 6. Renew license on device-a1 while entitlement is active -> succeeds
        $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/offline/licenses/{$licenseId}/renew", [
            'device_id' => 'device-a1',
        ])->assertOk()->assertJsonPath('data.status', 'active');

        // 7. Revoke entitlement -> renewal and download are blocked immediately
        $entitlement->update(['status' => 'revoked']);
        $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/offline/licenses/{$licenseId}/renew", [
            'device_id' => 'device-a1',
        ])->assertStatus(403);

        $this->actingAs($studentA, 'sanctum')
            ->withHeader('X-LMS-Device-Id', 'device-a1')
            ->get("/api/v1/lms/offline/licenses/{$licenseId}/file")
            ->assertStatus(403);
    }

    public function test_t11_03_offline_sync_events_ordering_replay_skew_age_revision_and_prerequisite_unlock(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $student = User::factory()->create(['role' => 'teknisi', 'name' => 'Student']);

        [$course, $lesson, $pdfMaterial, $videoMaterial] = $this->createPublishedPdfCourse($admin);

        Entitlement::create([
            'user_id' => $student->id,
            'package_type' => Course::TYPE_ANDROID,
            'status' => 'active',
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'expires_at' => now()->addDays(10),
        ]);

        $issue = $this->actingAs($student, 'sanctum')->postJson("/api/v1/lms/materials/{$pdfMaterial->id}/offline-license", [
            'request_id' => (string) Str::uuid(),
            'device_id' => 'device-sync-1',
            'platform' => 'android',
        ])->assertStatus(201);
        $licenseId = $issue->json('data.license_id');

        $ev1Id = (string) Str::uuid();
        $ev2Id = (string) Str::uuid();
        $evOldId = (string) Str::uuid();
        $evFutureId = (string) Str::uuid();
        $evQuizSubmitId = (string) Str::uuid();
        $occurred1 = now()->subMinutes(10)->toIso8601String();
        $occurred2 = now()->subMinutes(5)->toIso8601String();

        $syncRes = $this->actingAs($student, 'sanctum')->postJson('/api/v1/lms/offline/sync', [
            'device_id' => 'device-sync-1',
            'client_time' => now()->toIso8601String(),
            'events' => [
                [
                    'event_id' => $ev1Id,
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 1,
                    'event_type' => 'reading_progress',
                    'page' => 1,
                    'confirm_read' => false,
                    'occurred_at' => $occurred1,
                ],
                [
                    'event_id' => $ev2Id,
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 2,
                    'event_type' => 'reading_progress',
                    'page' => 2,
                    'confirm_read' => true,
                    'occurred_at' => $occurred2,
                ],
                [
                    'event_id' => (string) Str::uuid(),
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 1,
                    'event_type' => 'reading_progress',
                    'page' => 1,
                    'confirm_read' => false,
                    'occurred_at' => now()->subMinutes(3)->toIso8601String(),
                ],
                [
                    'event_id' => $evOldId,
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 3,
                    'event_type' => 'reading_progress',
                    'page' => 2,
                    'confirm_read' => false,
                    'occurred_at' => now()->subHours(26)->toIso8601String(),
                ],
                [
                    'event_id' => $evFutureId,
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 4,
                    'event_type' => 'reading_progress',
                    'page' => 2,
                    'confirm_read' => false,
                    'occurred_at' => now()->addMinutes(12)->toIso8601String(),
                ],
                [
                    'event_id' => $evQuizSubmitId,
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 5,
                    'event_type' => 'quiz_submit',
                    'page' => 1,
                    'confirm_read' => false,
                    'occurred_at' => now()->subMinute()->toIso8601String(),
                ],
            ],
        ])->assertOk();

        $this->assertSame(2, $syncRes->json('data.accepted_count'));
        $this->assertSame(4, $syncRes->json('data.rejected_count'));

        $resultsById = collect($syncRes->json('data.results'))->keyBy('event_id');
        $this->assertSame('accepted', $resultsById[$ev1Id]['status']);
        $this->assertSame('accepted', $resultsById[$ev2Id]['status']);
        $this->assertTrue($resultsById[$ev2Id]['progress']['completed']);
        $this->assertSame('event_too_old', $resultsById[$evOldId]['reason']);
        $this->assertSame('clock_skew_exceeded', $resultsById[$evFutureId]['reason']);
        $this->assertSame('offline_assessment_not_allowed', $resultsById[$evQuizSubmitId]['reason']);

        // Replay of ev1Id with identical payload is accepted idempotently
        $replayRes = $this->actingAs($student, 'sanctum')->postJson('/api/v1/lms/offline/sync', [
            'device_id' => 'device-sync-1',
            'events' => [
                [
                    'event_id' => $ev1Id,
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 1,
                    'event_type' => 'reading_progress',
                    'page' => 1,
                    'confirm_read' => false,
                    'occurred_at' => $occurred1,
                ],
            ],
        ])->assertOk();
        $this->assertTrue($replayRes->json('data.results.0.idempotent_replay'));

        // Verify second material in syllabus (videoMaterial) is now unlocked because pdfMaterial completed via offline sync!
        $syllabus = $this->actingAs($student, 'sanctum')
            ->getJson("/api/v1/lms/courses/{$course->id}")
            ->assertOk();
        $activities = collect($syllabus->json('data.modules.0.activities'))->keyBy('id');
        $this->assertTrue($activities[$pdfMaterial->id]['progress']['completed']);
        $this->assertTrue($activities[$videoMaterial->id]['can_open']);

        // Bump material revision -> subsequent offline event on old license is rejected with content_revision_changed
        $pdfMaterial->update(['revision' => 2]);
        $revChangeRes = $this->actingAs($student, 'sanctum')->postJson('/api/v1/lms/offline/sync', [
            'device_id' => 'device-sync-1',
            'events' => [
                [
                    'event_id' => (string) Str::uuid(),
                    'license_id' => $licenseId,
                    'material_id' => $pdfMaterial->id,
                    'sequence' => 3,
                    'event_type' => 'reading_progress',
                    'page' => 1,
                    'confirm_read' => false,
                    'occurred_at' => now()->toIso8601String(),
                ],
            ],
        ])->assertOk();
        $this->assertSame('content_revision_changed', $revChangeRes->json('data.results.0.reason'));
    }

    public function test_t11_04_and_t11_05_notifications_dedup_privacy_deeplink_and_ops_scheduler(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $studentA = User::factory()->create(['role' => 'teknisi', 'name' => 'Student A']);
        $studentB = User::factory()->create(['role' => 'teknisi', 'name' => 'Student B']);

        [$course, $lesson] = $this->createPublishedPdfCourse($admin);

        $entitlementA = Entitlement::create([
            'user_id' => $studentA->id,
            'package_type' => Course::TYPE_ANDROID,
            'status' => 'active',
            'source' => 'manual',
            'starts_at' => now()->subHour(),
            'expires_at' => now()->addDays(7),
        ]);

        // Register valid + invalid push tokens for Student A
        $this->actingAs($studentA, 'sanctum')->postJson('/api/v1/lms/notifications/push-token', [
            'device_id' => 'dev-valid',
            'push_token' => 'fcm-token-student-a-valid-12345',
            'platform' => 'android',
        ])->assertOk()->assertJsonPath('data.status', 'active');

        $this->actingAs($studentA, 'sanctum')->postJson('/api/v1/lms/notifications/push-token', [
            'device_id' => 'dev-invalid',
            'push_token' => 'invalid:token-expired-999',
            'platform' => 'ios',
        ])->assertOk()->assertJsonPath('data.status', 'invalid');

        // Create published assignment with deadline in 12 hours (eligible for H-1 reminder)
        $assignment = LmsAssignment::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'code' => 'ASG-B11-1',
            'title' => 'Tugas Pengukuran Arus B11',
            'instructions' => 'Ukur arus bocor dan lampirkan laporan.',
            'status' => 'published',
            'is_required' => true,
            'max_score' => 100,
            'passing_score' => 70,
            'weight' => 1,
            'allowed_mimes' => ['application/pdf'],
            'max_files' => 3,
            'max_file_bytes' => 10485760,
            'requires_text' => true,
            'requires_attachment' => false,
            'due_at' => now()->addHours(12),
            'late_submission_allowed' => true,
            'sort_order' => 1,
            'revision' => 1,
        ]);

        // 1. Run H-1 deadline reminder command twice -> deduplicated (only 1 notification created for Student A, 0 for Student B)
        Artisan::call('lms:send-assignment-reminders');
        Artisan::call('lms:send-assignment-reminders');

        $this->assertSame(1, Notification::where('recipient_id', $studentA->id)->where('type', 'assignment_deadline_reminder')->count());
        $this->assertSame(0, Notification::where('recipient_id', $studentB->id)->count());

        // 2. Student A submits assignment -> Admin returns for revision -> Admin publishes grade
        LearningMaterial::where('lesson_id', $lesson->id)->update(['is_required' => false]);

        $submitRes = $this->actingAs($studentA, 'sanctum')->postJson("/api/v1/lms/assignments/{$assignment->id}/submit", [
            'request_id' => (string) Str::uuid(),
            'text_body' => 'Jawaban rahasia siswa: arus bocor 120mA pada VBAT.',
            'upload_ids' => [],
        ])->assertOk();
        $submissionId = $submitRes->json('data.latest_submission.id');

        // Admin returns assignment for revision -> triggers assignment_returned notification
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/lms/submissions/{$submissionId}/return", [
            'request_id' => (string) Str::uuid(),
            'expected_version' => 0,
            'feedback' => 'Tambahkan foto osiloskop.',
            'reason' => 'Perlu bukti foto.',
        ])->assertOk();

        // Admin publishes grade on returned submission (expected_version = 1) -> triggers assignment_grade_published notification
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/admin/lms/submissions/{$submissionId}/publish", [
            'request_id' => (string) Str::uuid(),
            'expected_version' => 1,
            'score' => 88,
            'feedback' => 'Analisis sudah lengkap dan tepat.',
            'reason' => 'Penilaian final.',
        ])->assertOk();

        // Verify notifications in inbox do NOT leak score (88) or student answer ('120mA')
        $inboxRes = $this->actingAs($studentA, 'sanctum')
            ->withHeader('X-Request-Id', 'req-b11-inbox-check')
            ->getJson('/api/v1/user/notifications')
            ->assertOk()
            ->assertHeader('X-Request-Id', 'req-b11-inbox-check');

        $items = collect($inboxRes->json('data'));
        $this->assertGreaterThanOrEqual(3, $items->count());
        foreach ($items as $item) {
            $this->assertStringNotContainsString('88', (string) $item['body']);
            $this->assertStringNotContainsString('120mA', (string) $item['body']);
            $this->assertArrayNotHasKey('score', $item['data'] ?? []);
            $this->assertArrayNotHasKey('text_body', $item['data'] ?? []);
        }

        // Verify retry of DispatchLmsNotificationJob does NOT duplicate notification
        $dispatchRow = DB::table('lms_notification_dispatches')
            ->where('user_id', $studentA->id)
            ->where('event_type', 'assignment_grade_published')
            ->first();
        $this->assertNotNull($dispatchRow);
        $this->assertSame('sent', $dispatchRow->status);
        $this->assertStringStartsWith('PUSH-', (string) $dispatchRow->provider_receipt);

        $beforeCount = Notification::where('recipient_id', $studentA->id)->count();
        (new DispatchLmsNotificationJob((int) $dispatchRow->id))->handle(app(LmsNotificationService::class));
        $this->assertSame($beforeCount, Notification::where('recipient_id', $studentA->id)->count());

        // 3. Deep link resolution checks server identity & active course entitlement
        $this->actingAs($studentA, 'sanctum')->postJson('/api/v1/lms/notifications/resolve-deep-link', [
            'deep_link' => "/assignment?id={$assignment->id}&courseId={$course->id}",
        ])->assertOk()->assertJsonPath('data.allowed', true);

        // Student B (no entitlement) cannot resolve deep link
        $this->actingAs($studentB, 'sanctum')->postJson('/api/v1/lms/notifications/resolve-deep-link', [
            'deep_link' => "/assignment?id={$assignment->id}&courseId={$course->id}",
        ])->assertStatus(403);

        // Revoking Student A's entitlement blocks deep link resolution
        $entitlementA->update(['status' => 'revoked']);
        $this->actingAs($studentA, 'sanctum')->postJson('/api/v1/lms/notifications/resolve-deep-link', [
            'deep_link' => "/assignment?id={$assignment->id}&courseId={$course->id}",
        ])->assertStatus(403);

        // 4. Verify LmsRedactionService strips tokens, answers, text_body, and signed URLs
        $redactor = app(LmsRedactionService::class);
        $redacted = $redactor->redactArray([
            'authorization' => 'Bearer secret-token-12345',
            'answers' => ['101' => 'a'],
            'text_body' => 'Jawaban rahasia',
            'url' => 'https://vbat.test/api/v1/media/serve/pdf/9?expires=123&signature=abcdef123456',
        ]);
        $this->assertSame('[REDACTED]', $redacted['authorization']);
        $this->assertSame('[REDACTED]', $redacted['answers']);
        $this->assertSame('[REDACTED]', $redacted['text_body']);
        $this->assertStringContainsString('signature=[REDACTED]', $redacted['url']);

        // 5. Verify operational commands run cleanly
        $this->assertSame(0, Artisan::call('lms:cleanup-offline-licenses'));
        $this->assertSame(0, Artisan::call('lms:ops-status'));
    }
}
