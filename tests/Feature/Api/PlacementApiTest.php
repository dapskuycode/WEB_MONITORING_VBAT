<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\PlacementConfig;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacementApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SponsorTierSeeder::class);
    }

    public function test_placement_returns_empty_for_no_config(): void
    {
        $response = $this->getJson('/api/v1/placements/hero_slider');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.placement_type', 'hero_slider');
    }

    public function test_placement_returns_campaigns_with_config(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);

        PlacementConfig::factory()->create([
            'placement_type' => 'hero_slider',
            'tier_id' => $tier->id,
            'max_slots' => 3,
            'target_probability' => 0.5,
        ]);

        Campaign::factory()->count(5)->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'active',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/placements/hero_slider');

        $response->assertOk()
            ->assertJsonPath('meta.placement_type', 'hero_slider')
            ->assertJson(fn ($json) => $json->has('data')->etc());

        $count = $response->json('meta.count');
        $this->assertGreaterThanOrEqual(1, $count);
        $this->assertLessThanOrEqual(3, $count);
    }

    public function test_invalid_placement_type_returns_422(): void
    {
        $response = $this->getJson('/api/v1/placements/invalid_type');

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_log_impression(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/placements/impression', [
            'campaign_id' => $campaign->id,
            'placement_type' => 'hero_slider',
            'session_id' => 'test-session-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('campaign_logs', [
            'campaign_id' => $campaign->id,
            'event_type' => 'impression',
            'placement_context' => 'hero_slider',
        ]);
    }

    public function test_can_log_click(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/placements/click', [
            'campaign_id' => $campaign->id,
            'placement_type' => 'hero_slider',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('campaign_logs', [
            'campaign_id' => $campaign->id,
            'event_type' => 'click',
            'placement_context' => 'hero_slider',
        ]);
    }

    public function test_best_deal_returns_empty_when_no_active_deals(): void
    {
        $response = $this->getJson('/api/v1/placements/best-deal');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_best_deal_returns_manual_selection(): void
    {
        $tier = SponsorTier::where('slug', 'gold')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);
        $product = SponsorProduct::factory()->create(['sponsor_id' => $sponsor->id]);

        $deal = \App\Models\BestDeal::create([
            'title' => 'Flash Sale Phone',
            'description' => 'Best deal ever',
            'is_active' => true,
            'selected_by' => null,
            'selection_type' => 'manual',
            'sponsor_product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/v1/placements/best-deal');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.selection_type', 'manual');
    }

    public function test_daily_limit_enforced(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);

        PlacementConfig::factory()->create([
            'placement_type' => 'hero_slider',
            'tier_id' => $tier->id,
            'max_slots' => 5,
            'target_probability' => 0.5,
        ]);

        $campaign = Campaign::factory()->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'active',
            'daily_limit' => 2,
        ]);

        // Log 2 impressions (reaching daily limit)
        \App\Models\CampaignLog::factory()->count(2)->create([
            'campaign_id' => $campaign->id,
            'event_type' => 'impression',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/placements/hero_slider');

        $response->assertOk();
        // Campaign should be excluded due to daily limit reached
        $campaignIds = collect($response->json('data'))->pluck('id');
        $this->assertFalse($campaignIds->contains($campaign->id));
    }

    public function test_fallback_show_default_when_no_active_campaigns(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);

        PlacementConfig::factory()->create([
            'placement_type' => 'hero_slider',
            'tier_id' => $tier->id,
            'max_slots' => 3,
            'fallback_behavior' => 'show_default',
        ]);

        // Create paused campaign as fallback
        Campaign::factory()->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'paused',
        ]);

        $response = $this->getJson('/api/v1/placements/hero_slider');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_selection_events_logged(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);

        PlacementConfig::factory()->create([
            'placement_type' => 'hero_slider',
            'tier_id' => $tier->id,
            'max_slots' => 5,
        ]);

        Campaign::factory()->count(3)->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'active',
        ]);

        $this->getJson('/api/v1/placements/hero_slider');

        $this->assertDatabaseHas('campaign_logs', [
            'event_type' => 'selection',
            'placement_context' => 'hero_slider',
        ]);
    }
}
