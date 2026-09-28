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
            $response->assertSee('Admin &amp; Owner VBAT', false);
            $response->assertSee('Manajemen Sponsor');
            $response->assertSee('Daftar Akun User');
            $response->assertSee('Super Dashboard');

            // Tidak boleh ada menu portal mitra sponsor
            $response->assertDontSee('Portal Mitra Sponsor');
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
        $response->assertSee('Portal Mitra Sponsor');
        $response->assertSee('Dashboard Sponsor');
        $response->assertSee('Katalog Produk Saya');
        $response->assertSee('Ajukan Hero Slide');

        // Tidak boleh ada menu admin
        $response->assertDontSee('Admin &amp; Owner VBAT', false);
        $response->assertDontSee('Manajemen Sponsor');
        $response->assertDontSee('Daftar Akun User');
        $response->assertDontSee('Super Dashboard');
    }
}
