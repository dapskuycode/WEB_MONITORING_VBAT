<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\FeedConfig;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default feed configs
        FeedConfig::firstOrCreate(
            ['feed_type' => 'shop', 'banner_type' => 'shop_horizontal'],
            [
                'insertion_interval' => 12,
                'is_active' => true,
                'metadata' => ['max_banners' => 5],
            ]
        );

        FeedConfig::firstOrCreate(
            ['feed_type' => 'home', 'banner_type' => 'hero_slider'],
            [
                'insertion_interval' => 8,
                'is_active' => true,
                'metadata' => ['max_banners' => 3],
            ]
        );
    }

    /** @test */
    public function test_shop_feed_returns_products_with_content_type(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(5)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/feed/shop');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['content_type', 'id', 'name', 'price'],
                ],
                'meta' => ['next_cursor', 'has_more', 'per_page'],
            ]);

        // All items should be products
        $items = $response->json('data');
        foreach ($items as $item) {
            $this->assertEquals('product', $item['content_type']);
        }
    }

    /** @test */
    public function test_home_feed_returns_mixed_content(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(3)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $lesson = Lesson::factory()->create();
        LearningMaterial::factory()->count(3)->create([
            'lesson_id' => $lesson->id,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/feed/home');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['content_type', 'id'],
                ],
                'meta' => ['next_cursor', 'has_more', 'per_page'],
            ]);

        $items = $response->json('data');
        $contentTypes = array_column($items, 'content_type');

        $this->assertContains('product', $contentTypes);
        $this->assertContains('material', $contentTypes);
    }

    /** @test */
    public function test_feed_returns_empty_state(): void
    {
        $response = $this->getJson('/api/v1/feed/shop');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.has_more', false);
    }

    /** @test */
    public function test_feed_pagination_with_cursor(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(25)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        // First page
        $response1 = $this->getJson('/api/v1/feed/shop?per_page=10');
        $response1->assertOk();

        $meta = $response1->json('meta');
        $this->assertTrue($meta['has_more']);
        $this->assertNotNull($meta['next_cursor']);

        // Second page
        $response2 = $this->getJson('/api/v1/feed/shop?per_page=10&cursor=' . $meta['next_cursor']);
        $response2->assertOk();

        $items1 = $response1->json('data');
        $items2 = $response2->json('data');

        // No duplicates
        $ids1 = array_column($items1, 'id');
        $ids2 = array_column($items2, 'id');
        $this->assertEmpty(array_intersect($ids1, $ids2), 'Feed pages should not have duplicate items');
    }

    /** @test */
    public function test_feed_includes_banners_when_campaigns_exist(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(15)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        Campaign::factory()->create([
            'sponsor_id' => $sponsor->id,
            'placement_type' => 'shop_horizontal',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/feed/shop?per_page=20');

        $response->assertOk();
        $items = $response->json('data');

        $contentTypes = array_column($items, 'content_type');
        $this->assertContains('banner', $contentTypes, 'Feed should include banner items');
    }

    /** @test */
    public function test_feed_filters_inactive_products(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(3)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);
        SponsorProduct::factory()->count(2)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/feed/shop');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertCount(3, $items, 'Only active products should appear in feed');
    }

    /** @test */
    public function test_feed_filters_inactive_sponsors(): void
    {
        $activeSponsor = Sponsor::factory()->create(['is_active' => true]);
        $inactiveSponsor = Sponsor::factory()->create(['is_active' => false]);

        SponsorProduct::factory()->count(3)->create([
            'sponsor_id' => $activeSponsor->id,
            'is_active' => true,
        ]);
        SponsorProduct::factory()->count(2)->create([
            'sponsor_id' => $inactiveSponsor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/feed/shop');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertCount(3, $items, 'Only products from active sponsors should appear');
    }

    /** @test */
    public function test_home_feed_filters_draft_materials(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(2)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $lesson = Lesson::factory()->create();
        LearningMaterial::factory()->count(2)->create([
            'lesson_id' => $lesson->id,
            'status' => 'published',
        ]);
        LearningMaterial::factory()->count(2)->create([
            'lesson_id' => $lesson->id,
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/feed/home');

        $response->assertOk();
        $items = $response->json('data');

        $materialItems = array_filter($items, fn ($i) => $i['content_type'] === 'material');
        $this->assertCount(2, $materialItems, 'Only published materials should appear');
    }

    /** @test */
    public function test_feed_response_envelope_format(): void
    {
        $response = $this->getJson('/api/v1/feed/shop');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => [
                    'next_cursor',
                    'has_more',
                    'per_page',
                ],
                'message',
            ]);
    }

    /** @test */
    public function test_feed_per_page_parameter(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(30)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/feed/shop?per_page=5');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertCount(5, $items);
        $this->assertEquals(5, $response->json('meta.per_page'));
    }

    /** @test */
    public function test_feed_per_page_max_limit(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(60)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        // Request 100, should be capped at 50
        $response = $this->getJson('/api/v1/feed/shop?per_page=100');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertLessThanOrEqual(50, count($items), 'per_page should be capped at 50');
    }

    /** @test */
    public function test_shop_feed_end_condition(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(10)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/feed/shop?per_page=10');

        $response->assertOk();
        $meta = $response->json('meta');
        $this->assertNull($meta['next_cursor'], 'next_cursor should be null when exactly per_page items');
        $this->assertFalse($meta['has_more'], 'has_more should be false on last page');
    }

    /** @test */
    public function test_home_feed_cursor_pagination(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        $products = SponsorProduct::factory()->count(5)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $lesson = Lesson::factory()->create();
        $materials = LearningMaterial::factory()->count(5)->create([
            'lesson_id' => $lesson->id,
            'status' => 'published',
        ]);

        // First page
        $response1 = $this->getJson('/api/v1/feed/home?per_page=5');
        $response1->assertOk();
        $meta1 = $response1->json('meta');
        $this->assertTrue($meta1['has_more']);
        $this->assertNotNull($meta1['next_cursor']);

        // Second page
        $response2 = $this->getJson('/api/v1/feed/home?per_page=5&cursor=' . $meta1['next_cursor']);
        $response2->assertOk();
        $meta2 = $response2->json('meta');
        $this->assertFalse($meta2['has_more']);
        $this->assertNull($meta2['next_cursor']);

        // No duplicates — compare composite identity (content_type + id), since
        // products and materials live in separate tables with overlapping id ranges.
        $keys1 = array_map(fn ($i) => $i['content_type'] . ':' . $i['id'], $response1->json('data'));
        $keys2 = array_map(fn ($i) => $i['content_type'] . ':' . $i['id'], $response2->json('data'));
        $this->assertEmpty(array_intersect($keys1, $keys2), 'Home feed pages should not have duplicate items');
    }

    /** @test */
    public function test_three_page_sequential_no_duplicate(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(30)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $allIds = [];
        $cursor = null;

        for ($page = 1; $page <= 3; $page++) {
            $url = '/api/v1/feed/shop?per_page=10' . ($cursor ? '&cursor=' . $cursor : '');
            $response = $this->getJson($url);
            $response->assertOk();

            $items = $response->json('data');
            $this->assertCount(10, $items, "Page {$page} should have 10 items");

            $pageIds = array_column($items, 'id');
            $this->assertEmpty(array_intersect($allIds, $pageIds), "Page {$page} should not repeat previous IDs");
            $allIds = array_merge($allIds, $pageIds);

            $cursor = $response->json('meta.next_cursor');
        }

        $this->assertCount(30, array_unique($allIds), 'All 30 items across 3 pages should be unique');
    }

    /** @test */
    public function test_invalid_cursor_returns_graceful_error(): void
    {
        $response = $this->getJson('/api/v1/feed/shop?cursor=invalid_garbage_string');

        // Should not crash; return empty or 400 gracefully
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 400], true),
            'Invalid cursor should return 200 or 400'
        );
    }

    /** @test */
    public function test_home_feed_end_condition(): void
    {
        $sponsor = Sponsor::factory()->create(['is_active' => true]);
        SponsorProduct::factory()->count(3)->create([
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);

        $lesson = Lesson::factory()->create();
        LearningMaterial::factory()->count(2)->create([
            'lesson_id' => $lesson->id,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/feed/home?per_page=10');

        $response->assertOk();
        $meta = $response->json('meta');
        $this->assertNull($meta['next_cursor'], 'next_cursor should be null when total items < per_page');
        $this->assertFalse($meta['has_more'], 'has_more should be false on last page');
    }
}
