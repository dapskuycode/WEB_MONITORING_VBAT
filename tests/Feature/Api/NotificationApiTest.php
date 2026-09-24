<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_user_can_list_own_notifications(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Notification::create([
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'type' => 'payment_status',
            'title' => 'Payment Confirmed',
            'body' => 'Your payment was successful',
            'deep_link' => '/orders/1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/user/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data', 'meta' => ['unread_count', 'total']]);
        $response->assertJsonPath('meta.unread_count', 1);
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $notification = Notification::create([
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'type' => 'payment_status',
            'title' => 'Payment Confirmed',
            'body' => 'Your payment was successful',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/user/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    public function test_user_cannot_mark_other_user_notification_as_read(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $otherUser = User::factory()->create(['role' => 'student']);
        $notification = Notification::create([
            'recipient_type' => 'user',
            'recipient_id' => $otherUser->id,
            'type' => 'payment_status',
            'title' => 'Payment Confirmed',
            'body' => 'Your payment was successful',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/user/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Notification::create([
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'type' => 'payment_status',
            'title' => 'Payment Confirmed',
            'body' => 'Your payment was successful',
            'created_at' => now(),
        ]);
        Notification::create([
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'type' => 'hs_unlock',
            'title' => 'Hardware Solution Unlocked',
            'body' => 'You unlocked a new solution',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/user/notifications/read-all');

        $response->assertStatus(200);
        $response->assertJsonPath('data.count', 2);
        $this->assertDatabaseMissing('notifications', [
            'recipient_type' => 'user',
            'recipient_id' => $user->id,
            'is_read' => false,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_user_notifications(): void
    {
        $response = $this->getJson('/api/v1/user/notifications');
        $response->assertStatus(401);
    }

    public function test_notifications_are_idempotent(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $service = app(\App\Services\NotificationService::class);

        $service->createIdempotent(
            recipientType: 'user',
            recipientId: $user->id,
            type: 'payment_status',
            title: 'Payment Confirmed',
            body: 'Your payment was successful',
        );

        $service->createIdempotent(
            recipientType: 'user',
            recipientId: $user->id,
            type: 'payment_status',
            title: 'Payment Confirmed',
            body: 'Your payment was successful',
        );

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_admin_can_receive_admin_notification(): void
    {
        $admin = $this->adminUser();

        Notification::create([
            'recipient_type' => 'admin',
            'recipient_id' => $admin->id,
            'type' => 'sponsor_upload',
            'title' => 'New Sponsor Upload',
            'body' => 'A sponsor uploaded a new product',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }
}
