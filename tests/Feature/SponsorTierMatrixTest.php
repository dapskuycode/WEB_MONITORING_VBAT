<?php

namespace Tests\Feature;

use App\Models\BenefitCategory;
use App\Models\SponsorTier;
use App\Models\TierBenefit;
use App\Models\User;
use Database\Seeders\SponsorTierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SponsorTierMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SponsorTierSeeder::class);
    }

    public function test_six_tiers_and_fourteen_categories_are_seeded()
    {
        $this->assertEquals(6, SponsorTier::count());
        $this->assertEquals(14, BenefitCategory::count());
        $this->assertEquals(84, TierBenefit::count());
    }

    public function test_kontribusi_has_disabled_hero_slider()
    {
        $kontribusi = SponsorTier::where('slug', 'kontribusi')->first();
        $heroSlider = BenefitCategory::where('slug', 'hero_slider')->first();

        $benefit = TierBenefit::where('tier_id', $kontribusi->id)
            ->where('benefit_category_id', $heroSlider->id)
            ->first();

        $this->assertTrue((bool)$benefit->is_disabled);
        $this->assertNull($benefit->value);
    }

    public function test_diamond_has_unlimited_product_quota()
    {
        $diamond = SponsorTier::where('slug', 'diamond')->first();
        $quota = BenefitCategory::where('slug', 'kuota_produk')->first();

        $benefit = TierBenefit::where('tier_id', $diamond->id)
            ->where('benefit_category_id', $quota->id)
            ->first();

        $this->assertTrue((bool)$benefit->is_unlimited);
        $this->assertNull($benefit->value);
    }

    public function test_admin_can_update_gold_quota_in_matrix()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $gold = SponsorTier::where('slug', 'gold')->first();
        $quota = BenefitCategory::where('slug', 'kuota_produk')->first();

        $component = Livewire::actingAs($admin)
            ->test('admin.sponsor-manager')
            ->set('activeTab', 'matrix')
            ->set('matrix.kuota_produk.gold.value', 50)
            ->call('saveMatrix');

        $benefit = TierBenefit::where('tier_id', $gold->id)
            ->where('benefit_category_id', $quota->id)
            ->first();

        $this->assertEquals(50, $benefit->value);
    }

    public function test_reset_row_restores_initial_values()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $gold = SponsorTier::where('slug', 'gold')->first();
        $quota = BenefitCategory::where('slug', 'kuota_produk')->first();

        // Alter value to 50
        $component = Livewire::actingAs($admin)
            ->test('admin.sponsor-manager')
            ->set('matrix.kuota_produk.gold.value', 50)
            ->call('promptResetRow', 'kuota_produk')
            ->assertSet('showResetConfirmModal', true)
            ->call('confirmReset');

        // Check in component memory that it restored to initial (75)
        $this->assertEquals(75, $component->get('matrix.kuota_produk.gold.value'));
    }

    public function test_reset_all_restores_all_values()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $component = Livewire::actingAs($admin)
            ->test('admin.sponsor-manager')
            ->set('matrix.kuota_produk.gold.value', 50)
            ->set('matrix.share_of_voice.platinum.value', 30)
            ->call('promptResetAll')
            ->assertSet('showResetConfirmModal', true)
            ->call('confirmReset');

        $this->assertEquals(75, $component->get('matrix.kuota_produk.gold.value'));
        $this->assertEquals(50, $component->get('matrix.share_of_voice.platinum.value'));
    }
}
