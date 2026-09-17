<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VbatFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_hero_sliders_returns_success(): void
    {
        $sponsor = Sponsor::create([
            'name' => 'Test Sponsor',
            'slug' => 'test-sponsor',
        ]);

        Campaign::create([
            'sponsor_id' => $sponsor->id,
            'title' => 'Test Campaign',
            'placement_type' => 'hero_slider',
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/banners/hero');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_api_active_discount_event_returns_status(): void
    {
        $response = $this->getJson('/api/v1/shop/events/active');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'has_active_event']);
    }

    public function test_api_tracker_logs_impression_and_clicks(): void
    {
        $sponsor = Sponsor::create([
            'name' => 'Test Sponsor',
            'slug' => 'test-sponsor-2',
        ]);

        $product = SponsorProduct::create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Test Product',
            'price' => 100000,
        ]);

        $response = $this->postJson('/api/v1/track', [
            'event_type' => 'click',
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_authenticated_admin_can_access_admin_panels(): void
    {
        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@vbat.id',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/sponsors')->assertOk();
        $this->actingAs($admin)->get('/admin/products')->assertOk();
        $this->actingAs($admin)->get('/admin/campaigns')->assertOk();
        $this->actingAs($admin)->get('/admin/events')->assertOk();
        $this->actingAs($admin)->get('/admin/bulk-upload')->assertOk();
        $this->actingAs($admin)->get('/admin/notifications')->assertOk();
    }

    public function test_authenticated_sponsor_can_access_sponsor_portal(): void
    {
        $sponsor = Sponsor::create([
            'name' => 'Sponsor Co',
            'slug' => 'sponsor-co',
        ]);

        $sponsorUser = User::create([
            'name' => 'Sponsor Test',
            'email' => 'sponsor_test@vbat.id',
            'password' => 'password',
            'role' => 'sponsor',
        ]);

        $sponsor->update(['user_id' => $sponsorUser->id]);

        $this->actingAs($sponsorUser)->get('/sponsor/campaigns')->assertOk();
    }

    public function test_sponsor_cannot_access_admin_panels(): void
    {
        $sponsorUser = User::create([
            'name' => 'Sponsor Unauthorized',
            'email' => 'sponsor_unauth@vbat.id',
            'password' => 'password',
            'role' => 'sponsor',
        ]);

        $this->actingAs($sponsorUser)->get('/admin/sponsors')->assertStatus(403);
        $this->actingAs($sponsorUser)->get('/admin/products')->assertStatus(403);
        $this->actingAs($sponsorUser)->get('/admin/events')->assertStatus(403);
        $this->actingAs($sponsorUser)->get('/dashboard')->assertStatus(403);
    }

    public function test_student_cannot_access_admin_or_sponsor_panels(): void
    {
        $student = User::create([
            'name' => 'Student Teknisi',
            'email' => 'student_teknisi@vbat.id',
            'password' => 'password',
            'role' => 'student',
        ]);

        $this->actingAs($student)->get('/admin/sponsors')->assertStatus(403);
        $this->actingAs($student)->get('/admin/products')->assertStatus(403);
        $this->actingAs($student)->get('/sponsor/campaigns')->assertStatus(403);
        $this->actingAs($student)->get('/dashboard')->assertStatus(403);
    }
}
