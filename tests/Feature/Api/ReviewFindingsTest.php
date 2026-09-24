<?php

namespace Tests\Feature\Api;

use App\Models\AdminNotification;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialView;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReviewFindingsTest extends TestCase
{
    use RefreshDatabase;

    // ─── CRITICAL #1: Authentication on Core API Routes ─────────────────

    #[Test]
    public function guest_cannot_create_product(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'price' => 100000,
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function guest_cannot_update_product(): void
    {
        $product = SponsorProduct::factory()->create();

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function guest_cannot_delete_product(): void
    {
        $product = SponsorProduct::factory()->create();

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(401);
    }

    #[Test]
    public function guest_cannot_create_learning_material(): void
    {
        $lesson = \App\Models\Lesson::factory()->create();

        $response = $this->postJson('/api/v1/learning-materials', [
            'lesson_id' => $lesson->id,
            'unit_code' => 'TEST-001',
            'unit_title' => 'Test Unit',
            'material_type' => 'text_content',
            'title' => 'Test Material',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function guest_cannot_update_learning_material(): void
    {
        $material = LearningMaterial::factory()->create();

        $response = $this->putJson("/api/v1/learning-materials/{$material->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function guest_cannot_delete_learning_material(): void
    {
        $material = LearningMaterial::factory()->create();

        $response = $this->deleteJson("/api/v1/learning-materials/{$material->id}");

        $response->assertStatus(401);
    }

    // ─── CRITICAL #2: Client-Side Ownership Injection (recordProgress) ──

    #[Test]
    public function record_progress_uses_auth_user_not_payload(): void
    {
        $userA = User::factory()->create(['role' => 'student']);
        $userB = User::factory()->create(['role' => 'student']);
        $material = LearningMaterial::factory()->create();

        // Act as userA, but try to send user_id of userB in payload
        $response = $this->actingAs($userA)->postJson(
            "/api/v1/learning-materials/{$material->id}/progress",
            [
                'progress_percent' => 50,
                // user_id is NO LONGER accepted from payload
            ]
        );

        $response->assertStatus(200);

        // Verify the view was created for userA, NOT userB
        $this->assertDatabaseHas('learning_material_views', [
            'user_id' => $userA->id,
            'learning_material_id' => $material->id,
            'progress_percent' => 50,
        ]);

        $this->assertDatabaseMissing('learning_material_views', [
            'user_id' => $userB->id,
            'learning_material_id' => $material->id,
        ]);
    }

    #[Test]
    public function record_progress_requires_authentication(): void
    {
        $material = LearningMaterial::factory()->create();

        $response = $this->postJson("/api/v1/learning-materials/{$material->id}/progress", [
            'progress_percent' => 50,
        ]);

        $response->assertStatus(401);
    }

    // ─── CRITICAL #3: Cross-User Isolation (Sponsor Products) ──────────────

    #[Test]
    public function sponsor_can_only_create_products_for_own_account(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'sponsor']);
        $sponsor = Sponsor::factory()->create(['user_id' => $sponsorUser->id]);

        $otherSponsor = Sponsor::factory()->create(); // different sponsor

        $response = $this->actingAs($sponsorUser)->postJson('/api/v1/products', [
            'name' => 'Hijacked Product',
            'price' => 99999,
            'shopee_url' => 'https://shopee.co.id/test-item',
            'sponsor_id' => $otherSponsor->id, // trying to inject other sponsor's ID
        ]);

        $response->assertStatus(201);

        // Verify product was created under the authenticated user's sponsor, NOT the injected one
        $this->assertDatabaseHas('sponsor_products', [
            'name' => 'Hijacked Product',
            'sponsor_id' => $sponsor->id, // forced to auth user's sponsor
        ]);

        $this->assertDatabaseMissing('sponsor_products', [
            'name' => 'Hijacked Product',
            'sponsor_id' => $otherSponsor->id,
        ]);
    }

    #[Test]
    public function sponsor_cannot_update_another_sponsors_product(): void
    {
        $sponsorUserA = User::factory()->create(['role' => 'sponsor']);
        $sponsorA = Sponsor::factory()->create(['user_id' => $sponsorUserA->id]);
        $productA = SponsorProduct::factory()->create(['sponsor_id' => $sponsorA->id]);

        $sponsorUserB = User::factory()->create(['role' => 'sponsor']);

        $response = $this->actingAs($sponsorUserB)->putJson(
            "/api/v1/products/{$productA->id}",
            ['name' => 'Stolen Product']
        );

        $response->assertStatus(403);
    }

    #[Test]
    public function sponsor_cannot_delete_another_sponsors_product(): void
    {
        $sponsorUserA = User::factory()->create(['role' => 'sponsor']);
        $sponsorA = Sponsor::factory()->create(['user_id' => $sponsorUserA->id]);
        $productA = SponsorProduct::factory()->create(['sponsor_id' => $sponsorA->id]);

        $sponsorUserB = User::factory()->create(['role' => 'sponsor']);

        $response = $this->actingAs($sponsorUserB)->deleteJson(
            "/api/v1/products/{$productA->id}"
        );

        $response->assertStatus(403);
        $this->assertModelExists($productA); // still exists
    }

    #[Test]
    public function super_admin_can_create_product_for_any_sponsor(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $targetSponsor = Sponsor::factory()->create();

        $response = $this->actingAs($admin)->postJson('/api/v1/products', [
            'name' => 'Admin-Created Product',
            'price' => 50000,
            'shopee_url' => 'https://shopee.co.id/admin-item',
            'sponsor_id' => $targetSponsor->id, // admin can specify any sponsor
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sponsor_products', [
            'name' => 'Admin-Created Product',
            'sponsor_id' => $targetSponsor->id,
        ]);
    }

    // ─── MAJOR #2: Marketplace Domain Validation ────────────────────────

    #[Test]
    public function rejects_non_shopee_tokopedia_url(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'sponsor']);
        Sponsor::factory()->create(['user_id' => $sponsorUser->id]);

        $response = $this->actingAs($sponsorUser)->postJson('/api/v1/products', [
            'name' => 'Phishing Product',
            'price' => 0,
            'shopee_url' => 'https://evil-phishing-site.com/fake-product',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['shopee_url']);
    }

    #[Test]
    public function accepts_valid_shopee_url(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'sponsor']);
        Sponsor::factory()->create(['user_id' => $sponsorUser->id]);

        $response = $this->actingAs($sponsorUser)->postJson('/api/v1/products', [
            'name' => 'Valid Shopee Product',
            'price' => 150000,
            'shopee_url' => 'https://shopee.co.id/valid-item-123',
        ]);

        $response->assertStatus(201);
    }

    #[Test]
    public function accepts_valid_tokopedia_url(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'sponsor']);
        Sponsor::factory()->create(['user_id' => $sponsorUser->id]);

        $response = $this->actingAs($sponsorUser)->postJson('/api/v1/products', [
            'name' => 'Valid Tokopedia Product',
            'price' => 250000,
            'tokopedia_url' => 'https://www.tokopedia.com/shop/item-456',
        ]);

        $response->assertStatus(201);
    }

    // ─── MINOR #1: Route Grouping (Admin routes protected) ──────────────

    #[Test]
    public function guest_cannot_access_admin_endpoints(): void
    {
        $endpoints = [
            '/api/v1/admin/notifications',
            '/api/v1/admin/analytics/dashboard?from=2026-01-01&to=2026-12-31',
            '/api/v1/admin/audit-logs',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)->assertStatus(401);
        }
    }

    #[Test]
    public function non_admin_cannot_access_admin_endpoints(): void
    {
        $sponsorUser = User::factory()->create(['role' => 'sponsor']);

        $response = $this->actingAs($sponsorUser)
            ->getJson('/api/v1/admin/notifications');

        $response->assertStatus(403);
    }
}
