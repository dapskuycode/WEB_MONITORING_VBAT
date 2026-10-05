<?php

namespace Tests\Feature;

use App\Models\BenefitCategory;
use App\Models\BestDeal;
use App\Models\Campaign;
use App\Models\Placement;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use App\Models\TierBenefit;
use App\Models\User;
use Database\Seeders\SponsorTierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SponsorAdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SponsorTierSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────
    // SPONSOR-04: Bobot Tayang Otomatis dari Tier / Share of Voice
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_sponsor_and_campaign_weights_automatically_sync_from_tier_share_of_voice(): void
    {
        $platinumTier = SponsorTier::where('slug', 'platinum')->firstOrFail();
        $silverTier = SponsorTier::where('slug', 'silver')->firstOrFail();

        $user = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create([
            'user_id' => $user->id,
            'tier_id' => $silverTier->id,
            'tier' => 'silver',
        ]);

        // Create campaign for sponsor
        $campaign = Campaign::create([
            'sponsor_id' => $sponsor->id,
            'title' => 'Promo Diskon Tools',
            'placement_type' => 'hero_slider',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDays(10),
            'weight' => $sponsor->weight,
            'is_active' => true,
        ]);

        // Initial weight should be Silver Share of Voice (20)
        $this->assertEquals(20, $sponsor->fresh()->weight);
        $this->assertEquals(20, $campaign->fresh()->weight);

        // Upgrade sponsor to Platinum tier
        $sponsor->update([
            'tier_id' => $platinumTier->id,
            'tier' => 'platinum',
        ]);

        // Sponsor weight and campaign weight should automatically update to Platinum Share of Voice (50)
        $this->assertEquals(50, $sponsor->fresh()->weight);
        $this->assertEquals(50, $campaign->fresh()->weight);
    }

    // ─────────────────────────────────────────────────────────────
    // SPONSOR-05: Batas Kuota & Aturan Unggah Bulanan
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_monthly_product_quota_enforced_for_standard_tier(): void
    {
        $kontribusiTier = SponsorTier::where('slug', 'kontribusi')->firstOrFail();
        $user = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create([
            'user_id' => $user->id,
            'tier_id' => $kontribusiTier->id,
            'tier' => 'kontribusi',
        ]);

        // Kontribusi has quota of 5 products per month
        $this->assertEquals(5, $sponsor->getMonthlyProductQuota());

        // Create 5 products for current month
        for ($i = 1; $i <= 5; $i++) {
            SponsorProduct::create([
                'sponsor_id' => $sponsor->id,
                'name' => "Produk Part {$i}",
                'price' => 50000,
                'shopee_url' => 'https://shopee.co.id/product/123/' . $i,
                'is_active' => true,
            ]);
        }

        $this->assertEquals(5, $sponsor->getCurrentMonthProductsCount());
        $this->assertTrue($sponsor->hasReachedProductQuota());

        // Attempting to create a 6th product via API should be rejected with 422
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/products', [
            'name' => 'Produk Melebihi Kuota',
            'price' => 75000,
            'shopee_url' => 'https://shopee.co.id/product/overquota',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['kuota_produk']]);
    }

    #[Test]
    public function test_diamond_tier_has_unlimited_product_quota(): void
    {
        $diamondTier = SponsorTier::where('slug', 'diamond')->firstOrFail();
        $user = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create([
            'user_id' => $user->id,
            'tier_id' => $diamondTier->id,
            'tier' => 'diamond',
        ]);

        $this->assertNull($sponsor->getMonthlyProductQuota());
        $this->assertFalse($sponsor->hasReachedProductQuota());
    }

    #[Test]
    public function test_product_quota_only_counts_current_month(): void
    {
        $kontribusiTier = SponsorTier::where('slug', 'kontribusi')->firstOrFail();
        $user = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create([
            'user_id' => $user->id,
            'tier_id' => $kontribusiTier->id,
            'tier' => 'kontribusi',
        ]);

        // Create 5 products in previous month
        for ($i = 1; $i <= 5; $i++) {
            $p = SponsorProduct::create([
                'sponsor_id' => $sponsor->id,
                'name' => "Produk Bulan Lalu {$i}",
                'price' => 50000,
                'shopee_url' => 'https://shopee.co.id/product/old/' . $i,
                'is_active' => true,
            ]);
            $p->created_at = now()->subMonth();
            $p->save();
        }

        // Current month count should be 0, quota not reached
        $this->assertEquals(0, $sponsor->getCurrentMonthProductsCount());
        $this->assertFalse($sponsor->hasReachedProductQuota());
    }

    // ─────────────────────────────────────────────────────────────
    // SPONSOR-06: Alur Best Deal Mandiri & Rotasi Otomatis
    // ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_sponsor_can_self_service_submit_product_to_best_deal(): void
    {
        $goldTier = SponsorTier::where('slug', 'gold')->firstOrFail();
        $user = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create([
            'user_id' => $user->id,
            'tier_id' => $goldTier->id,
            'tier' => 'gold',
        ]);

        $product = SponsorProduct::create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Blower Quick 2008 Original',
            'price' => 1250000,
            'shopee_url' => 'https://shopee.co.id/product/blower',
            'is_active' => true,
        ]);

        // Submit to Best Deal
        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/products/{$product->id}/best-deal");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_id', $product->id)
            ->assertJsonPath('data.badge_text', 'BEST DEAL GOLD')
            ->assertJsonPath('data.order', 3); // Gold rank = 3

        $activeBestDeal = BestDeal::active()->first();
        $this->assertNotNull($activeBestDeal);
        $this->assertTrue($activeBestDeal->products->contains('id', $product->id));

        // Withdraw from Best Deal
        $deleteResponse = $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/products/{$product->id}/best-deal");
        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertFalse($activeBestDeal->fresh()->products->contains('id', $product->id));
    }

    #[Test]
    public function test_kontribusi_tier_cannot_submit_to_best_deal(): void
    {
        $kontribusiTier = SponsorTier::where('slug', 'kontribusi')->firstOrFail();
        $user = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create([
            'user_id' => $user->id,
            'tier_id' => $kontribusiTier->id,
            'tier' => 'kontribusi',
        ]);

        $product = SponsorProduct::create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Kabel Jumper Tembaga',
            'price' => 15000,
            'shopee_url' => 'https://shopee.co.id/product/jumper',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/products/{$product->id}/best-deal");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tier Kontribusi tidak memiliki slot Best Deal.');
    }

    #[Test]
    public function test_best_deals_are_ordered_by_tier_priority(): void
    {
        // Setup Diamond, Platinum, and Bronze sponsors and products
        $diamondTier = SponsorTier::where('slug', 'diamond')->firstOrFail();
        $platinumTier = SponsorTier::where('slug', 'platinum')->firstOrFail();
        $bronzeTier = SponsorTier::where('slug', 'bronze')->firstOrFail();

        $diamondSponsor = Sponsor::factory()->create(['tier_id' => $diamondTier->id, 'tier' => 'diamond']);
        $platinumSponsor = Sponsor::factory()->create(['tier_id' => $platinumTier->id, 'tier' => 'platinum']);
        $bronzeSponsor = Sponsor::factory()->create(['tier_id' => $bronzeTier->id, 'tier' => 'bronze']);

        $bronzeProduct = SponsorProduct::create([
            'sponsor_id' => $bronzeSponsor->id,
            'name' => 'Bronze Flux Solder',
            'price' => 25000,
            'shopee_url' => 'https://shopee.co.id/flux',
            'is_active' => true,
        ]);

        $diamondProduct = SponsorProduct::create([
            'sponsor_id' => $diamondSponsor->id,
            'name' => 'Diamond Microscop Trinocular',
            'price' => 4500000,
            'shopee_url' => 'https://shopee.co.id/microscope',
            'is_active' => true,
        ]);

        $platinumProduct = SponsorProduct::create([
            'sponsor_id' => $platinumSponsor->id,
            'name' => 'Platinum Power Supply JCID',
            'price' => 2100000,
            'shopee_url' => 'https://shopee.co.id/powersupply',
            'is_active' => true,
        ]);

        $bestDeal = BestDeal::create([
            'title' => 'BEST DEALS VBAT',
            'description' => 'Produk Pilihan Terbaik',
            'is_active' => true,
            'selection_type' => 'auto',
        ]);

        // Attach in mixed order: Bronze first, then Diamond, then Platinum
        $bestDeal->products()->attach([
            $bronzeProduct->id => ['badge_text' => 'BEST DEAL BRONZE', 'order' => 5],
            $diamondProduct->id => ['badge_text' => 'BEST DEAL DIAMOND', 'order' => 1],
            $platinumProduct->id => ['badge_text' => 'BEST DEAL PLATINUM', 'order' => 2],
        ]);

        // Query Best Deal products
        $response = $this->getJson('/api/v1/shop/best-deals');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $productNames = collect($response->json('data'))->pluck('name')->toArray();

        // Diamond (rank 1) should be first, Platinum (rank 2) second, Bronze (rank 5) third
        $this->assertEquals([
            'Diamond Microscop Trinocular',
            'Platinum Power Supply JCID',
            'Bronze Flux Solder',
        ], $productNames);
    }
}
