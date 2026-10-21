<?php

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarRoleSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_shows_only_admin_menus_across_all_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $pages = [
            '/dashboard',
            '/admin/sponsors',
            '/admin/products',
            '/admin/users',
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($admin)->get($url);
            $response->assertStatus(200);

            // Harus ada menu admin
            $response->assertSee('Dashboard Utama');
            $response->assertSee('Manajemen');

            // Tidak boleh ada menu portal mitra sponsor
            $response->assertDontSee('Ajukan Kampanye');
            $response->assertDontSee('Hero Slider');
            $response->assertDontSee('Katalog Produk Saya');
            $response->assertDontSee('Ajukan Hero Slide');
        }
    }

    public function test_sponsor_sidebar_shows_only_sponsor_menus(): void
    {
        $sponsorUser = User::factory()->create([
            'role' => 'sponsor',
        ]);

        Sponsor::create([
            'user_id' => $sponsorUser->id,
            'name' => 'Mitra Tech',
            'slug' => 'mitra-tech',
        ]);

        $response = $this->actingAs($sponsorUser)->get('/sponsor/dashboard');
        $response->assertStatus(200);

        // Harus ada menu sponsor
        $response->assertSee('Hero Slider');
        $response->assertSee('Dashboard Sponsor');

        // Tidak boleh ada menu admin
        $response->assertDontSee('Manajemen');
        $response->assertDontSee('Dashboard Utama');
    }
}
