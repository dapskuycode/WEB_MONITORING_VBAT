<?php

namespace Tests\Feature\Api;

use App\Models\AdminAuditLog;
use App\Models\AdminNotification;
use App\Models\Campaign;
use App\Models\PlacementConfig;
use App\Models\PushNotification;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GapClosingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $sponsorUser;
    protected Sponsor $diamondSponsor;
    protected SponsorTier $diamondTier;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create(['role' => 'super_admin']);

        // Create sponsor user and diamond sponsor
        $this->sponsorUser = User::factory()->create(['role' => 'sponsor']);

        // Create Diamond tier
        $this->diamondTier = SponsorTier::create([
            'slug' => 'diamond',
            'name' => 'Diamond',
            'badge_label' => 'Diamond Partner',
            'badge_color' => '#B9F2FF',
            'icon_url' => null,
            'sort_order' => 6,
            'is_active' => true,
        ]);

        // Create a diamond-tiered sponsor
        $this->diamondSponsor = Sponsor::create([
            'user_id' => $this->sponsorUser->id,
            'name' => 'Diamond Corp',
            'slug' => 'diamond-corp',
            'description' => 'A premium diamond sponsor',
            'logo_path' => 'sponsor/logos/diamond.png',
            'co_branding_header_url' => null,
            'co_branding_splash_url' => null,
            'tier' => 'diamond',
            'tier_id' => $this->diamondTier->id,
            'weight' => 100,
            'is_active' => true,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // [C1] Co-Branding Asset System (Diamond Tier)
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function co_branding_upload_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/co-branding', []);

        $response->assertStatus(401);
    }

    #[Test]
    public function non_diamond_sponsor_cannot_upload_co_branding(): void
    {
        // Create a Bronze tier and bronze sponsor
        $bronzeTier = SponsorTier::create([
            'slug' => 'bronze', 'name' => 'Bronze', 'badge_label' => 'Bronze',
            'badge_color' => '#CD7F32', 'icon_url' => null, 'sort_order' => 2, 'is_active' => true,
        ]);
        $bronzeSponsor = Sponsor::create([
            'user_id' => $this->sponsorUser->id, 'name' => 'Bronze Inc', 'slug' => 'bronze-inc',
            'description' => 'Bronze tier', 'logo_path' => null, 'tier' => 'bronze',
            'tier_id' => $bronzeTier->id, 'weight' => 10, 'is_active' => true,
        ]);

        $response = $this->actingAs($this->sponsorUser)
            ->postJson('/api/v1/sponsors/' . $bronzeSponsor->id . '/co-branding', []);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'success' => false,
            'message' => 'Co-branding assets are only available for Diamond-tier sponsors.',
        ]);
    }

    #[Test]
    public function co_branding_upload_accepts_header_banner_image(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('header.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->sponsorUser)
            ->postJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/co-branding', [
                'header_banner' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('data.co_branding_header_url'));
        $this->assertNull($response->json('data.co_branding_splash_url'));
    }

    #[Test]
    public function co_branding_upload_accepts_splash_logo_image(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('splash.png', 50, 'image/png');

        $response = $this->actingAs($this->sponsorUser)
            ->postJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/co-branding', [
                'splash_logo' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNotNull($response->json('data.co_branding_splash_url'));
        $this->assertNull($response->json('data.co_branding_header_url'));
    }

    #[Test]
    public function co_branding_upload_accepts_both_files(): void
    {
        $header = \Illuminate\Http\UploadedFile::fake()->create('header.jpg', 100, 'image/jpeg');
        $splash = \Illuminate\Http\UploadedFile::fake()->create('splash.png', 50, 'image/png');

        $response = $this->actingAs($this->sponsorUser)
            ->postJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/co-branding', [
                'header_banner' => $header,
                'splash_logo' => $splash,
            ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.co_branding_header_url'));
        $this->assertNotNull($response->json('data.co_branding_splash_url'));
    }

    #[Test]
    public function co_branding_upload_rejects_non_image_files(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->sponsorUser)
            ->postJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/co-branding', [
                'header_banner' => $file,
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function co_branding_upload_rejects_empty_payload(): void
    {
        $response = $this->actingAs($this->sponsorUser)
            ->postJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/co-branding', []);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'At least one file (header_banner or splash_logo) must be provided.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // [M1] Dedicated Storefront Endpoint
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function storefront_returns_sponsor_profile_with_tier_and_badge(): void
    {
        // Create some products for this sponsor
        SponsorProduct::create([
            'sponsor_id' => $this->diamondSponsor->id,
            'name' => 'Diamond Product A',
            'slug' => 'diamond-product-a',
            'description' => 'Premium product from Diamond Corp',
            'price' => 500000,
            'is_active' => true,
            'order' => 1,
        ]);

        $response = $this->getJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/storefront');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Check sponsor data
        $this->assertEquals('Diamond Corp', $response->json('data.sponsor.name'));
        $this->assertEquals('diamond-corp', $response->json('data.sponsor.slug'));

        // Check tier info
        $this->assertNotNull($response->json('data.tier_info'));
        $this->assertEquals('Diamond', $response->json('data.tier_info.name'));
        $this->assertEquals('#B9F2FF', $response->json('data.tier_info.badge_color'));

        // Check badge
        $this->assertEquals('Diamond Partner', $response->json('data.badge.label'));
        $this->assertEquals('#B9F2FF', $response->json('data.badge.color'));

        // Check products
        $this->assertCount(1, $response->json('data.products'));
        $this->assertEquals('Diamond Product A', $response->json('data.products.0.name'));

        // Check meta pagination
        $this->assertEquals(1, $response->json('meta.total_products'));
    }

    #[Test]
    public function storefront_404_for_inactive_sponsor(): void
    {
        $inactiveSponsor = Sponsor::create([
            'user_id' => $this->sponsorUser->id, 'name' => 'Ghost Inc', 'slug' => 'ghost-inc',
            'description' => 'Inactive', 'logo_path' => null, 'tier' => 'bronze',
            'tier_id' => $this->diamondTier->id, 'weight' => 10, 'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/sponsors/' . $inactiveSponsor->id . '/storefront');
        $response->assertStatus(404);
    }

    #[Test]
    public function storefront_products_are_paginated(): void
    {
        // Create 25 products
        foreach (range(1, 25) as $i) {
            SponsorProduct::create([
                'sponsor_id' => $this->diamondSponsor->id,
                'name' => "Product {$i}",
                'slug' => "product-{$i}",
                'description' => "Desc {$i}",
                'price' => 10000 * $i,
                'is_active' => true,
                'order' => $i,
            ]);
        }

        // Default per_page=20 should return 20 items
        $response = $this->getJson('/api/v1/sponsors/' . $this->diamondSponsor->id . '/storefront?per_page=20');
        $response->assertStatus(200);
        $this->assertCount(20, $response->json('data.products'));
        $this->assertEquals(25, $response->json('meta.total_products'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }

    // ══════════════════════════════════════════════════════════════════
    // [m1] Hero Slider Max 6 Validation
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function hero_slider_max_6_blocks_seventh_activation(): void
    {
        // Create 6 active hero slider campaigns
        foreach (range(1, 6) as $i) {
            Campaign::create([
                'sponsor_id' => $this->diamondSponsor->id,
                'placement_type' => 'hero_slider',
                'title' => "Hero Slide {$i}",
                'status' => 'active',
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(30),
                'weight' => 100 - $i,
                'daily_limit' => 0,
            ]);
        }

        // Create a 7th campaign in paused state
        $seventhCampaign = Campaign::create([
            'sponsor_id' => $this->diamondSponsor->id,
            'placement_type' => 'hero_slider',
            'title' => 'Hero Slide 7 (Should Be Blocked)',
            'status' => 'paused',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'weight' => 50,
            'daily_limit' => 0,
        ]);

        // Try to activate the 7th
        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/campaigns/' . $seventhCampaign->id, [
                'status' => 'active',
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Cannot activate this campaign. Maximum of 6 active hero slider slides has been reached.',
        ]);
    }

    #[Test]
    public function hero_slider_allows_up_to_6_active(): void
    {
        // Create 5 active + try to activate 6th → should succeed
        foreach (range(1, 5) as $i) {
            Campaign::create([
                'sponsor_id' => $this->diamondSponsor->id,
                'placement_type' => 'hero_slider',
                'title' => "Hero Slide {$i}",
                'status' => 'active',
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(30),
                'weight' => 100,
                'daily_limit' => 0,
            ]);
        }

        $sixthCampaign = Campaign::create([
            'sponsor_id' => $this->diamondSponsor->id,
            'placement_type' => 'hero_slider',
            'title' => 'Hero Slide 6 (Allowed)',
            'status' => 'paused',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'weight' => 90,
            'daily_limit' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/campaigns/' . $sixthCampaign->id, [
                'status' => 'active',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('active', $sixthCampaign->fresh()->status);
    }

    #[Test]
    public function hero_slider_limit_only_applies_to_hero_type(): void
    {
        // Create 6 active hero sliders
        foreach (range(1, 6) as $i) {
            Campaign::create([
                'sponsor_id' => $this->diamondSponsor->id,
                'placement_type' => 'hero_slider',
                'title' => "Hero {$i}",
                'status' => 'active',
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(30),
                'weight' => 100,
                'daily_limit' => 0,
            ]);
        }

        // Creating an active card_infeed campaign should NOT be blocked
        $cardCampaign = Campaign::create([
            'sponsor_id' => $this->diamondSponsor->id,
            'placement_type' => 'card_infeed',
            'title' => 'Card Banner (Not Hero)',
            'status' => 'paused',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'weight' => 80,
            'daily_limit' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->putJson('/api/v1/admin/campaigns/' . $cardCampaign->id, [
                'status' => 'active',
            ]);

        $response->assertStatus(200); // Should succeed — not a hero slider
    }

    // ══════════════════════════════════════════════════════════════════
    // [M2] Push Broadcast Creation Endpoint (Admin)
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function push_broadcast_requires_admin_auth(): void
    {
        $response = $this->postJson('/api/v1/admin/push-broadcast', [
            'title' => 'Test Broadcast',
            'body' => 'Hello world',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function push_broadcast_creates_record_and_notification(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/push-broadcast', [
                'title' => 'New Feature Alert',
                'body' => 'Check out our latest update!',
                'target_role' => 'all',
                'deep_link_type' => 'product',
                'deep_link_id' => 42,
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'title' => 'New Feature Alert',
                'target_role' => 'all',
                'status' => 'sent',
            ],
        ]);

        // Verify push notification record was created
        $this->assertDatabaseHas('push_notifications', [
            'title' => 'New Feature Alert',
            'target_audience' => 'all',
            'status' => 'sent',
        ]);

        // Verify admin notification was created
        $this->assertDatabaseHas('admin_notifications', [
            'type' => 'push.broadcast_created',
            'title' => 'Push Broadcast: New Feature Alert',
            'actor_type' => 'admin',
            'actor_id' => $this->admin->id,
        ]);

        // Verify audit log was created
        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'push_broadcast_created',
            'actor_id' => $this->admin->id,
            'actor_type' => 'admin',
        ]);
    }

    #[Test]
    public function push_broadcast_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/push-broadcast', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'body']);
    }

    #[Test]
    public function push_broadcast_validates_target_role_enum(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/push-broadcast', [
                'title' => 'Test',
                'body' => 'Body',
                'target_role' => 'invalid_role',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_role']);
    }

    // ══════════════════════════════════════════════════════════════════
    // [M3] Share of Voice Algorithm (Unit-level verification)
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function sov_weighting_boosts_high_sov_campaigns(): void
    {
        // Create placement config with SoV for diamond tier
        PlacementConfig::create([
            'placement_type' => 'hero_slider',
            'tier_id' => $this->diamondTier->id,
            'max_slots' => 3,
            'target_probability' => 0.8,
            'share_of_voice' => 0.600, // 60% SoV
            'insertion_interval' => 3,
            'fallback_behavior' => 'show_default',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create a bronze tier with low SoV
        $bronzeTier = SponsorTier::create([
            'slug' => 'bronze', 'name' => 'Bronze', 'badge_label' => 'Bronze',
            'badge_color' => '#CD7F32', 'icon_url' => null, 'sort_order' => 2, 'is_active' => true,
        ]);
        PlacementConfig::create([
            'placement_type' => 'hero_slider',
            'tier_id' => $bronzeTier->id,
            'max_slots' => 3,
            'target_probability' => 0.8,
            'share_of_voice' => 0.100, // 10% SoV
            'insertion_interval' => 3,
            'fallback_behavior' => 'show_default',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create campaigns for each tier
        $diamondCampaign = Campaign::create([
            'sponsor_id' => $this->diamondSponsor->id,
            'placement_type' => 'hero_slider',
            'title' => 'Diamond Ad',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'weight' => 50, // Same base weight
            'daily_limit' => 0,
        ]);

        $bronzeSponsor = Sponsor::create([
            'user_id' => $this->sponsorUser->id, 'name' => 'Bronze Co', 'slug' => 'bronze-co',
            'description' => '', 'logo_path' => null, 'tier' => 'bronze',
            'tier_id' => $bronzeTier->id, 'weight' => 10, 'is_active' => true,
        ]);
        $bronzeCampaign = Campaign::create([
            'sponsor_id' => $bronzeSponsor->id,
            'placement_type' => 'hero_slider',
            'title' => 'Bronze Ad',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'weight' => 50, // Same base weight
            'daily_limit' => 0,
        ]);

        // Run selection multiple times — diamond should be selected more often due to SoV
        $service = app(\App\Services\PlacementSelectionService::class);
        $diamondWins = 0;
        $runs = 50;

        for ($i = 0; $i < $runs; $i++) {
            $selected = $service->select('hero_slider', slots: 1);
            if ($selected->first()?->id === $diamondCampaign->id) {
                $diamondWins++;
            }
        }

        // With 60% SoV vs 10% SoV and equal base weights,
        // diamond should win significantly more than 50% of runs
        // (not deterministic, but statistically very likely > 65%)
        $winRate = $diamondWins / $runs;
        $this->assertGreaterThan(0.55, $winRate, "Diamond (SoV 0.6) should win more often than Bronze (SoV 0.1). Win rate: {$winRate}");
    }

    #[Test]
    public function sov_zero_does_not_change_base_weight(): void
    {
        // Config with SoV = 0 means no boost
        PlacementConfig::create([
            'placement_type' => 'horizontal_infeed',
            'tier_id' => $this->diamondTier->id,
            'max_slots' => 2,
            'target_probability' => 1.0,
            'share_of_voice' => 0.000,
            'insertion_interval' => 5,
            'fallback_behavior' => 'empty',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $campaign = Campaign::create([
            'sponsor_id' => $this->diamondSponsor->id,
            'placement_type' => 'horizontal_infeed',
            'title' => 'Zero SoV Ad',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(30),
            'weight' => 100,
            'daily_limit' => 0,
        ]);

        $service = app(\App\Services\PlacementSelectionService::class);
        $selected = $service->select('horizontal_infeed', slots: 1);

        $this->assertCount(1, $selected);
        $this->assertEquals($campaign->id, $selected->first()->id);
    }

    // ══════════════════════════════════════════════════════════════════
    // [m2] Explicit Tier Badge in API Responses
    // ══════════════════════════════════════════════════════════════════

    #[Test]
    public function sponsors_index_includes_badge_metadata(): void
    {
        $response = $this->getJson('/api/v1/sponsors');

        $response->assertStatus(200);
        $sponsors = $response->json('data');

        // Find our diamond sponsor in the list
        $diamond = collect($sponsors)->first(fn ($s) => $s['slug'] === 'diamond-corp');
        $this->assertNotNull($diamond);

        // Check badge fields exist
        $this->assertArrayHasKey('tier_badge', $diamond);
        $this->assertEquals('Diamond Partner', $diamond['tier_badge']['label']);
        $this->assertEquals('#B9F2FF', $diamond['tier_badge']['color']);

        // Check logo URL is absolute
        $this->assertArrayHasKey('logo_url', $diamond);
        $this->assertStringContainsString('http', $diamond['logo_url']);
    }

    #[Test]
    public function sponsors_show_includes_co_branding_and_badge(): void
    {
        $response = $this->getJson('/api/v1/sponsors/' . $this->diamondSponsor->id);

        $response->assertStatus(200);
        $data = $response->json('data');

        // Co-branding URLs (null since none uploaded yet)
        $this->assertArrayHasKey('co_branding_header_url', $data);
        $this->assertArrayHasKey('co_branding_splash_url', $data);

        // Tier badge
        $this->assertArrayHasKey('tier_badge', $data);
        $this->assertEquals('Diamond Partner', $data['tier_badge']['label']);
        $this->assertEquals('#B9F2FF', $data['tier_badge']['color']);

        // Logo URL
        $this->assertArrayHasKey('logo_url', $data);
    }

    #[Test]
    public function sponsors_list_tiers_includes_badge_color_and_icon(): void
    {
        $response = $this->getJson('/api/v1/sponsors/tiers');

        $response->assertStatus(200);
        $tiers = $response->json('data');

        $diamond = collect($tiers)->first(fn ($t) => $t['slug'] === 'diamond');
        $this->assertNotNull($diamond);

        $this->assertEquals('#B9F2FF', $diamond['badge_color']);
        $this->assertArrayHasKey('icon_url', $diamond);
    }
}
