<?php

namespace Tests\Feature\Api;

use App\Models\BenefitCategory;
use App\Models\FeedConfig;
use App\Models\PlacementConfig;
use App\Models\SponsorTier;
use App\Models\TierBenefit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConfigCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'super_admin']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    // ─── Placement Config CRUD ─────────────────────────────────

    public function test_admin_can_list_placement_configs(): void
    {
        PlacementConfig::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/placement-configs');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_admin_can_create_placement_config(): void
    {
        $tier = SponsorTier::first();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/placement-configs', [
                'placement_type' => 'hero_slider',
                'tier_id' => $tier?->id,
                'max_slots' => 6,
                'target_probability' => 0.8,
                'share_of_voice' => 0.5,
                'insertion_interval' => 12,
                'fallback_behavior' => 'show_default',
                'is_active' => true,
                'sort_order' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.placement_type', 'hero_slider')
            ->assertJsonPath('data.max_slots', 6);
    }

    public function test_admin_can_update_placement_config(): void
    {
        $config = PlacementConfig::factory()->create(['max_slots' => 4]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/placement-configs/{$config->id}", [
                'max_slots' => 8,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.max_slots', 8);
    }

    public function test_admin_can_delete_placement_config(): void
    {
        $config = PlacementConfig::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/placement-configs/{$config->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('placement_configs', ['id' => $config->id]);
    }

    public function test_placement_config_validation_rejects_invalid_type(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/placement-configs', [
                'placement_type' => 'invalid_type',
                'max_slots' => 6,
                'target_probability' => 0.8,
                'share_of_voice' => 0.5,
                'fallback_behavior' => 'show_default',
            ]);

        $response->assertStatus(422);
    }

    // ─── Feed Config CRUD ──────────────────────────────────────

    public function test_admin_can_list_feed_configs(): void
    {
        FeedConfig::factory()->count(2)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/feed-configs');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_admin_can_create_feed_config(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/feed-configs', [
                'feed_type' => 'home',
                'banner_type' => 'hero',
                'insertion_interval' => 8,
                'max_banners' => 3,
                'is_active' => true,
                'sort_order' => 0,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.feed_type', 'home')
            ->assertJsonPath('data.insertion_interval', 8);
    }

    public function test_admin_can_update_feed_config(): void
    {
        $config = FeedConfig::factory()->create(['insertion_interval' => 12]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/feed-configs/{$config->id}", [
                'insertion_interval' => 6,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.insertion_interval', 6);
    }

    public function test_admin_can_delete_feed_config(): void
    {
        $config = FeedConfig::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/feed-configs/{$config->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('feed_configs', ['id' => $config->id]);
    }

    public function test_feed_config_validation_rejects_invalid_feed_type(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/feed-configs', [
                'feed_type' => 'invalid',
                'banner_type' => 'hero',
                'insertion_interval' => 8,
            ]);

        $response->assertStatus(422);
    }

    // ─── Benefit Category CRUD ─────────────────────────────────

    public function test_admin_can_list_benefit_categories(): void
    {
        BenefitCategory::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/benefit-categories');

        $response->assertOk()->assertJson(['success' => true]);
    }

    public function test_admin_can_create_benefit_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/benefit-categories', [
                'name' => 'Custom Benefit',
                'slug' => 'custom_benefit',
                'description' => 'A custom benefit category',
                'icon' => 'heroicon-c-star',
                'sort_order' => 99,
                'is_active' => true,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Custom Benefit')
            ->assertJsonPath('data.icon', 'heroicon-c-star');
    }

    public function test_admin_can_update_benefit_category(): void
    {
        $cat = BenefitCategory::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/benefit-categories/{$cat->id}", [
                'name' => 'New Name',
                'icon' => 'heroicon-c-cube',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.icon', 'heroicon-c-cube');
    }

    public function test_admin_can_delete_benefit_category_without_references(): void
    {
        $cat = BenefitCategory::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/benefit-categories/{$cat->id}");

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('benefit_categories', ['id' => $cat->id]);
    }

    public function test_admin_cannot_delete_benefit_category_with_references(): void
    {
        $cat = BenefitCategory::factory()->create();
        $tier = SponsorTier::factory()->create();
        TierBenefit::create([
            'tier_id' => $tier->id,
            'benefit_category_id' => $cat->id,
            'value' => 10,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/benefit-categories/{$cat->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertDatabaseHas('benefit_categories', ['id' => $cat->id]);
    }

    // ─── Authorization ─────────────────────────────────────────

    public function test_student_cannot_access_placement_config_crud(): void
    {
        $endpoints = [
            ['get', '/api/v1/admin/placement-configs'],
            ['post', '/api/v1/admin/placement-configs'],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->actingAs($this->student, 'sanctum')
                ->{$method.'Json'}($url)
                ->assertForbidden();
        }
    }

    public function test_student_cannot_access_feed_config_crud(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/admin/feed-configs')
            ->assertForbidden();

        $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/v1/admin/feed-configs', [])
            ->assertForbidden();
    }

    public function test_student_cannot_access_benefit_category_crud(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/admin/benefit-categories')
            ->assertForbidden();

        $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/v1/admin/benefit-categories', [])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_config_crud(): void
    {
        $this->getJson('/api/v1/admin/placement-configs')
            ->assertUnauthorized();

        $this->getJson('/api/v1/admin/feed-configs')
            ->assertUnauthorized();

        $this->getJson('/api/v1/admin/benefit-categories')
            ->assertUnauthorized();
    }
}
