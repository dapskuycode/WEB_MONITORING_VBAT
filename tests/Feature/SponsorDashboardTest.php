<?php

namespace Tests\Feature;

use App\Models\BenefitCategory;
use App\Models\SponsorProduct;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\User;
use Database\Seeders\SponsorTierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SponsorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SponsorTierSeeder::class);
        $this->admin = User::factory()->create(['role' => 'super_admin']);
    }

    public function test_sponsor_dashboard_renders_cards_and_quota_indicators()
    {
        $goldTier = SponsorTier::where('slug', 'gold')->first();

        $sponsor = Sponsor::create([
            'name' => 'Kopi Kapal Api',
            'slug' => 'kopi-kapal-api',
            'tier' => 'gold',
            'tier_id' => $goldTier->id,
            'weight' => 35,
            'is_active' => true,
        ]);

        SponsorProduct::create([
            'sponsor_id' => $sponsor->id,
            'name' => 'Kopi Spesial Mix',
            'slug' => 'kopi-spesial-mix',
            'price' => 15000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->assertSee('Kopi Kapal Api')
            ->assertSee('Terpakai 1 dari 75'); // Gold initial quota is 75
    }

    public function test_search_and_filters_filter_sponsors_correctly()
    {
        $goldTier = SponsorTier::where('slug', 'gold')->first();
        $silverTier = SponsorTier::where('slug', 'silver')->first();

        Sponsor::create([
            'name' => 'Garuda Food',
            'slug' => 'garuda-food',
            'tier' => 'gold',
            'tier_id' => $goldTier->id,
            'contact_email' => 'contact@garudafood.com',
            'phone' => '0812345678',
            'is_active' => true,
        ]);

        Sponsor::create([
            'name' => 'Indofood Sukses Makmur',
            'slug' => 'indofood',
            'tier' => 'silver',
            'tier_id' => $silverTier->id,
            'contact_email' => 'sales@indofood.co.id',
            'phone' => '0898765432',
            'is_active' => false,
        ]);

        // Search by name
        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->set('search', 'Garuda')
            ->assertSee('Garuda Food')
            ->assertDontSee('Indofood Sukses Makmur');

        // Filter by tier
        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->set('filterTier', 'silver')
            ->assertSee('Indofood Sukses Makmur')
            ->assertDontSee('Garuda Food');

        // Filter by status active only
        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->set('filterStatus', '1')
            ->assertSee('Garuda Food')
            ->assertDontSee('Indofood Sukses Makmur');
    }

    public function test_quick_tier_change_updates_tier_and_auto_derives_weight()
    {
        $bronzeTier = SponsorTier::where('slug', 'bronze')->first();

        $sponsor = Sponsor::create([
            'name' => 'Teh Botol',
            'slug' => 'teh-botol',
            'tier' => 'bronze',
            'tier_id' => $bronzeTier->id,
            'weight' => 0,
            'is_active' => true,
        ]);

        // Change to platinum (Platinum SOV is 50 in seeder)
        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('changeTier', $sponsor->id, 'platinum');

        $sponsor->refresh();
        $this->assertEquals('platinum', $sponsor->tier);
        $this->assertEquals(50, $sponsor->weight);
        $this->assertEquals(150, $sponsor->resolveBenefit('kuota_produk'));
    }

    public function test_toggle_status_switches_active_flag()
    {
        $goldTier = SponsorTier::where('slug', 'gold')->first();

        $sponsor = Sponsor::create([
            'name' => 'Aqua Danone',
            'slug' => 'aqua-danone',
            'tier' => 'gold',
            'tier_id' => $goldTier->id,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('toggleStatus', $sponsor->id);

        $sponsor->refresh();
        $this->assertFalse((bool)$sponsor->is_active);

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('toggleStatus', $sponsor->id);

        $sponsor->refresh();
        $this->assertTrue((bool)$sponsor->is_active);
    }

    public function test_open_detail_modal_shows_profile_and_benefits()
    {
        $diamondTier = SponsorTier::where('slug', 'diamond')->first();

        $sponsor = Sponsor::create([
            'name' => 'Bank Mandiri',
            'slug' => 'bank-mandiri',
            'tier' => 'diamond',
            'tier_id' => $diamondTier->id,
            'description' => 'Official Banking Partner',
            'website_url' => 'https://bankmandiri.co.id',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('openDetailModal', $sponsor->id)
            ->assertSet('showDetailModal', true)
            ->assertSee('Bank Mandiri')
            ->assertSee('Official Banking Partner')
            ->set('detailActiveTab', 'benefits')
            ->assertSee('∞ (Tidak Terbatas)');
    }

    public function test_create_sponsor_auto_sets_weight_from_tier_without_dates()
    {
        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('openCreateModal')
            ->set('name', 'Pertamina')
            ->set('tier', 'gold')
            ->set('createUserAccount', false)
            ->call('save');

        $sponsor = Sponsor::where('name', 'Pertamina')->first();
        $this->assertNotNull($sponsor);
        $this->assertEquals('gold', $sponsor->tier);
        $this->assertEquals(35, $sponsor->weight); // Gold SOV is 35
        $this->assertTrue((bool)$sponsor->is_active);
    }

    public function test_inactive_sponsor_products_are_hidden_from_public_api()
    {
        $goldTier = SponsorTier::where('slug', 'gold')->first();

        $inactiveSponsor = Sponsor::create([
            'name' => 'Inactive Merchant',
            'slug' => 'inactive-merchant',
            'tier' => 'gold',
            'tier_id' => $goldTier->id,
            'is_active' => false,
        ]);

        SponsorProduct::create([
            'sponsor_id' => $inactiveSponsor->id,
            'name' => 'Hidden Product',
            'slug' => 'hidden-product',
            'price' => 20000,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/products');
        $response->assertOk();
        $this->assertFalse(collect($response->json('data'))->contains('name', 'Hidden Product'));
    }

    public function test_admin_can_upload_valid_sponsor_logo()
    {
        Storage::fake('public');

        $goldTier = SponsorTier::where('slug', 'gold')->first();
        $file = UploadedFile::fake()->image('sponsor_logo.png', 300, 300);

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('openCreateModal')
            ->set('name', 'Logo Brand Co')
            ->set('tier', 'gold')
            ->set('logo', $file)
            ->set('createUserAccount', false)
            ->call('save');

        $sponsor = Sponsor::where('name', 'Logo Brand Co')->first();
        $this->assertNotNull($sponsor);
        $this->assertNotNull($sponsor->logo_path);
        Storage::disk('public')->assertExists($sponsor->logo_path);
        $this->assertNotNull($sponsor->logo_url);
        $this->assertStringContainsString('storage/', $sponsor->logo_url);
    }

    public function test_non_image_file_is_rejected_when_uploading_logo()
    {
        Storage::fake('public');

        $pdfFile = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('openCreateModal')
            ->set('name', 'Invalid File Co')
            ->set('tier', 'silver')
            ->set('logo', $pdfFile)
            ->set('createUserAccount', false)
            ->call('save')
            ->assertHasErrors(['logo']);

        $this->assertDatabaseMissing('sponsors', ['name' => 'Invalid File Co']);
    }

    public function test_admin_can_remove_sponsor_logo()
    {
        Storage::fake('public');

        $goldTier = SponsorTier::where('slug', 'gold')->first();
        $sponsor = Sponsor::create([
            'name' => 'Has Logo Co',
            'slug' => 'has-logo-co',
            'tier' => 'gold',
            'tier_id' => $goldTier->id,
            'logo_path' => 'sponsors/logos/old_logo.png',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.sponsor-manager')
            ->call('editSponsor', $sponsor->id)
            ->call('removeLogo')
            ->call('save');

        $sponsor->refresh();
        $this->assertNull($sponsor->logo_path);
    }

    public function test_sponsor_can_upload_logo_from_sponsor_portal_dashboard()
    {
        Storage::fake('public');

        $sponsorUser = User::factory()->create(['role' => 'sponsor']);
        $tier = SponsorTier::where('slug', 'platinum')->first();

        $sponsor = Sponsor::create([
            'user_id' => $sponsorUser->id,
            'name' => 'Mitra Mandiri Portal',
            'slug' => 'mitra-mandiri-portal',
            'tier' => 'platinum',
            'tier_id' => $tier->id,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('portal_logo.png', 400, 400);

        Livewire::actingAs($sponsorUser)
            ->test('sponsor.sponsor-dashboard')
            ->set('showLogoModal', true)
            ->set('logo', $file)
            ->call('saveLogo')
            ->assertSet('showLogoModal', false)
            ->assertSet('logoSuccessMessage', 'Logo resmi sponsor Anda berhasil diperbarui!');

        $sponsor->refresh();
        $this->assertNotNull($sponsor->logo_path);
        Storage::disk('public')->assertExists($sponsor->logo_path);
    }
}
