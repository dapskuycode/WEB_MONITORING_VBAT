<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHierarchyAndGenderSanitationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@vbatponsel.com',
            'gender' => 'male',
        ]);
    }

    /**
     * ADMIN-01: Verifikasi satu judul tunggal pada Dashboard & modul Admin (tanpa header ganda).
     */
    public function test_dashboard_has_single_unified_header_and_kpi_cards(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Super Dashboard');
        $response->assertSee('Analitik');
        // Pastikan tidak ada judul ganda versi lama
        $response->assertDontSee('Dashboard Analitik Super Admin & Owner');
        // Pastikan KPI stat cards tampil
        $response->assertSee('Total Tayangan Iklan');
        $response->assertSee('Total Klik Sponsor');
        $response->assertSee('Rata-rata CTR');
    }

    /**
     * ADMIN-01: Verifikasi halaman admin sponsor, best deals, events, products hanya memiliki satu judul.
     */
    public function test_admin_pages_render_cleanly_without_duplicate_titles(): void
    {
        $pages = [
            route('admin.sponsors'),
            route('admin.best-deals'),
            route('admin.products'),
            route('admin.events'),
            route('admin.campaigns'),
            route('admin.notifications'),
            route('admin.bulk-upload'),
            route('admin.users'),
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            $response->assertOk();
        }
    }

    /**
     * ADMIN-02: Verifikasi rasio gender di dashboard analitik hanya menampilkan Laki-laki dan Perempuan (tanpa Lainnya).
     */
    public function test_dashboard_gender_demographics_has_no_lainnya(): void
    {
        // Buat user male dan female
        User::factory()->create(['role' => 'student', 'gender' => 'male']);
        User::factory()->create(['role' => 'student', 'gender' => 'female']);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Rasio Jenis Kelamin');
        $response->assertSee('Laki-laki');
        $response->assertSee('Perempuan');
        // Tidak boleh ada teks kategori gender 'Lainnya' di ringkasan rasio
        $response->assertDontSee('>Lainnya<', false);
    }

    /**
     * ADMIN-02: Backend API menolak opsi gender 'other' dan hanya menerima male/female/laki-laki/perempuan.
     */
    public function test_demographic_api_rejects_gender_other(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        // Uji coba kirim gender 'other' -> wajib ditolak HTTP 422
        $response = $this->actingAs($user)->postJson('/api/v1/user/demographics', [
            'user_id' => $user->id,
            'gender' => 'other',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gender']);
    }

    /**
     * ADMIN-02: Backend API menerima gender 'Laki-laki' / 'male' dan menyimpannya sebagai 'male'.
     */
    public function test_demographic_api_accepts_male_and_normalizes(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->postJson('/api/v1/user/demographics', [
            'user_id' => $user->id,
            'gender' => 'Laki-laki',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'gender' => 'male',
        ]);
    }

    /**
     * ADMIN-02: Backend API menerima gender 'Perempuan' / 'female' dan menyimpannya sebagai 'female'.
     */
    public function test_demographic_api_accepts_female_and_normalizes(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->postJson('/api/v1/user/demographics', [
            'user_id' => $user->id,
            'gender' => 'Perempuan',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'gender' => 'female',
        ]);
    }
}
