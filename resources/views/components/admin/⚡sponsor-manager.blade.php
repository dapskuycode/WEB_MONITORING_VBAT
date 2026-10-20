<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\BenefitCategory;
use App\Models\TierBenefit;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $activeTab = 'sponsors'; // 'sponsors' | 'matrix'

    // Search & Filters (SPONSOR-02)
    public $search = '';
    public $filterTier = '';
    public $filterStatus = '';

    // Sponsors list & form state
    public $showModal = false;
    public $editingId = null;

    public $name = '';
    public $tier = 'gold';
    public $website_url = '';
    public $marketplace_url = '';
    public $contact_email = '';
    public $phone = '';
    public $whatsapp = '';
    public $description = '';
    public $is_active = true;

    // Logo Upload (SPONSOR-03)
    public $logo;
    public $existingLogoPath = null;

    // Gambar Header (WR-02)
    public $headerImage;
    public $existingHeaderImagePath = null;

    public $createUserAccount = true;
    public $userEmail = '';
    public $userPassword = '';

    // Detail modal state
    public $showDetailModal = false;
    public $detailSponsorId = null;
    public $detailActiveTab = 'profile'; // 'profile' | 'benefits' | 'products'

    // Matrix state (SPONSOR-01)
    public $matrixTiers = [];
    public $matrixCategories = [];
    public $matrix = [];

    public $showResetConfirmModal = false;
    public $resetType = 'row';
    public $resetTargetCategorySlug = null;
    public $resetDiff = [];
    public $saveSuccessMessage = '';

    // TIER-01: pengelolaan tier (nama, label badge, warna, urutan).
    public $showTierModal = false;
    public $tierEditingId = null;
    public $tierName = '';
    public $tierBadgeLabel = '';
    public $tierBadgeColor = '#1B4F9B';
    public $tierSortOrder = 0;
    public $tierMessage = '';

    public function mount()
    {
        $this->loadMatrix();
    }

    /** TIER-01: buka form tier untuk tambah atau ubah. */
    public function openTierModal($tierId = null)
    {
        $this->tierEditingId = $tierId;
        $this->tierMessage = '';

        if ($tierId) {
            $tier = SponsorTier::findOrFail($tierId);
            $this->tierName = $tier->name;
            $this->tierBadgeLabel = $tier->badge_label;
            $this->tierBadgeColor = $tier->badge_color ?? '#1B4F9B';
            $this->tierSortOrder = $tier->sort_order;
        } else {
            $this->reset(['tierName', 'tierBadgeLabel', 'tierSortOrder']);
            $this->tierBadgeColor = '#1B4F9B';
            $this->tierSortOrder = (int) SponsorTier::max('sort_order') + 10;
        }

        $this->showTierModal = true;
    }

    /** TIER-01: simpan tier baru atau perubahan tier. */
    public function saveTier()
    {
        $this->validate([
            'tierName' => 'required|string|max:60',
            'tierBadgeLabel' => 'nullable|string|max:60',
            'tierBadgeColor' => 'nullable|string|max:20',
            'tierSortOrder' => 'required|integer|min:0',
        ]);

        if ($this->tierEditingId) {
            SponsorTier::findOrFail($this->tierEditingId)->update([
                'name' => $this->tierName,
                'badge_label' => $this->tierBadgeLabel ?? '',
                'badge_color' => $this->tierBadgeColor,
                'sort_order' => $this->tierSortOrder,
            ]);
            $this->tierMessage = 'Tier berhasil diperbarui.';
        } else {
            $slug = \Illuminate\Support\Str::slug($this->tierName);
            if (SponsorTier::where('slug', $slug)->exists()) {
                $this->addError('tierName', 'Nama tier ini sudah dipakai. Gunakan nama lain.');
                return;
            }
            SponsorTier::create([
                'slug' => $slug,
                'name' => $this->tierName,
                'badge_label' => $this->tierBadgeLabel ?? '',
                'badge_color' => $this->tierBadgeColor,
                'sort_order' => $this->tierSortOrder,
                'is_active' => true,
            ]);
            $this->tierMessage = 'Tier baru berhasil ditambahkan.';
        }

        $this->showTierModal = false;
        $this->loadMatrix();
    }

    /**
     * TIER-01: hapus tier.
     *
     * Tier TIDAK boleh dihapus selama masih ada sponsor di dalamnya. Admin harus
     * memindahkan sponsor ke tier lain (misalnya Free) lebih dulu.
     */
    public function deleteTier($tierId)
    {
        $tier = SponsorTier::findOrFail($tierId);

        if ($tier->slug === 'free') {
            $this->tierMessage = 'Tier Free tidak dapat dihapus karena menjadi tempat penampung sponsor.';
            return;
        }

        $jumlah = Sponsor::where('tier_id', $tier->id)->count();
        if ($jumlah > 0) {
            $this->tierMessage = "Tier {$tier->name} tidak dapat dihapus karena masih ada {$jumlah} sponsor. Pindahkan sponsor tersebut ke tier lain terlebih dahulu.";
            return;
        }

        $tier->delete();
        $this->tierMessage = "Tier {$tier->name} berhasil dihapus.";
        $this->loadMatrix();
    }

    public function loadMatrix()
    {
        $tiers = SponsorTier::orderBy('sort_order')->get();
        // BEN-02: outbound marketplace dikeluarkan dari matriks. Semua tier
        // mendapatkannya, jadi tidak perlu dijadikan baris yang bisa diubah.
        // Nilai di database tetap dipertahankan.
        $categories = BenefitCategory::orderBy('sort_order')
            ->where('slug', '!=', 'outbound_marketplace')
            ->get();
        $tierBenefits = TierBenefit::all();

        $this->matrixTiers = $tiers->map(fn($t) => [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->name,
            'badge_label' => $t->badge_label,
            // TIER-01: warna ikut dikirim supaya Admin dapat mengubahnya.
            'badge_color' => $t->badge_color ?? '#1B4F9B',
            'sort_order' => $t->sort_order,
        ])->toArray();

        // BEN-03: setiap baris diberi kelompok supaya Admin awam langsung
        // memahami urutan dan hubungan antar benefit.
        $groupMap = [
            'kuota_produk' => 'Etalase Produk',
            'storefront_khusus' => 'Etalase Produk',
            'best_deal_slot' => 'Best Deal',
            'best_deal_durasi' => 'Best Deal',
            'best_deal_prioritas' => 'Best Deal',
            'hero_slider' => 'Banner',
            'hero_posisi' => 'Banner',
            'popup_cta' => 'Banner',
            'infeed_banner' => 'Banner',
            'infeed_jarak' => 'Banner',
            'share_of_voice' => 'Frekuensi Tayang',
            'push_broadcast' => 'Komunikasi',
            'badge' => 'Identitas',
        ];

        $this->matrixCategories = $categories->map(fn($c) => [
            'id' => $c->id,
            'slug' => $c->slug,
            'name' => $c->name,
            'description' => $c->description,
            'value_type' => $c->value_type,
            'group' => $groupMap[$c->slug] ?? 'Lainnya',
        ])->toArray();

        $matrixData = [];
        foreach ($categories as $cat) {
            $matrixData[$cat->slug] = [];
            foreach ($tiers as $tier) {
                $tb = $tierBenefits->where('tier_id', $tier->id)
                    ->where('benefit_category_id', $cat->id)
                    ->first();

                $val = $tb ? $tb->value : null;
                $initVal = $tb && $tb->initial_value !== null ? $tb->initial_value : $val;
                $isUnlimited = $tb ? (bool)$tb->is_unlimited : false;
                $isDisabled = $tb ? (bool)$tb->is_disabled : false;

                $matrixData[$cat->slug][$tier->slug] = [
                    'id' => $tb?->id,
                    'tier_id' => $tier->id,
                    'category_id' => $cat->id,
                    'value' => $val,
                    'initial_value' => $initVal,
                    'is_unlimited' => $isUnlimited,
                    'is_disabled' => $isDisabled,
                    'last_number' => $isUnlimited ? ($initVal ?? 10) : ($val ?? 0),
                    'value_type' => $cat->value_type,
                ];
            }
        }

        $this->matrix = $matrixData;
    }

    public function getSponsorsProperty()
    {
        return Sponsor::with(['user', 'sponsorTier', 'products'])
            ->withCount('products', 'campaigns')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterTier, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('tier', strtolower($this->filterTier))
                        ->orWhereHas('sponsorTier', fn($st) => $st->where('slug', strtolower($this->filterTier)));
                });
            })
            ->when($this->filterStatus !== '', function ($q) {
                $q->where('is_active', (bool)$this->filterStatus);
            })
            ->orderByDesc('weight')
            ->get();
    }

    public function changeTier($sponsorId, $newTierSlug)
    {
        $sponsor = Sponsor::findOrFail($sponsorId);
        $tier = SponsorTier::where('slug', $newTierSlug)->firstOrFail();
        
        // Auto-derive weight from tier Share of Voice (SPONSOR-04)
        $sov = TierBenefit::where('tier_id', $tier->id)
            ->whereHas('benefitCategory', fn($q) => $q->where('slug', 'share_of_voice'))
            ->value('value') ?? 0;

        $sponsor->update([
            'tier' => $newTierSlug,
            'tier_id' => $tier->id,
            'weight' => (int)$sov,
        ]);

        $this->saveSuccessMessage = "Tier untuk '{$sponsor->name}' berhasil diubah ke {$tier->name}. Batas kuota dan bobot tayang langsung mengikuti tier baru!";
    }

    public function toggleStatus($id)
    {
        $s = Sponsor::findOrFail($id);
        $s->is_active = !$s->is_active;
        $s->save();
        $this->saveSuccessMessage = "Status mitra '{$s->name}' berhasil diubah menjadi " . ($s->is_active ? 'Aktif' : 'Nonaktif') . ".";
    }

    public function openDetailModal($id)
    {
        $this->detailSponsorId = $id;
        $this->detailActiveTab = 'profile';
        $this->showDetailModal = true;
    }

    public function getDetailSponsorProperty()
    {
        if (!$this->detailSponsorId) return null;
        return Sponsor::with(['sponsorTier', 'user', 'products', 'campaigns'])->find($this->detailSponsorId);
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'tier', 'website_url', 'marketplace_url', 'contact_email', 'phone', 'whatsapp', 'description', 'userEmail', 'userPassword', 'logo', 'existingLogoPath', 'headerImage', 'existingHeaderImagePath']);
        $this->tier = 'gold';
        $this->is_active = true;
        $this->createUserAccount = true;
        $this->showModal = true;
    }

    public function editSponsor($id)
    {
        $sponsor = Sponsor::findOrFail($id);
        $this->editingId = $sponsor->id;
        $this->name = $sponsor->name;
        $this->tier = strtolower($sponsor->tier ?? 'gold');
        $this->website_url = $sponsor->website_url;
        $this->marketplace_url = $sponsor->marketplace_url;
        $this->contact_email = $sponsor->contact_email;
        $this->phone = $sponsor->phone;
        $this->whatsapp = $sponsor->whatsapp;
        $this->description = $sponsor->description;
        $this->is_active = (bool)$sponsor->is_active;
        $this->existingLogoPath = $sponsor->logo_path;
        $this->logo = null;
        $this->existingHeaderImagePath = $sponsor->header_image_url;
        $this->headerImage = null;
        $this->createUserAccount = false;
        $this->showModal = true;
    }

    public function removeLogo()
    {
        $this->logo = null;
        $this->existingLogoPath = null;
    }

    /** WR-02: hapus gambar header sponsor. */
    public function removeHeaderImage()
    {
        $this->headerImage = null;
        $this->existingHeaderImagePath = null;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'tier' => 'required|in:kontribusi,bronze,silver,gold,platinum,diamond',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'headerImage' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'marketplace_url' => 'nullable|url',
        ], [
            'logo.image' => 'Berkas harus berupa gambar valid (JPG, PNG, atau WEBP). Dokumen non-gambar seperti PDF tidak diizinkan.',
            'logo.mimes' => 'Format file yang diperbolehkan hanya JPG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran berkas logo maksimal 2MB.',
            'headerImage.image' => 'Gambar header harus berupa berkas gambar.',
            'headerImage.mimes' => 'Format gambar header hanya JPG, PNG, atau WEBP.',
            'headerImage.max' => 'Ukuran gambar header maksimal 4MB.',
            'marketplace_url.url' => 'Format tautan marketplace tidak valid.',
        ]);

        $tierModel = SponsorTier::where('slug', $this->tier)->first();
        $tierId = $tierModel?->id;
        $sov = $tierModel ? (TierBenefit::where('tier_id', $tierId)->whereHas('benefitCategory', fn($q) => $q->where('slug', 'share_of_voice'))->value('value') ?? 0) : 0;

        $userId = null;
        if ($this->createUserAccount && !empty($this->userEmail) && !empty($this->userPassword)) {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->userEmail,
                'password' => Hash::make($this->userPassword),
                'role' => 'sponsor',
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
            ]);
            $userId = $user->id;
        }

        $logoPath = $this->existingLogoPath;
        if ($this->logo) {
            $logoPath = $this->logo->store('sponsors/logos', 'public');
        }

        // WR-02: gambar header sponsor.
        $headerPath = $this->existingHeaderImagePath;
        if ($this->headerImage) {
            $headerPath = $this->headerImage->store('sponsors/headers', 'public');
        }

        if ($this->editingId) {
            $sponsor = Sponsor::findOrFail($this->editingId);
            $sponsor->update([
                'name' => $this->name,
                'tier' => $this->tier,
                'tier_id' => $tierId,
                'weight' => (int)$sov,
                'logo_path' => $logoPath,
                'header_image_url' => $headerPath,
                // WM-01: Website dan Marketplace diisi terpisah.
                'website_url' => $this->website_url,
                'marketplace_url' => $this->marketplace_url,
                'contact_email' => $this->contact_email,
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
        } else {
            Sponsor::create([
                'user_id' => $userId,
                'name' => $this->name,
                'slug' => Str::slug($this->name) . '-' . rand(100, 999),
                'tier' => $this->tier,
                'tier_id' => $tierId,
                'weight' => (int)$sov,
                'logo_path' => $logoPath,
                'header_image_url' => $headerPath,
                // WM-01: Website dan Marketplace diisi terpisah.
                'website_url' => $this->website_url,
                'marketplace_url' => $this->marketplace_url,
                'contact_email' => $this->contact_email,
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]);
        }

        $this->showModal = false;
        $this->saveSuccessMessage = "Data mitra sponsor berhasil disimpan!";
    }

    public function deleteSponsor($id)
    {
        Sponsor::destroy($id);
        $this->saveSuccessMessage = "Mitra sponsor berhasil dihapus.";
    }

    // Matrix Methods (SPONSOR-01)
    public function toggleUnlimited($catSlug, $tierSlug)
    {
        if (!isset($this->matrix[$catSlug][$tierSlug])) return;

        $cell = &$this->matrix[$catSlug][$tierSlug];
        $cell['is_unlimited'] = !$cell['is_unlimited'];

        if ($cell['is_unlimited']) {
            $cell['last_number'] = $cell['value'] ?? $cell['last_number'] ?? 10;
            $cell['value'] = null;
        } else {
            $cell['value'] = $cell['last_number'] ?? $cell['initial_value'] ?? 10;
        }
    }

    public function promptResetRow($catSlug)
    {
        $this->resetType = 'row';
        $this->resetTargetCategorySlug = $catSlug;
        $this->resetDiff = [];

        $catName = collect($this->matrixCategories)->firstWhere('slug', $catSlug)['name'] ?? $catSlug;

        foreach ($this->matrixTiers as $t) {
            $cell = $this->matrix[$catSlug][$t['slug']] ?? null;
            if (!$cell) continue;

            $currentDisplay = $cell['is_disabled'] ? '—' : ($cell['is_unlimited'] ? '∞ (Tidak terbatas)' : (string)($cell['value'] ?? '0'));
            $initialDisplay = $cell['is_disabled'] ? '—' : (is_null($cell['initial_value']) && $cell['is_unlimited'] ? '∞ (Tidak terbatas)' : (string)($cell['initial_value'] ?? '0'));

            if ($currentDisplay !== $initialDisplay) {
                $this->resetDiff[] = [
                    'category_name' => $catName,
                    'tier_name' => $t['name'],
                    'current' => $currentDisplay,
                    'initial' => $initialDisplay,
                ];
            }
        }

        if (empty($this->resetDiff)) {
            $this->resetDiff[] = [
                'category_name' => $catName,
                'tier_name' => 'Semua Tier',
                'current' => '(Sudah sesuai nilai awal)',
                'initial' => 'Tetap',
            ];
        }

        $this->showResetConfirmModal = true;
    }

    public function promptResetAll()
    {
        $this->resetType = 'all';
        $this->resetTargetCategorySlug = null;
        $this->resetDiff = [];

        foreach ($this->matrixCategories as $cat) {
            foreach ($this->matrixTiers as $t) {
                $cell = $this->matrix[$cat['slug']][$t['slug']] ?? null;
                if (!$cell) continue;

                $currentDisplay = $cell['is_disabled'] ? '—' : ($cell['is_unlimited'] ? '∞' : (string)($cell['value'] ?? '0'));
                $initialDisplay = $cell['is_disabled'] ? '—' : (is_null($cell['initial_value']) && $cell['is_unlimited'] ? '∞' : (string)($cell['initial_value'] ?? '0'));

                if ($currentDisplay !== $initialDisplay) {
                    $this->resetDiff[] = [
                        'category_name' => $cat['name'],
                        'tier_name' => $t['name'],
                        'current' => $currentDisplay,
                        'initial' => $initialDisplay,
                    ];
                }
            }
        }

        if (empty($this->resetDiff)) {
            $this->resetDiff[] = [
                'category_name' => 'Semua Kategori',
                'tier_name' => 'Semua Tier',
                'current' => '(Sudah sesuai nilai awal)',
                'initial' => 'Tetap',
            ];
        }

        $this->showResetConfirmModal = true;
    }

    public function confirmReset()
    {
        if ($this->resetType === 'row' && $this->resetTargetCategorySlug) {
            $catSlug = $this->resetTargetCategorySlug;
            foreach ($this->matrixTiers as $t) {
                if (isset($this->matrix[$catSlug][$t['slug']])) {
                    $cell = &$this->matrix[$catSlug][$t['slug']];
                    $cell['value'] = $cell['initial_value'];
                    $cell['is_unlimited'] = is_null($cell['initial_value']) && in_array($catSlug, ['kuota_produk', 'best_deal_slot', 'push_broadcast']) && $t['slug'] === 'diamond';
                    if ($cell['is_unlimited']) {
                        $cell['value'] = null;
                    }
                }
            }
            $this->saveSuccessMessage = "Baris benefit berhasil dikembalikan ke nilai awal (belum disimpan).";
        } elseif ($this->resetType === 'all') {
            foreach ($this->matrixCategories as $cat) {
                foreach ($this->matrixTiers as $t) {
                    if (isset($this->matrix[$cat['slug']][$t['slug']])) {
                        $cell = &$this->matrix[$cat['slug']][$t['slug']];
                        $cell['value'] = $cell['initial_value'];
                        $cell['is_unlimited'] = is_null($cell['initial_value']) && in_array($cat['slug'], ['kuota_produk', 'best_deal_slot', 'push_broadcast']) && $t['slug'] === 'diamond';
                        if ($cell['is_unlimited']) {
                            $cell['value'] = null;
                        }
                    }
                }
            }
            $this->saveSuccessMessage = "Seluruh tabel matriks berhasil dikembalikan ke nilai awal (belum disimpan).";
        }

        $this->showResetConfirmModal = false;
    }

    public function saveMatrix()
    {
        foreach ($this->matrix as $catSlug => $tiersData) {
            foreach ($tiersData as $tierSlug => $cell) {
                if (!isset($cell['tier_id']) || !isset($cell['category_id'])) continue;

                $val = $cell['value'];
                if ($cell['is_unlimited']) {
                    $val = null;
                }

                TierBenefit::updateOrCreate(
                    [
                        'tier_id' => $cell['tier_id'],
                        'benefit_category_id' => $cell['category_id'],
                    ],
                    [
                        'value' => $val,
                        'is_unlimited' => (bool)$cell['is_unlimited'],
                        'is_disabled' => (bool)$cell['is_disabled'],
                        'label' => $cell['is_unlimited'] ? 'unlimited' : ($cell['is_disabled'] ? '—' : null),
                    ]
                );
            }
        }

        $this->saveSuccessMessage = "Matriks benefit ke-6 tier berhasil disimpan permanen ke database dan langsung aktif!";
        $this->loadMatrix();
    }
};
?>

<div class="space-y-6">
    <!-- Unified Action/Tab Bar: 4 Inline Items -->
    <div class="border-b border-slate-200 dark:border-zinc-800 -mt-2">
        <nav class="flex items-center gap-1 sm:gap-2 overflow-x-auto no-scrollbar">
            <!-- 1. DAFTAR SPONSOR (Active tab) -->
            <button 
                type="button"
                wire:click="$set('activeTab', 'sponsors')" 
                class="inline-flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold uppercase tracking-wider transition-all border-b-2 cursor-pointer {{ $activeTab === 'sponsors' ? 'border-[#1a68f0] text-[#1a68f0] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <span>DAFTAR SPONSOR</span>
                <span class="text-[11px] px-2 py-0.5 rounded-full font-semibold {{ $activeTab === 'sponsors' ? 'bg-blue-50 text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                    {{ count($this->sponsors) }}
                </span>
            </button>

            <!-- 2. KELOLA BENEFIT -->
            <button 
                type="button"
                wire:click="$set('activeTab', 'matrix')" 
                class="inline-flex items-center gap-2 py-3.5 px-4 text-xs sm:text-sm font-bold uppercase tracking-wider transition-all border-b-2 cursor-pointer {{ $activeTab === 'matrix' ? 'border-[#1a68f0] text-[#1a68f0] dark:text-blue-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <span>KELOLA BENEFIT</span>
            </button>

            <!-- 3. TAMBAH TIER (Inactive secondary button tab) -->
            <button 
                type="button"
                wire:click="openTierModal" 
                class="inline-flex items-center gap-1.5 py-3.5 px-4 text-xs sm:text-sm font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                title="Tambah atau kelola tingkatan tier sponsor"
            >
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>TAMBAH TIER</span>
            </button>

            <!-- 4. TAMBAH SPONSOR BARU (Inactive secondary button tab) -->
            <button 
                type="button"
                wire:click="openCreateModal" 
                class="inline-flex items-center gap-1.5 py-3.5 px-4 text-xs sm:text-sm font-bold uppercase tracking-wider border-b-2 border-transparent text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors cursor-pointer"
                title="Daftarkan mitra sponsor baru"
            >
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                </svg>
                <span>TAMBAH SPONSOR BARU</span>
            </button>
        </nav>
    </div>

    <!-- TAB 1: DAFTAR SPONSOR (Cards & Filters) -->
    @if ($activeTab === 'sponsors')
        <div class="space-y-6">
            <!-- Search & Filter: Clean, full-width white bar -->
            <div class="p-3.5 sm:p-4 bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-[0_2px_12px_-3px_rgba(0,0,0,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Search Input with Magnifying Glass -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.250ms="search" 
                        placeholder="Cari nama sponsor..." 
                        class="w-full pl-10 pr-4 py-2 text-sm text-[#212121] dark:text-zinc-100 placeholder-slate-400 bg-slate-50/70 dark:bg-zinc-800/60 rounded-lg border border-slate-200 dark:border-zinc-700 focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all"
                    >
                </div>

                <!-- Two Clean Dropdown Filters ("Semua Tier", "Semua Status") -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <select 
                        wire:model.live="filterTier" 
                        class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-200 focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer shadow-xs transition"
                    >
                        <option value="">Semua Tier</option>
                        <option value="diamond">Diamond</option>
                        <option value="platinum">Platinum</option>
                        <option value="gold">Gold</option>
                        <option value="silver">Silver</option>
                        <option value="bronze">Bronze</option>
                        <option value="kontribusi">Kontribusi</option>
                    </select>

                    <select 
                        wire:model.live="filterStatus" 
                        class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-200 focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer shadow-xs transition"
                    >
                        <option value="">Semua Status</option>
                        <option value="1">Aktif Saja</option>
                        <option value="0">Nonaktif Saja</option>
                    </select>
                </div>
            </div>

            <!-- Sponsor Cards Grid: Clean white cards with very soft, subtle drop shadows (border-radius: 12px) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @forelse ($this->sponsors as $sponsor)
                    @php
                        $tSlug = strtolower($sponsor->tier ?? 'kontribusi');
                        $maxQuota = $sponsor->resolveBenefit('kuota_produk');
                        $usedCount = $sponsor->products_count ?? 0;
                        $isUnlimited = is_null($maxQuota);
                        $quotaPercent = (!$isUnlimited && $maxQuota > 0) ? min(100, round(($usedCount / $maxQuota) * 100)) : 0;
                        $isOverQuota = (!$isUnlimited && $usedCount >= $maxQuota);
                    @endphp

                    <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/70 dark:border-zinc-800 p-5 shadow-[0_4px_16px_-4px_rgba(20,40,90,0.04)] hover:shadow-[0_8px_24px_-6px_rgba(20,40,90,0.08)] transition-all duration-200 flex flex-col justify-between space-y-4">
                        <!-- Card Header: Sponsor Avatar/Logo, Sponsor Name (Dark text), Clean Status Badge -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <!-- Sponsor Avatar/Logo -->
                                <div class="w-12 h-12 rounded-[10px] bg-slate-50 dark:bg-zinc-800 border border-slate-100 dark:border-zinc-700 flex items-center justify-center overflow-hidden shrink-0">
                                    @if ($sponsor->logo_path)
                                        <img src="{{ asset('storage/' . $sponsor->logo_path) }}" alt="{{ $sponsor->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="font-bold text-sm text-[#1a68f0] uppercase tracking-wider">{{ substr($sponsor->name, 0, 2) }}</span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <!-- Sponsor Name (Dark text) -->
                                    <h3 class="font-bold text-[#212121] dark:text-zinc-100 text-base leading-snug truncate" title="{{ $sponsor->name }}">
                                        {{ $sponsor->name }}
                                    </h3>
                                    @if ($sponsor->website_url)
                                        <a href="{{ $sponsor->website_url }}" target="_blank" class="text-xs text-[#1a68f0] hover:underline flex items-center gap-1 mt-0.5 truncate max-w-[170px]">
                                            <span>{{ parse_url($sponsor->website_url, PHP_URL_HOST) ?? $sponsor->website_url }}</span>
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <p class="text-xs text-[#828282] mt-0.5">Mitra Sponsor</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Clean Status Badge: pastel green background, dark green text for 'Aktif' -->
                            <button 
                                type="button" 
                                wire:click="toggleStatus({{ $sponsor->id }})" 
                                title="Klik untuk ubah status aktif/nonaktif" 
                                class="px-2.5 py-1 text-xs font-semibold rounded-full border transition-colors flex items-center gap-1.5 shrink-0 cursor-pointer {{ $sponsor->is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700' }}"
                            >
                                <i class="fas fa-circle {{ $sponsor->is_active ? 'text-emerald-600' : 'text-slate-400' }} shrink-0"></i>
                                <span>{{ $sponsor->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>
                        </div>

                        <!-- Tier Dropdown: A clean, minimal select input showing current tier without heavy borders -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-semibold text-[#828282] uppercase tracking-wider">Tier Sponsor</label>
                            <div class="relative">
                                <select 
                                    wire:change="changeTier({{ $sponsor->id }}, $event.target.value)" 
                                    class="w-full pl-3 pr-8 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-zinc-700/80 bg-slate-50/60 dark:bg-zinc-800/60 text-[#212121] dark:text-zinc-200 cursor-pointer focus:outline-none focus:border-[#1a68f0] focus:bg-white dark:focus:bg-zinc-800 transition"
                                >
                                    <option value="diamond" {{ $tSlug === 'diamond' ? 'selected' : '' }}>Diamond</option>
                                    <option value="platinum" {{ $tSlug === 'platinum' ? 'selected' : '' }}>Platinum</option>
                                    <option value="gold" {{ $tSlug === 'gold' ? 'selected' : '' }}>Gold</option>
                                    <option value="silver" {{ $tSlug === 'silver' ? 'selected' : '' }}>Silver</option>
                                    <option value="bronze" {{ $tSlug === 'bronze' ? 'selected' : '' }}>Bronze</option>
                                    <option value="kontribusi" {{ $tSlug === 'kontribusi' ? 'selected' : '' }}>Kontribusi</option>
                                </select>
                            </div>
                        </div>

                        <!-- Quota Progress: A thin, elegant progress bar showing 'Pemakaian Kuota Bulan Ini' -->
                        <!-- Use primary blue for the fill, light gray for the track (no neon pink or purple) -->
                        <div class="p-3.5 rounded-xl bg-[#F8F9FA] dark:bg-zinc-800/40 border border-slate-100 dark:border-zinc-800/80 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-[#828282] font-medium">Pemakaian Kuota Bulan Ini</span>
                                <span class="font-bold text-[#212121] dark:text-zinc-100">
                                    @if ($isUnlimited)
                                        Terpakai {{ $usedCount }} dari <span class="text-[#1a68f0] font-bold">Tak Terbatas</span>
                                    @else
                                        Terpakai {{ $usedCount }} dari {{ $maxQuota }}
                                    @endif
                                </span>
                            </div>

                            @if (!$isUnlimited && $maxQuota > 0)
                                <div class="w-full bg-slate-200 dark:bg-zinc-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-[#1a68f0] transition-all duration-300" style="width: {{ $quotaPercent }}%"></div>
                                </div>
                            @elseif ($isUnlimited)
                                <div class="w-full bg-blue-100 dark:bg-blue-950/40 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-[#1a68f0] w-full"></div>
                                </div>
                            @endif

                            <!-- Details: 'Akun Portal: email@domain.com' in subtle gray text -->
                            <div class="flex items-center justify-between text-xs text-[#828282] pt-1.5 border-t border-slate-200/60 dark:border-zinc-700/60">
                                <span>Akun Portal:</span>
                                @if ($sponsor->user)
                                    <span class="font-medium text-[#212121] dark:text-zinc-300 truncate max-w-[170px]" title="{{ $sponsor->user->email }}">{{ $sponsor->user->email }}</span>
                                @else
                                    <span class="italic text-slate-400">Belum ada akun</span>
                                @endif
                            </div>
                        </div>

                        <!-- Actions (Bottom of card): Clean, minimal buttons for 'Detail' and 'Edit' (outline or light gray fill), and simple trash can icon for delete -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-zinc-800/80">
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button" 
                                    wire:click="openDetailModal({{ $sponsor->id }})" 
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-200 hover:bg-slate-200 dark:hover:bg-zinc-700 transition cursor-pointer"
                                >
                                    Detail
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="editSponsor({{ $sponsor->id }})" 
                                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-800 transition cursor-pointer"
                                >
                                    Edit
                                </button>
                            </div>

                            <button 
                                type="button" 
                                wire:click="deleteSponsor({{ $sponsor->id }})" 
                                wire:confirm="Yakin ingin menghapus mitra sponsor '{{ $sponsor->name }}'?" 
                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-zinc-800 rounded-lg transition cursor-pointer" 
                                title="Hapus sponsor"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 text-slate-400">
                        <svg class="w-12 h-12 text-slate-300 dark:text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <p class="text-sm font-medium text-slate-600 dark:text-zinc-300">Tidak ada mitra sponsor yang ditemukan</p>
                        <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter tier & status.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- TAB 2: MATRIKS BENEFIT PER TIER (SPONSOR-01) -->
    @if ($activeTab === 'matrix')
        <div class="space-y-4">
            <!-- Deskripsi & Info Bar -->
            <div class="p-4 rounded-2xl bg-blue-50/80 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 text-xs text-blue-900 dark:text-blue-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Ubah nilai benefit keenam tier sekaligus. Nilai ini langsung membatasi kuota unggah dan mengatur alur tayang di aplikasi tanpa deploy ulang.</span>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-zinc-600 dark:text-zinc-300">
                        <span class="w-3 h-3 rounded bg-zinc-200 dark:bg-zinc-700 border border-zinc-300"></span>
                        <span class="text-[11px]">— : Tidak berlaku</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-semibold text-purple-700 dark:text-purple-300">
                        <span class="px-1 py-0.5 rounded bg-purple-100 dark:bg-purple-900/50 text-[11px] font-bold">∞</span>
                        <span class="text-[11px]">Tidak terbatas</span>
                    </span>
                </div>
            </div>

            <!-- Tabel Matriks Besar -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-h-[72vh]">
                    <table class="w-full text-left text-xs border-collapse">
                        <!-- Table Header: Tier Columns -->
                        <thead class="bg-zinc-100/90 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 uppercase font-bold sticky top-0 z-20 backdrop-blur border-b border-zinc-200 dark:border-zinc-700">
                            <tr>
                                <th class="py-3.5 px-4 min-w-[220px] bg-zinc-100 dark:bg-zinc-800 sticky left-0 z-30 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                    <div class="flex items-center justify-between">
                                        <span>Benefit / Fitur</span>
                                        <span class="text-[10px] text-zinc-400 font-normal">Aksi Reset</span>
                                    </div>
                                </th>
                                @foreach ($matrixTiers as $t)
                                    @php
                                        $tSlug = $t['slug'];
                                        $headerColor = match($tSlug) {
                                            'diamond' => 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-800 dark:text-cyan-300 border-cyan-200',
                                            'platinum' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 border-purple-200',
                                            'gold' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-200',
                                            'silver' => 'bg-slate-100 dark:bg-slate-800/80 text-slate-800 dark:text-slate-300 border-slate-200',
                                            'bronze' => 'bg-orange-50 dark:bg-orange-950/40 text-orange-900 dark:text-orange-300 border-orange-200',
                                            default => 'bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border-blue-200',
                                        };
                                    @endphp
                                    <th class="py-3 px-3 text-center min-w-[155px] border-l border-zinc-200 dark:border-zinc-700 {{ $headerColor }}">
                                        <div class="text-xs font-black">{{ $t['name'] }}</div>
                                        <div class="text-[10px] font-medium opacity-80 mt-0.5">{{ $t['badge_label'] }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <!-- Table Body: 14 Benefit Rows -->
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @php $lastGroup = null; @endphp
                            @foreach ($matrixCategories as $cat)
                                @php
                                    $catSlug = $cat['slug'];
                                    $catGroup = $cat['group'] ?? 'Lainnya';
                                @endphp

                                {{-- BEN-03: judul kelompok supaya Admin awam mudah membaca --}}
                                @if ($catGroup !== $lastGroup)
                                    @php $lastGroup = $catGroup; @endphp
                                    <tr class="bg-blue-50/70 dark:bg-blue-950/30">
                                        <td colspan="{{ count($matrixTiers) + 2 }}" class="py-2 px-4 text-[11px] font-black uppercase tracking-wider text-blue-700 dark:text-blue-300 border-y border-blue-100 dark:border-blue-900">
                                            {{ $catGroup }}
                                        </td>
                                    </tr>
                                @endif

                                @php
                                    $allowUnlimited = in_array($catSlug, ['kuota_produk', 'best_deal_slot', 'push_broadcast']);
                                @endphp
                                <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/30 transition">
                                    <!-- Row Header: Category Name + Reset Button -->
                                    <td class="py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100 bg-white dark:bg-zinc-900 sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">
                                        <div class="flex items-center justify-between gap-2">
                                            <div>
                                                <div class="font-bold text-zinc-900 dark:text-white">{{ $cat['name'] }}</div>
                                                <div class="text-[10px] text-zinc-400 font-normal leading-tight">{{ $cat['description'] }}</div>
                                            </div>
                                            <button type="button" wire:click="promptResetRow('{{ $catSlug }}')" title="Kembalikan baris {{ $cat['name'] }} ke nilai awal" class="p-1.5 rounded-lg text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-zinc-800 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Matrix Cells per Tier -->
                                    @foreach ($matrixTiers as $t)
                                        @php
                                            $tSlug = $t['slug'];
                                            $cell = $matrix[$catSlug][$tSlug] ?? null;
                                            $isDisabled = $cell && $cell['is_disabled'];
                                            $isUnlimited = $cell && $cell['is_unlimited'];
                                        @endphp
                                        <td class="py-2.5 px-3 text-center border-l border-zinc-200 dark:border-zinc-800 {{ $isDisabled ? 'bg-zinc-100/60 dark:bg-zinc-800/40' : '' }}">
                                            @if ($isDisabled)
                                                <span class="text-zinc-400 dark:text-zinc-600 font-bold select-none text-sm">—</span>
                                            @elseif ($cat['value_type'] === 'boolean')
                                                <!-- Checkbox Ya / Tidak -->
                                                <label class="inline-flex items-center justify-center cursor-pointer">
                                                    <input type="checkbox" wire:model.defer="matrix.{{ $catSlug }}.{{ $tSlug }}.value" class="w-4 h-4 rounded text-blue-600 border-zinc-300 dark:border-zinc-600 focus:ring-blue-500">
                                                </label>
                                            @elseif ($catSlug === 'best_deal_prioritas')
                                                <!-- Dropdown Prioritas Best Deal -->
                                                <select wire:model.defer="matrix.{{ $catSlug }}.{{ $tSlug }}.value" class="w-full px-2 py-1 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-medium">
                                                    <option value="Standar">Standar</option>
                                                    <option value="Top 5">Top 5</option>
                                                    <option value="Top 3">Top 3</option>
                                                    <option value="Slot #1">Slot #1</option>
                                                </select>
                                            @elseif ($catSlug === 'hero_posisi')
                                                <!-- Dropdown Posisi Hero -->
                                                <select wire:model.defer="matrix.{{ $catSlug }}.{{ $tSlug }}.value" class="w-full px-2 py-1 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-medium">
                                                    <option value="Slide 5-6">Slide 5-6</option>
                                                    <option value="Slide 3-4">Slide 3-4</option>
                                                    <option value="Slide 1-2">Slide 1-2</option>
                                                    <option value="Slide #1">Slide #1</option>
                                                </select>
                                            @elseif ($cat['value_type'] === 'string')
                                                <!-- Input Teks (Badge) -->
                                                <input type="text" wire:model.defer="matrix.{{ $catSlug }}.{{ $tSlug }}.value" class="w-full px-2 py-1 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-medium text-center">
                                            @else
                                                <!-- Input Angka / Persen dengan Checkbox Tidak Terbatas (∞) -->
                                                <div class="flex flex-col items-center gap-1">
                                                    <div class="relative w-full">
                                                        @if ($isUnlimited)
                                                            <div class="w-full py-1 text-xs font-bold rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 text-center select-none">
                                                                ∞
                                                            </div>
                                                        @else
                                                            <input type="number" 
                                                                wire:model.defer="matrix.{{ $catSlug }}.{{ $tSlug }}.value" 
                                                                min="0" 
                                                                max="{{ $catSlug === 'share_of_voice' ? 100 : 9999 }}"
                                                                class="w-full px-2 py-1 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-bold text-center">
                                                        @endif
                                                    </div>

                                                    @if ($allowUnlimited)
                                                        <!-- BEN-05: teks "Tidak terbatas" hanya tampil saat dicentang,
                                                             supaya tidak berulang di setiap sel. -->
                                                        <label class="inline-flex items-center gap-1 cursor-pointer select-none" title="Tandai tidak terbatas">
                                                            <input type="checkbox" wire:click="toggleUnlimited('{{ $catSlug }}', '{{ $tSlug }}')" {{ $isUnlimited ? 'checked' : '' }} class="w-3 h-3 rounded text-purple-600 border-zinc-300 dark:border-zinc-600">
                                                            @if ($isUnlimited)
                                                                <span class="text-[9px] font-semibold text-purple-600 dark:text-purple-300">Tidak terbatas</span>
                                                            @else
                                                                <span class="text-[9px] font-semibold text-zinc-400">∞</span>
                                                            @endif
                                                        </label>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Action Bar: Reset All & Save All -->
                <!-- TIER-01: kartu pengelolaan tier -->
                <div class="p-4 border-t border-zinc-200 dark:border-zinc-800 space-y-3">
                    @if ($tierMessage)
                        <div class="px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs font-semibold text-amber-800 dark:text-amber-200">
                            {{ $tierMessage }}
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-100">Daftar Tier</h3>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Nama, label badge, warna, dan urutan tier dapat diubah. Tier tidak dapat dihapus selama masih ada sponsor di dalamnya.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($matrixTiers as $t)
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                                <i class="fas fa-star shrink-0" style="color: {{ $t['badge_color'] ?? '#1B4F9B' }};"></i>
                                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $t['name'] }}</span>
                                <span class="text-[10px] text-zinc-400">{{ $t['badge_label'] }}</span>
                                <button type="button" wire:click="openTierModal({{ $t['id'] }})" class="text-[11px] font-semibold text-blue-600 hover:underline">Ubah</button>
                                @if (($t['slug'] ?? '') !== 'free')
                                    <button type="button" wire:click="deleteTier({{ $t['id'] }})" wire:confirm="Hapus tier {{ $t['name'] }}? Tier tidak akan terhapus bila masih ada sponsor di dalamnya." class="text-[11px] font-semibold text-rose-600 hover:underline">Hapus</button>
                                @endif
                            </div>
                        @endforeach
                        <button type="button" wire:click="openTierModal" class="px-3 py-1.5 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-600 text-xs font-semibold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            + Tambah Tier
                        </button>
                    </div>
                </div>

                <div class="p-4 bg-zinc-50 dark:bg-zinc-800/60 border-t border-zinc-200 dark:border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <button type="button" wire:click="promptResetAll" class="px-4 py-2 text-xs font-bold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-xl transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Kembalikan Semua ke Nilai Awal
                    </button>

                    <div class="flex items-center gap-3">
                        <span class="text-xs text-zinc-400">Pastikan nilai sesuai sebelum menyimpan.</span>
                        <button type="button" wire:click="saveMatrix" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow flex items-center gap-2 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan Semua Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL DETAIL SPONSOR (Profil, Benefit, Produk) -->
    @if ($showDetailModal && $this->detailSponsor)
        @php $s = $this->detailSponsor; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-2xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-zinc-100 dark:bg-zinc-800 border flex items-center justify-center font-bold text-sm">
                            @if ($s->logo_path)
                                <img src="{{ asset('storage/' . $s->logo_path) }}" class="w-full h-full object-cover rounded-xl">
                            @else
                                {{ substr($s->name, 0, 2) }}
                            @endif
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">{{ $s->name }}</h2>
                            <span class="text-xs font-semibold text-zinc-500 uppercase">{{ $s->sponsorTier?->name ?? $s->tier }}</span>
                        </div>
                    </div>
                    <button wire:click="$set('showDetailModal', false)" class="text-zinc-400 hover:text-zinc-600 font-bold">✕</button>
                </div>

                <!-- Sub Tabs in Detail Modal -->
                <div class="flex border-b dark:border-zinc-800 text-xs font-bold gap-4">
                    <button wire:click="$set('detailActiveTab', 'profile')" class="pb-2 border-b-2 {{ $detailActiveTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-400' }}">Profil</button>
                    <button wire:click="$set('detailActiveTab', 'benefits')" class="pb-2 border-b-2 {{ $detailActiveTab === 'benefits' ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-400' }}">Benefit Tier</button>
                    <button wire:click="$set('detailActiveTab', 'products')" class="pb-2 border-b-2 {{ $detailActiveTab === 'products' ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-400' }}">Produk ({{ count($s->products) }})</button>
                </div>

                <!-- Tab Content: Profil -->
                @if ($detailActiveTab === 'profile')
                    <div class="space-y-3 text-xs">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                                <span class="text-zinc-400 block mb-0.5">Kontak WhatsApp:</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $s->whatsapp ?: '-' }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                                <span class="text-zinc-400 block mb-0.5">Email Penanggung Jawab:</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $s->contact_email ?: '-' }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                            <span class="text-zinc-400 block mb-0.5">Website / Toko Online:</span>
                            <a href="{{ $s->website_url }}" target="_blank" class="text-blue-600 hover:underline font-bold">{{ $s->website_url ?: '-' }}</a>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
                            <span class="text-zinc-400 block mb-0.5">Deskripsi Perusahaan:</span>
                            <p class="text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $s->description ?: 'Tidak ada deskripsi.' }}</p>
                        </div>
                    </div>
                @endif

                <!-- Tab Content: Benefits -->
                @if ($detailActiveTab === 'benefits')
                    <div class="space-y-2 text-xs max-h-64 overflow-y-auto">
                        @foreach ($matrixCategories as $cat)
                            @php
                                $val = $s->resolveBenefit($cat['slug']);
                                $isUn = is_null($val) && in_array($cat['slug'], ['kuota_produk', 'best_deal_slot', 'push_broadcast']) && $s->tier === 'diamond';
                            @endphp
                            <div class="p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $cat['name'] }}</span>
                                <span class="font-bold {{ $isUn ? 'text-purple-600' : 'text-zinc-900 dark:text-white' }}">
                                    @if ($isUn)
                                        ∞ (Tidak Terbatas)
                                    @elseif (is_bool($val))
                                        {{ $val ? '✓ Ya' : '✗ Tidak' }}
                                    @else
                                        {{ $val ?? '—' }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Tab Content: Produk -->
                @if ($detailActiveTab === 'products')
                    <div class="space-y-2 text-xs max-h-64 overflow-y-auto">
                        @forelse ($s->products as $p)
                            <div class="p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-800 flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ $p->name }}</div>
                                    <div class="text-[11px] text-zinc-400">Rp {{ number_format($p->price, 0, ',', '.') }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $p->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">
                                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-zinc-400 text-center py-6">Belum ada produk yang diunggah sponsor ini.</p>
                        @endforelse
                    </div>
                @endif

                <div class="flex justify-end pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('showDetailModal', false)" class="px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL KONFIRMASI RESET MATRIX (Diff Before vs After) -->
    @if ($showResetConfirmModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3 border-b pb-3 dark:border-zinc-800">
                    <div class="w-9 h-9 rounded-full bg-amber-100 dark:bg-amber-950 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-zinc-900 dark:text-white">
                            {{ $resetType === 'row' ? 'Kembalikan Baris ke Nilai Awal?' : 'Kembalikan Seluruh Matriks ke Nilai Awal?' }}
                        </h2>
                        <p class="text-xs text-zinc-500">Nilai sekarang akan digantikan dengan patokan nilai default.</p>
                    </div>
                </div>

                <div class="text-xs space-y-2">
                    <p class="font-semibold text-zinc-700 dark:text-zinc-300">Daftar nilai yang akan berubah:</p>
                    <div class="max-h-48 overflow-y-auto rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/40 p-3 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($resetDiff as $d)
                            <div class="py-1.5 flex items-center justify-between text-[11px]">
                                <div>
                                    <span class="font-bold text-zinc-900 dark:text-white">{{ $d['tier_name'] }}</span>
                                    <span class="text-zinc-400">({{ $d['category_name'] }})</span>
                                </div>
                                <div class="font-mono font-bold">
                                    <span class="text-rose-600">{{ $d['current'] }}</span>
                                    <span class="text-zinc-400 mx-1">→</span>
                                    <span class="text-emerald-600">{{ $d['initial'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-zinc-400 italic">*Perubahan hanya terjadi di tampilan dan baru permanen setelah menekan "Simpan Semua".</p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('showResetConfirmModal', false)" class="px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 transition">
                        Batal
                    </button>
                    <button wire:click="confirmReset" class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-sm transition">
                        Ya, Kembalikan ke Nilai Awal
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL FORM TAMBAH / EDIT SPONSOR -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm transition-all"
             x-data
             @keydown.escape.window="$wire.set('showModal', false)">
            <div class="bg-white rounded-[16px] w-full max-w-[600px] shadow-2xl border border-[#E0E0E0] flex flex-col max-h-[90vh] overflow-hidden text-[#212121] animate-in fade-in zoom-in-95 duration-150">
                
                <!-- 1. Sticky Header (Fixed at top) -->
                <div class="sticky top-0 z-10 bg-white px-6 py-4 flex items-center justify-between border-b border-[#E0E0E0]">
                    <div>
                        <h2 class="text-base font-bold text-[#212121]">
                            {{ $editingId ? 'Edit Mitra Sponsor' : 'Tambah Mitra Sponsor Baru' }}
                        </h2>
                        <p class="text-xs text-[#828282] mt-0.5">Kelola data identitas dan pengaturan akun mitra sponsor</p>
                    </div>
                    <button type="button"
                            wire:click="$set('showModal', false)" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] hover:bg-zinc-100 transition-colors"
                            title="Tutup Modal">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- 2. Scrollable Body (Form Content with 24px padding) -->
                <div class="flex-1 overflow-y-auto p-6 space-y-5">
                    <!-- Nama Perusahaan / Sponsor -->
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">
                            Nama Perusahaan / Sponsor <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="name" 
                               placeholder="Contoh: PT VBAT Mobile Solusindo"
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('name')
                            <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Upload Logo Sponsor Zone -->
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">
                            Logo Resmi Sponsor
                        </label>
                        <div class="relative rounded-xl border-2 border-dashed border-[#E0E0E0] hover:border-[#1a68f0]/60 bg-[#F8F9FA] hover:bg-[#EBF3FE]/30 p-4 transition-all">
                            <input type="file" 
                                   wire:model="logo" 
                                   accept="image/png,image/jpeg,image/jpg,image/webp" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            <div class="flex items-center gap-4">
                                <!-- Preview Thumbnail -->
                                <div class="w-16 h-16 rounded-xl bg-white border border-[#E0E0E0] flex items-center justify-center overflow-hidden shrink-0 shadow-xs relative">
                                    @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                                        <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-full h-full object-contain p-1.5">
                                    @elseif ($existingLogoPath)
                                        <img src="{{ asset('storage/' . $existingLogoPath) }}" alt="Logo" class="w-full h-full object-contain p-1.5">
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center text-[#1a68f0] bg-[#EBF3FE]/60">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                <!-- Text & Instructions -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-[#1a68f0]">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <span>Klik atau seret file ke sini</span>
                                    </div>
                                    <p class="text-[11px] text-[#828282] mt-0.5">Format: JPG, PNG, atau WEBP (maks. 2MB)</p>
                                    <div wire:loading wire:target="logo" class="text-[11px] text-[#1a68f0] font-medium mt-1">Mengunggah logo...</div>
                                    @if ($logo || $existingLogoPath)
                                        <button type="button" 
                                                wire:click.stop="removeLogo" 
                                                class="relative z-20 text-[11px] text-rose-600 hover:text-rose-700 font-semibold mt-1 inline-block">
                                            Hapus logo terpilih
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @error('logo')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Gambar Header Halaman Sponsor (Upload Zone) -->
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">
                            Gambar Header Halaman Sponsor <span class="text-[11px] font-normal text-[#828282]">(Opsional)</span>
                        </label>
                        <div class="relative rounded-xl border-2 border-dashed border-[#E0E0E0] hover:border-[#1a68f0]/60 bg-[#F8F9FA] hover:bg-[#EBF3FE]/30 p-4 transition-all">
                            <input type="file" 
                                   wire:model="headerImage" 
                                   accept="image/png,image/jpeg,image/jpg,image/webp" 
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                            
                            <div class="flex items-center gap-4">
                                <div class="w-20 h-14 rounded-lg bg-white border border-[#E0E0E0] flex items-center justify-center overflow-hidden shrink-0 shadow-xs relative">
                                    @if ($headerImage && method_exists($headerImage, 'isPreviewable') && $headerImage->isPreviewable())
                                        <img src="{{ $headerImage->temporaryUrl() }}" alt="Preview Header" class="w-full h-full object-cover">
                                    @elseif ($existingHeaderImagePath)
                                        <img src="{{ asset('storage/' . $existingHeaderImagePath) }}" alt="Header" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex flex-col items-center justify-center text-[#828282] bg-zinc-100">
                                            <svg class="w-5 h-5 text-[#828282]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold text-[#1a68f0]">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                        </svg>
                                        <span>Klik atau seret gambar header</span>
                                    </div>
                                    <p class="text-[11px] text-[#828282] mt-0.5">JPG, PNG, atau WEBP (maks. 4MB). Bila kosong memakai default tema.</p>
                                    <div wire:loading wire:target="headerImage" class="text-[11px] text-[#1a68f0] font-medium mt-1">Mengunggah header...</div>
                                    @if ($headerImage || $existingHeaderImagePath)
                                        <button type="button" 
                                                wire:click.stop="removeHeaderImage" 
                                                class="relative z-20 text-[11px] text-rose-600 hover:text-rose-700 font-semibold mt-1 inline-block">
                                            Hapus gambar header
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @error('headerImage')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tier Sponsor (Modern Custom Styled Select) -->
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">
                            Tier Sponsor <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <select wire:model="tier" 
                                    class="w-full appearance-none px-3.5 py-2.5 pr-10 text-xs font-medium rounded-xl border border-[#E0E0E0] bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all cursor-pointer">
                                <option value="free">Free - Basic Exposure</option>
                                <option value="kontribusi">Kontribusi - Partner</option>
                                <option value="bronze">Bronze - Featured</option>
                                <option value="silver">Silver - Premium Partner</option>
                                <option value="gold">Gold - Priority Sponsor</option>
                                <option value="platinum">Platinum - Exclusive Partner</option>
                                <option value="diamond">Diamond - Main Partner</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#828282]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-[11px] text-[#828282] mt-1">Bobot tayang dan kuota produk otomatis disesuaikan dengan paket benefit tier.</p>
                    </div>

                    <!-- 2-Column Grid: WhatsApp Order & Website Resmi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] mb-1.5">WhatsApp Order</label>
                            <div class="relative">
                                <input type="text" 
                                       wire:model="whatsapp" 
                                       placeholder="628123456789" 
                                       class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                            </div>
                            <span class="text-[11px] text-[#828282] mt-1 block">Gunakan kode negara (62...)</span>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] mb-1.5">Website Resmi</label>
                            <input type="url" 
                                   wire:model="website_url" 
                                   placeholder="https://perusahaan.com" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                            @error('website_url')
                                <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- 2-Column Grid: Tautan Marketplace & Email Kontak -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] mb-1.5">Tautan Marketplace</label>
                            <input type="url" 
                                   wire:model="marketplace_url" 
                                   placeholder="https://shopee.co.id/toko" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                            @error('marketplace_url')
                                <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] mb-1.5">Email Kontak</label>
                            <input type="email" 
                                   wire:model="contact_email" 
                                   placeholder="sponsor@perusahaan.com" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                            @error('contact_email')
                                <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Deskripsi Singkat -->
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">Deskripsi Singkat</label>
                        <textarea wire:model="description" 
                                  rows="2" 
                                  placeholder="Tuliskan profil singkat sponsor atau keterangan kemitraan..." 
                                  class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all resize-none"></textarea>
                    </div>

                    <!-- Status Akun Aktif -->
                    <div class="p-3.5 rounded-xl border border-[#E0E0E0] bg-[#F8F9FA] flex items-center justify-between">
                        <div>
                            <label for="sponsor-is-active" class="text-xs font-semibold text-[#212121] cursor-pointer block">
                                Status Akun Aktif
                            </label>
                            <span class="text-[11px] text-[#828282] block mt-0.5">Sponsor dapat tampil dan menayangkan produk di platform</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="sponsor-is-active" wire:model="is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-zinc-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1a68f0]"></div>
                        </label>
                    </div>

                    <!-- Terbitkan Akun Login Baru -->
                    @if (!$editingId)
                        <div class="p-4 rounded-xl border border-[#E0E0E0] bg-[#F8F9FA] space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label for="create-user-acc" class="text-xs font-semibold text-[#212121] cursor-pointer block">
                                        Terbitkan Akun Login Portal
                                    </label>
                                    <span class="text-[11px] text-[#828282] block mt-0.5">Buat kredensial login otomatis untuk akses mitra sponsor</span>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="create-user-acc" wire:model.live="createUserAccount" class="sr-only peer">
                                    <div class="w-11 h-6 bg-zinc-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1a68f0]"></div>
                                </label>
                            </div>

                            @if ($createUserAccount)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-[#E0E0E0]">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[#212121] mb-1">Email Login Portal</label>
                                        <input type="email" 
                                               wire:model="userEmail" 
                                               placeholder="sponsor@perusahaan.com" 
                                               class="w-full px-3 py-2 text-xs rounded-lg border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0]">
                                        @error('userEmail')
                                            <span class="text-[11px] text-rose-500 mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-[#212121] mb-1">Password Awal</label>
                                        <input type="password" 
                                               wire:model="userPassword" 
                                               placeholder="Min. 8 karakter" 
                                               class="w-full px-3 py-2 text-xs rounded-lg border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0]">
                                        @error('userPassword')
                                            <span class="text-[11px] text-rose-500 mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- 3. Sticky Footer (Fixed at bottom) -->
                <div class="sticky bottom-0 z-10 bg-white px-6 py-4 flex items-center justify-end gap-3 border-t border-[#E0E0E0]">
                    <!-- Secondary Button: Batal (Ghost / Outline style) -->
                    <button type="button" 
                            wire:click="$set('showModal', false)" 
                            class="px-5 py-2 text-xs font-semibold rounded-xl border border-[#E0E0E0] bg-white text-[#212121] hover:bg-zinc-50 hover:border-zinc-300 transition-colors">
                        Batal
                    </button>
                    <!-- Primary Button: Simpan (Solid VBAT Blue) -->
                    <button type="button" 
                            wire:click="save" 
                            wire:loading.attr="disabled"
                            class="px-5 py-2 text-xs font-semibold rounded-xl bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save" class="flex items-center gap-1.5">
                            <svg class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

    <!-- MODAL KELOLA TIER (TIER-01) -->
    @if ($showTierModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all"
             x-data
             @keydown.escape.window="$wire.set('showTierModal', false)">
            <div class="bg-white rounded-[16px] w-full max-w-[480px] shadow-2xl border border-[#E0E0E0] flex flex-col max-h-[90vh] overflow-hidden text-[#212121] animate-in fade-in zoom-in-95 duration-150">
                <!-- Sticky Header -->
                <div class="sticky top-0 z-10 bg-white px-6 py-4 flex items-center justify-between border-b border-[#E0E0E0]">
                    <div>
                        <h2 class="text-base font-bold text-[#212121]">
                            {{ $tierEditingId ? 'Ubah Tier Sponsor' : 'Tambah Tier Baru' }}
                        </h2>
                        <p class="text-xs text-[#828282] mt-0.5">Atur identitas tier sponsor dan urutan penampilan</p>
                    </div>
                    <button type="button" 
                            wire:click="$set('showTierModal', false)" 
                            class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] hover:bg-zinc-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Scrollable Body -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">Nama Tier <span class="text-rose-500">*</span></label>
                        <input type="text" 
                               wire:model="tierName" 
                               placeholder="Contoh: Silver, Gold, Platinum" 
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        @error('tierName') <span class="text-xs text-rose-500 mt-1 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#212121] mb-1.5">Label Badge</label>
                        <input type="text" 
                               wire:model="tierBadgeLabel" 
                               placeholder="Contoh: Silver Official Partner" 
                               class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] placeholder-[#BDBDBD] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                        <span class="text-[11px] text-[#828282] mt-1 block">Teks badge yang tampil pada kartu produk katalog.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] mb-1.5">Warna Aksen Badge</label>
                            <div class="flex items-center gap-2">
                                <input type="color" 
                                       wire:model="tierBadgeColor" 
                                       class="w-10 h-10 rounded-xl border border-[#E0E0E0] bg-white cursor-pointer p-0.5 shrink-0">
                                <input type="text" 
                                       wire:model="tierBadgeColor" 
                                       class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-[#E0E0E0] bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] mb-1.5">Urutan Prioritas</label>
                            <input type="number" 
                                   wire:model="tierSortOrder" 
                                   min="0" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-[#E0E0E0] bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all">
                            <span class="text-[11px] text-[#828282] mt-1 block">Angka lebih kecil tampil di urutan awal.</span>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer -->
                <div class="sticky bottom-0 z-10 bg-white px-6 py-4 flex items-center justify-end gap-3 border-t border-[#E0E0E0]">
                    <button type="button" 
                            wire:click="$set('showTierModal', false)" 
                            class="px-5 py-2 text-xs font-semibold rounded-xl border border-[#E0E0E0] bg-white text-[#212121] hover:bg-zinc-50 hover:border-zinc-300 transition-colors">
                        Batal
                    </button>
                    <button type="button" 
                            wire:click="saveTier" 
                            wire:loading.attr="disabled"
                            class="px-5 py-2 text-xs font-semibold rounded-xl bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white shadow-sm transition-colors flex items-center gap-1.5 disabled:opacity-50">
                        <span wire:loading.remove wire:target="saveTier">Simpan Tier</span>
                        <span wire:loading wire:target="saveTier" class="flex items-center gap-1.5">
                            <svg class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
