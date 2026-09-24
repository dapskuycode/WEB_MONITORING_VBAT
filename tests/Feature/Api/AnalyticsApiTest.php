<?php

namespace Tests\Feature\Api;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    public function test_unauthenticated_user_cannot_ingest_events_via_admin_dashboard(): void
    {
        $response = $this->getJson('/api/v1/admin/analytics/dashboard?from=2026-01-01&to=2026-12-31');
        $response->assertStatus(401);
    }

    public function test_non_admin_user_cannot_access_analytics_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/analytics/dashboard?from=2026-01-01&to=2026-12-31');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_analytics_dashboard(): void
    {
        $admin = $this->adminUser();

        AnalyticsEvent::create([
            'event_type' => 'product_view',
            'actor_id' => $admin->id,
            'session_id' => (string) Str::uuid(),
            'target_type' => 'sponsor_product',
            'target_id' => 1,
            'context' => ['placement' => 'feed_shop'],
            'created_at' => now()->subDay(),
        ]);

        AnalyticsEvent::create([
            'event_type' => 'impression',
            'actor_id' => null,
            'session_id' => (string) Str::uuid(),
            'target_type' => 'campaign',
            'target_id' => 1,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/analytics/dashboard?from='.now()->subDays(7)->toDateString().'&to='.now()->addDay()->toDateString());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'total_events',
                'unique_sessions',
                'unique_actors',
                'events_by_type',
                'product_analytics',
                'learning_analytics',
                'campaign_analytics',
            ],
        ]);
        $response->assertJsonPath('data.total_events', 2);
        $response->assertJsonPath('data.unique_sessions', 2);
    }

    public function test_analytics_dashboard_validates_date_range(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/analytics/dashboard?from=invalid&to=2026-12-31');

        $response->assertStatus(422);
    }

    public function test_unauthenticated_event_ingestion(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'events' => [
                ['event_type' => 'product_view', 'target_type' => 'sponsor_product', 'target_id' => 1],
            ],
        ]);

        // Events endpoint should be accessible without auth (anonymous tracking)
        $response->assertStatus(201);
        $response->assertJsonPath('data.count', 1);
        $this->assertDatabaseCount('analytics_events', 1);
    }

    public function test_authenticated_event_ingestion_logs_actor_id(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/events', [
                'events' => [
                    ['event_type' => 'product_view', 'target_type' => 'sponsor_product', 'target_id' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('analytics_events', [
            'event_type' => 'product_view',
            'actor_id' => $user->id,
        ]);
    }

    public function test_batch_ingestion_logs_multiple_events(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'events' => [
                ['event_type' => 'page_view'],
                ['event_type' => 'product_click', 'target_type' => 'sponsor_product', 'target_id' => 5],
                ['event_type' => 'wishlist_add', 'target_type' => 'sponsor_product', 'target_id' => 7],
                ['event_type' => 'invalid_event_type'], // Should be silently dropped
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.count', 3);
        $this->assertDatabaseCount('analytics_events', 3);
    }

    public function test_analytics_validates_request(): void
    {
        $response = $this->postJson('/api/v1/events', [
            'events' => [], // empty
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_export_analytics_csv(): void
    {
        $admin = $this->adminUser();

        AnalyticsEvent::create([
            'event_type' => 'product_view',
            'session_id' => (string) Str::uuid(),
            'target_type' => 'sponsor_product',
            'target_id' => 1,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/analytics/export?from='.now()->subDays(7)->toDateString().'&to='.now()->addDay()->toDateString());

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data' => ['download_url', 'filename']]);
    }

    public function test_non_admin_cannot_export_analytics(): void
    {
        $user = User::factory()->create(['role' => 'sponsor']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/analytics/export?from=2026-01-01&to=2026-12-31');

        $response->assertStatus(403);
    }
}