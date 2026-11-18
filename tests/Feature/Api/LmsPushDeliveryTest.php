<?php

namespace Tests\Feature\Api;

use App\Jobs\DispatchLmsNotificationJob;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmAccessToken;
use App\Services\LmsDocumentInspection;
use App\Services\LmsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LmsPushDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Http::preventStrayRequests();
        config(['lms.fcm_project_id' => 'vbat-qa-fixture']);
        $this->mock(FcmAccessToken::class, fn ($m) => $m->shouldReceive('get')->andReturn('fixture-access-token'));
    }

    private function dispatchFor(User $u): array
    {
        return app(LmsNotificationService::class)->enqueueEvent($u->id, 'qa-'.uniqid(), 'quiz_grade_published', 'Hasil belajar tersedia', 'Buka aplikasi untuk melihat hasil.', '/learning-history');
    }

    private function token(User $u, string $token): void
    {
        app(LmsNotificationService::class)->registerDeviceToken($u, ['device_id' => 'device-'.$token, 'push_token' => $token, 'platform' => 'android']);
    }

    public function test_fcm_receipt_comes_from_provider_and_retry_does_not_resend_successful_device(): void
    {
        $u = User::factory()->create();
        $this->token($u, 'device-token-first');
        $this->token($u, 'device-token-second');
        $second = 0;
        Http::fake(['fcm.googleapis.com/*' => function ($r) use (&$second) {
            if ($r['message']['token'] === 'device-token-second' && ++$second === 1) {
                return Http::response(['error' => ['status' => 'UNAVAILABLE']], 503);
            }

            return Http::response(['name' => 'projects/vbat-qa-fixture/messages/'.$r['message']['token']]);
        }]);
        $id = $this->dispatchFor($u)['id'];
        $s = app(LmsNotificationService::class);
        $this->assertSame('failed', $s->processDispatch($id)['status']);
        $this->assertSame('sent', $s->processDispatch($id)['status']);
        Http::assertSentCount(3);
        $this->assertSame(1, Notification::where('recipient_id', $u->id)->count());
        $this->assertDatabaseCount('lms_push_deliveries', 2);
        $this->assertStringContainsString('projects/vbat-qa-fixture/messages/', $s->processDispatch($id)['provider_receipt']);
        Http::assertSentCount(3);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer fixture-access-token') && ! isset($r['message']['data']['score']));
    }

    public function test_missing_configuration_never_fakes_push_success_and_job_retries(): void
    {
        $u = User::factory()->create();
        $this->token($u, 'device-token-config');
        config(['lms.fcm_project_id' => null]);
        $id = $this->dispatchFor($u)['id'];
        $s = app(LmsNotificationService::class);
        $this->assertSame('failed', $s->processDispatch($id)['status']);
        $this->assertNull(DB::table('lms_notification_dispatches')->where('id', $id)->value('provider_receipt'));
        $this->assertSame(1, Notification::where('recipient_id', $u->id)->count());
        Http::assertNothingSent();
        $this->expectException(\RuntimeException::class);
        (new DispatchLmsNotificationJob($id))->handle($s);
    }

    public function test_unregistered_token_is_disabled_and_account_switch_revokes_old_owner(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->token($a, 'device-token-shared');
        $this->token($b, 'device-token-shared');
        $this->assertDatabaseHas('lms_device_push_tokens', ['user_id' => $a->id, 'status' => 'revoked']);
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => ['details' => [['@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED']]]], 404)]);
        $id = $this->dispatchFor($b)['id'];
        app(LmsNotificationService::class)->processDispatch($id);
        $this->assertDatabaseHas('lms_device_push_tokens', ['user_id' => $b->id, 'status' => 'invalid']);
        $this->assertDatabaseHas('lms_push_deliveries', ['dispatch_id' => $id, 'status' => 'invalid', 'provider_receipt' => null]);
    }

    public function test_inbox_without_devices_has_no_provider_receipt(): void
    {
        $u = User::factory()->create();
        $id = $this->dispatchFor($u)['id'];
        $d = app(LmsNotificationService::class)->processDispatch($id);
        $this->assertSame('sent', $d['status']);
        $this->assertNull($d['provider_receipt']);
        $this->assertDatabaseHas('lms_notification_dispatches', ['id' => $id, 'channel' => 'in_app']);
        Http::assertNothingSent();
    }

    public function test_empty_scanner_is_fail_closed_by_default(): void
    {
        config(['lms.clamscan_binary' => '', 'lms.allow_unscanned_uploads' => false]);
        $this->expectExceptionMessage('scanner_unavailable');
        app(LmsDocumentInspection::class)->inspectImage('/unused-fixture');
    }

    public function test_worker_lease_prevents_concurrent_send_and_recovers_after_expiry(): void
    {
        $u = User::factory()->create();
        $this->token($u, 'device-token-lease');
        $id = $this->dispatchFor($u)['id'];
        DB::table('lms_notification_dispatches')->where('id', $id)->update(['processing_token' => '11111111-1111-4111-8111-111111111111', 'processing_until' => now()->addMinute()]);
        try {
            app(LmsNotificationService::class)->processDispatch($id);
            $this->fail('Concurrent worker should wait.');
        } catch (\RuntimeException $e) {
            $this->assertSame('push_dispatch_in_progress', $e->getMessage());
        }
        Http::assertNothingSent();
        DB::table('lms_notification_dispatches')->where('id', $id)->update(['processing_until' => now()->subSecond()]);
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/vbat-qa-fixture/messages/recovered'])]);
        $this->assertSame('sent', app(LmsNotificationService::class)->processDispatch($id)['status']);
        $this->assertNull(DB::table('lms_notification_dispatches')->where('id', $id)->value('processing_token'));
    }
}
