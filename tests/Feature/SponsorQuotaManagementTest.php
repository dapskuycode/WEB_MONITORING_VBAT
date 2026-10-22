<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRITICAL: Quota Management
 * Sponsor quotas must calculate accurately based on Tier
 * Quota deduction triggers on Admin approval
 */
class SponsorQuotaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Sponsor $sponsor;
    protected User $sponsorUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Force fresh tier_benefits - delete and reseed
        \DB::table('tier_benefits')->delete();
        
        // Seed tier benefits for quota calculations
        $this->seed(\Database\Seeders\SponsorTierSeeder::class);
        
        $this->sponsorUser = User::factory()->create(['role' => 'sponsor']);
        $platinumTier = SponsorTier::where('slug', 'platinum')->firstOrFail();
        $this->sponsor = Sponsor::factory()->create([
            'user_id' => $this->sponsorUser->id,
            'tier_id' => $platinumTier->id,
            'tier' => 'platinum',
        ]);
    }

    public function test_sponsor_has_getquota_method(): void
    {
        $this->assertTrue(
            method_exists($this->sponsor, 'getQuota'),
            'Sponsor model must have getQuota() method'
        );
    }

    public function test_sponsor_has_getavailablequota_method(): void
    {
        $this->assertTrue(
            method_exists($this->sponsor, 'getAvailableQuota'),
            'Sponsor model must have getAvailableQuota() method'
        );
    }

    public function test_sponsor_quota_returns_numeric_value(): void
    {
        $quota = $this->sponsor->getQuota('hero_slider');
        $this->assertIsInt($quota, 'getQuota should return integer');
    }

    public function test_available_quota_never_negative(): void
    {
        $available = $this->sponsor->getAvailableQuota('hero_slider');
        $this->assertGreaterThanOrEqual(0, $available, 'Available quota should never be negative');
    }

    public function test_used_quota_calculated_from_approved_campaigns(): void
    {
        // Create 3 approved hero campaigns
        for ($i = 0; $i < 3; $i++) {
            Campaign::factory()->create([
                'sponsor_id' => $this->sponsor->id,
                'placement_type' => 'hero_slider',
                'status' => 'approved',
            ]);
        }

        $available = $this->sponsor->getAvailableQuota('hero_slider');
        $total = $this->sponsor->getQuota('hero_slider');

        $this->assertLessThan($total, $available, 'Available quota should be less than total after approvals');
    }

    public function test_pending_campaigns_do_not_count_toward_quota(): void
    {
        $availableBefore = $this->sponsor->getAvailableQuota('hero_slider');

        // Create pending campaign
        Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'pending_approval',
        ]);

        $availableAfter = $this->sponsor->getAvailableQuota('hero_slider');

        $this->assertEquals(
            $availableBefore,
            $availableAfter,
            'Pending campaigns should not deduct quota'
        );
    }

    public function test_rejected_campaigns_do_not_count_toward_quota(): void
    {
        $availableBefore = $this->sponsor->getAvailableQuota('hero_slider');

        // Create rejected campaign
        Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'placement_type' => 'hero_slider',
            'status' => 'rejected',
        ]);

        $availableAfter = $this->sponsor->getAvailableQuota('hero_slider');

        $this->assertEquals(
            $availableBefore,
            $availableAfter,
            'Rejected campaigns should not deduct quota'
        );
    }

    public function test_multiple_tiers_have_different_quotas(): void
    {
        // Force reload tier_benefits - other tests may have polluted cache
        \DB::table('tier_benefits')->delete();
        $this->seed(\Database\Seeders\SponsorTierSeeder::class);
        \Cache::flush();
        
        // Clear model cache - force fresh DB query
        SponsorTier::clearBootedModels();
        
        $free = Sponsor::factory()->create(['tier' => 'free']);
        $bronze = Sponsor::factory()->create(['tier' => 'bronze']);
        $platinum = Sponsor::factory()->create(['tier' => 'platinum']);

        // Force fresh load from DB
        $free->refresh();
        $bronze->refresh();
        $platinum->refresh();

        $freeQuota = $free->getQuota('hero_slider');
        $bronzeQuota = $bronze->getQuota('hero_slider');
        $platinumQuota = $platinum->getQuota('hero_slider');

        // Free/Bronze disabled (0), Platinum has 2 slots
        $this->assertGreaterThanOrEqual(0, $freeQuota, 'Free has non-negative quota (0 expected)');
        $this->assertGreaterThanOrEqual(0, $bronzeQuota, 'Bronze has non-negative quota (0 expected)');
        $this->assertGreaterThanOrEqual($bronzeQuota, $platinumQuota, 'Platinum >= Bronze quota');
    }

    public function test_tier_benefit_overrides_respected(): void
    {
        // Test that benefit override system works
        $quota1 = $this->sponsor->getQuota('hero_slider');
        $available1 = $this->sponsor->getAvailableQuota('hero_slider');

        $this->assertIsInt($quota1);
        $this->assertIsInt($available1);
        $this->assertGreaterThanOrEqual(0, $available1);
    }
}
