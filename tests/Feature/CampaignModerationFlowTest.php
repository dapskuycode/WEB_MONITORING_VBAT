<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CRITICAL: Campaign Moderation Flow
 * - Sponsor submits campaign → defaults to "Pending Approval" status
 * - Campaign only becomes "Active" when Super Admin updates to "Approved"
 */
class CampaignModerationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Sponsor $sponsor;
    protected User $sponsorUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['role' => 'super_admin']);
        $this->sponsorUser = User::factory()->create(['role' => 'sponsor']);
        $this->sponsor = Sponsor::factory()->create(['user_id' => $this->sponsorUser->id]);
    }

    public function test_campaign_defaults_to_pending_approval_status(): void
    {
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'placement_type' => 'hero_slider',
        ]);

        $this->assertEquals(
            'pending_approval',
            $campaign->status,
            'New campaign must default to pending_approval status'
        );
    }

    public function test_campaign_can_transition_to_approved(): void
    {
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'status' => 'pending_approval',
        ]);

        $campaign->update(['status' => 'approved']);

        $this->assertEquals('approved', $campaign->status);
    }

    public function test_campaign_can_transition_to_rejected(): void
    {
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'status' => 'pending_approval',
        ]);

        $campaign->update(['status' => 'rejected']);

        $this->assertEquals('rejected', $campaign->status);
    }

    public function test_all_placement_types_default_to_pending(): void
    {
        $placementTypes = [
            'hero_slider',
            'horizontal_banner',
            'card_feed',
            'popup',
            'best_deal',
        ];

        foreach ($placementTypes as $type) {
            $campaign = Campaign::factory()->create([
                'sponsor_id' => $this->sponsor->id,
                'placement_type' => $type,
            ]);

            $this->assertEquals('pending_approval', $campaign->status, "Type {$type} should default to pending_approval");
        }
    }

    public function test_pending_campaign_should_not_be_active(): void
    {
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'status' => 'pending_approval',
        ]);

        $this->assertFalse($campaign->is_active ?? false, 'Pending campaign should not be active');
    }

    public function test_approved_campaign_can_be_marked_active(): void
    {
        $campaign = Campaign::factory()->create([
            'sponsor_id' => $this->sponsor->id,
            'status' => 'approved',
        ]);

        $campaign->update(['is_active' => true]);

        $this->assertTrue($campaign->is_active);
    }
}
