<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Province;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SponsorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SponsorTierSeeder::class);
    }

    #[Test]
    public function test_can_list_sponsors(): void
    {
        $tier = SponsorTier::where('slug', 'gold')->first();
        Sponsor::factory()->count(3)->create(['tier_id' => $tier->id, 'tier' => 'gold', 'is_active' => true]);

        $response = $this->getJson('/api/v1/sponsors');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function test_can_filter_sponsors_by_tier(): void
    {
        $gold = SponsorTier::where('slug', 'gold')->first();
        $silver = SponsorTier::where('slug', 'silver')->first();

        Sponsor::factory()->create(['tier_id' => $gold->id, 'tier' => 'gold', 'name' => 'Gold Sponsor']);
        Sponsor::factory()->create(['tier_id' => $silver->id, 'tier' => 'silver', 'name' => 'Silver Sponsor']);

        $response = $this->getJson('/api/v1/sponsors?tier=gold');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gold Sponsor');
    }

    #[Test]
    public function test_can_search_sponsors_by_name(): void
    {
        $tier = SponsorTier::first();
        Sponsor::factory()->create(['tier_id' => $tier->id, 'name' => 'Brader Parts']);
        Sponsor::factory()->create(['tier_id' => $tier->id, 'name' => 'Other Shop']);

        $response = $this->getJson('/api/v1/sponsors?search=Brader');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Brader Parts');
    }

    #[Test]
    public function test_can_show_sponsor_with_benefits(): void
    {
        $tier = SponsorTier::where('slug', 'platinum')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id, 'tier' => 'platinum']);

        $response = $this->getJson("/api/v1/sponsors/{$sponsor->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $sponsor->id)
            ->assertJsonPath('data.effective_benefits', fn ($v) => is_array($v) && count($v) > 0)
            ->assertJsonPath('data.sponsor_tier.slug', 'platinum');
    }

    #[Test]
    public function test_can_create_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $tier = SponsorTier::where('slug', 'silver')->first();

        $payload = [
            'name' => 'New Sponsor Co',
            'slug' => 'new-sponsor-co',
            'description' => 'A test sponsor',
            'tier_slug' => 'silver',
            'website_url' => 'https://example.com',
            'weight' => 5,
        ];

        $response = $this->actingAs($admin)->postJson('/api/v1/sponsors', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Sponsor Co')
            ->assertJsonPath('data.sponsor_tier.slug', 'silver');

        $this->assertDatabaseHas('sponsors', [
            'slug' => 'new-sponsor-co',
            'tier_id' => $tier->id,
            'tier' => 'silver',
        ]);
    }

    #[Test]
    public function test_can_update_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $tier = SponsorTier::where('slug', 'bronze')->first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id, 'tier' => 'bronze']);

        $response = $this->actingAs($admin)->putJson("/api/v1/sponsors/{$sponsor->id}", [
            'name' => 'Updated Name',
            'tier_slug' => 'gold',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.sponsor_tier.slug', 'gold');
    }

    #[Test]
    public function test_can_soft_delete_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $tier = SponsorTier::first();
        $sponsor = Sponsor::factory()->create(['tier_id' => $tier->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/v1/sponsors/{$sponsor->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('sponsors', ['id' => $sponsor->id]);
    }

    #[Test]
    public function test_can_list_tiers(): void
    {
        $response = $this->getJson('/api/v1/sponsors/tiers');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(6, 'data');
    }

    #[Test]
    public function test_create_sponsor_validates_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->postJson('/api/v1/sponsors', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'tier_slug']);
    }

    #[Test]
    public function test_show_returns_404_for_missing_sponsor(): void
    {
        $response = $this->getJson('/api/v1/sponsors/99999');
        $response->assertNotFound();
    }
}
