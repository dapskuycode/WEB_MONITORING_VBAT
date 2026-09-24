<?php

namespace Tests\Feature\Api;

use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SponsorProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SponsorTierSeeder::class);
    }

    private function adminUser(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function createSponsorWithTier(string $tierSlug = 'gold'): Sponsor
    {
        $tier = SponsorTier::where('slug', $tierSlug)->first();
        return Sponsor::factory()->create(['tier_id' => $tier->id, 'tier' => $tierSlug]);
    }

    #[Test]
    public function test_can_list_products(): void
    {
        $sponsor = $this->createSponsorWithTier();
        SponsorProduct::factory()->count(3)->create(['sponsor_id' => $sponsor->id, 'is_active' => true]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function test_can_filter_products_by_sponsor(): void
    {
        $s1 = $this->createSponsorWithTier();
        $s2 = $this->createSponsorWithTier();

        SponsorProduct::factory()->count(2)->create(['sponsor_id' => $s1->id, 'is_active' => true]);
        SponsorProduct::factory()->create(['sponsor_id' => $s2->id, 'is_active' => true]);

        $response = $this->getJson("/api/v1/products?sponsor_id={$s1->id}");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function test_can_show_product(): void
    {
        $sponsor = $this->createSponsorWithTier();
        $product = SponsorProduct::factory()->create(['sponsor_id' => $sponsor->id]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id);
    }

    #[Test]
    public function test_can_create_product(): void
    {
        $this->actingAs($this->adminUser());

        $sponsor = $this->createSponsorWithTier('gold');

        $payload = [
            'sponsor_id' => $sponsor->id,
            'name' => 'LCD iPhone 15 Pro',
            'price' => 850000,
            'discount_price' => 750000,
            'shopee_url' => 'https://shopee.co.id/product/123',
            'tokopedia_url' => null,
            'is_featured' => true,
        ];

        $response = $this->postJson('/api/v1/products', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'LCD iPhone 15 Pro')
            ->assertJsonPath('data.sponsor.id', $sponsor->id);
    }

    #[Test]
    public function test_create_product_requires_at_least_one_marketplace_url(): void
    {
        $this->actingAs($this->adminUser());

        $sponsor = $this->createSponsorWithTier();

        $payload = [
            'sponsor_id' => $sponsor->id,
            'name' => 'Test Product',
            'price' => 100000,
            'shopee_url' => null,
            'tokopedia_url' => null,
        ];

        $response = $this->postJson('/api/v1/products', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function test_can_update_product(): void
    {
        $this->actingAs($this->adminUser());

        $sponsor = $this->createSponsorWithTier();
        $product = SponsorProduct::factory()->create(['sponsor_id' => $sponsor->id, 'price' => 100000]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'price' => 90000,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.price', '90000.00');

        $this->assertDatabaseHas('sponsor_products', [
            'id' => $product->id,
            'price' => 90000,
        ]);
    }

    #[Test]
    public function test_can_soft_delete_product(): void
    {
        $this->actingAs($this->adminUser());

        $sponsor = $this->createSponsorWithTier();
        $product = SponsorProduct::factory()->create(['sponsor_id' => $sponsor->id]);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('sponsor_products', ['id' => $product->id]);
    }

    #[Test]
    public function test_create_product_validates_required_fields(): void
    {
        $this->actingAs($this->adminUser());

        $response = $this->postJson('/api/v1/products', []);

        // sponsor_id is no longer required — it's resolved from auth
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price']);
    }
}