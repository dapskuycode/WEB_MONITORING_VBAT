<?php

namespace Tests\Feature\Api;

use App\Models\BenefitCategory;
use App\Models\AdminAuditLog;
use App\Models\AdminNotification;
use App\Models\BestDeal;
use App\Models\Campaign;
use App\Models\Sponsor;
use App\Models\SponsorBenefitOverride;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create(['role' => 'super_admin']);
        Sanctum::actingAs($user);

        return $user;
    }

    private function actingAsSponsor(): User
    {
        $user = User::factory()->create(['role' => 'sponsor']);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createBenefitCategory(): BenefitCategory
    {
        return BenefitCategory::create([
            'slug' => 'logo-display-' . uniqid(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'value_type' => 'boolean',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function createSponsorTier(): SponsorTier
    {
        return SponsorTier::create([
            'slug' => 'tier-' . uniqid(),
            'name' => fake()->company(),
            'badge_label' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ]);
    }

    public function test_admin_can_list_notifications(): void
    {
        $this->actingAsAdmin();
        AdminNotification::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/admin/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', fn ($data) => count($data) === 3)
            ->assertJsonPath('meta.unread_count', 3);
    }

    public function test_admin_can_filter_unread_notifications(): void
    {
        $this->actingAsAdmin();
        AdminNotification::factory()->create(['is_read' => true, 'read_at' => now()]);
        AdminNotification::factory()->create(['is_read' => false]);

        $response = $this->getJson('/api/v1/admin/notifications?unread_only=1');

        $response->assertOk()
            ->assertJsonPath('data', fn ($data) => count($data) === 1)
            ->assertJsonPath('data.0.is_read', false);
    }

    public function test_admin_can_mark_notification_read(): void
    {
        $this->actingAsAdmin();
        $notification = AdminNotification::factory()->create(['is_read' => false]);

        $response = $this->postJson("/api/v1/admin/notifications/{$notification->id}/read");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_read', true);

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_sponsor_cannot_access_admin_notifications(): void
    {
        $this->actingAsSponsor();

        $this->getJson('/api/v1/admin/notifications')
            ->assertForbidden();
    }

    public function test_admin_can_override_sponsor_benefit(): void
    {
        $admin = $this->actingAsAdmin();
        $sponsor = Sponsor::factory()->create();
        $category = $this->createBenefitCategory();

        $response = $this->postJson("/api/v1/admin/sponsors/{$sponsor->id}/benefit-overrides", [
            'benefit_category_id' => $category->id,
            'value' => 'custom_value',
            'label' => 'Custom Label',
            'reason' => 'Override reason',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.value', 'custom_value')
            ->assertJsonPath('data.label', 'Custom Label');

        $this->assertDatabaseHas('sponsor_benefit_overrides', [
            'sponsor_id' => $sponsor->id,
            'benefit_category_id' => $category->id,
            'overridden_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'benefit.overridden',
            'actor_id' => $admin->id,
            'target_type' => 'sponsor',
            'target_id' => $sponsor->id,
        ]);

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'benefit.overridden',
            'target_type' => 'sponsor',
            'target_id' => $sponsor->id,
        ]);
    }

    public function test_admin_can_change_sponsor_tier(): void
    {
        $admin = $this->actingAsAdmin();
        $currentTier = $this->createSponsorTier();
        $newTier = $this->createSponsorTier();
        $sponsor = Sponsor::factory()->create(['tier_id' => $currentTier->id]);

        $response = $this->putJson("/api/v1/admin/sponsors/{$sponsor->id}/tier", [
            'tier_id' => $newTier->id,
            'reason' => 'Upgrade to gold',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sponsor_tier.id', $newTier->id);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'sponsor.tier_changed',
            'actor_id' => $admin->id,
            'target_type' => 'sponsor',
            'target_id' => $sponsor->id,
        ]);
    }

    public function test_admin_can_override_campaign_status(): void
    {
        $admin = $this->actingAsAdmin();
        $campaign = Campaign::factory()->create(['status' => 'active']);

        $response = $this->putJson("/api/v1/admin/campaigns/{$campaign->id}", [
            'status' => 'paused',
            'reason' => 'Pending review',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'paused');

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'campaign.paused',
            'actor_id' => $admin->id,
            'target_type' => 'campaign',
            'target_id' => $campaign->id,
        ]);

        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'campaign.paused',
            'target_type' => 'campaign',
            'target_id' => $campaign->id,
        ]);
    }

    public function test_admin_can_delete_any_product(): void
    {
        $admin = $this->actingAsAdmin();
        $product = SponsorProduct::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/products/{$product->id}", [
            'reason' => 'Policy violation',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('sponsor_products', ['id' => $product->id]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'product.deleted',
            'actor_id' => $admin->id,
            'target_type' => 'product',
            'target_id' => $product->id,
        ]);
    }

    public function test_admin_can_create_and_delete_best_deal(): void
    {
        $admin = $this->actingAsAdmin();
        $products = SponsorProduct::factory()->count(2)->create();
        $tier = SponsorTier::first();

        $createResponse = $this->postJson('/api/v1/admin/best-deals', [
            'title' => 'Flash Sale',
            'product_ids' => $products->pluck('id')->toArray(),
            'tier_id' => $tier->id,
            'weight' => 10,
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Flash Sale')
            ->assertJsonPath('data.products', fn ($list) => count($list) === 2);

        $dealId = $createResponse->json('data.id');
        $this->assertDatabaseHas('best_deals', ['id' => $dealId]);

        $this->deleteJson("/api/v1/admin/best-deals/{$dealId}")
            ->assertOk();

        $this->assertDatabaseMissing('best_deals', ['id' => $dealId, 'deleted_at' => null]);
    }

    public function test_admin_can_list_audit_logs(): void
    {
        $admin = $this->actingAsAdmin();
        AdminAuditLog::factory()->count(5)->create(['actor_id' => $admin->id]);

        $response = $this->getJson('/api/v1/admin/audit-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data', fn ($data) => count($data) === 5)
            ->assertJsonPath('meta.total', 5);
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $this->getJson('/api/v1/admin/notifications')->assertUnauthorized();
        $this->getJson('/api/v1/admin/audit-logs')->assertUnauthorized();
    }
}
