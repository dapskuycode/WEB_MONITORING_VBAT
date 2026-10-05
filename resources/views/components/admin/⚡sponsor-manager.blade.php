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
    public $contact_email = '';
    public $phone = '';
    public $whatsapp = '';
    public $description = '';
    public $is_active = true;

    // Logo Upload (SPONSOR-03)
    public $logo;
    public $existingLogoPath = null;

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

    public function mount()
    {
        $this->loadMatrix();
    }

    public function loadMatrix()
    {
        $tiers = SponsorTier::orderBy('sort_order')->get();
        $categories = BenefitCategory::orderBy('sort_order')->get();
        $tierBenefits = TierBenefit::all();

        $this->matrixTiers = $tiers->map(fn($t) => [
            'id' => $t->id,
            'slug' => $t->slug,
            'name' => $t->name,
            'badge_label' => $t->badge_label,
        ])->toArray();

        $this->matrixCategories = $categories->map(fn($c) => [
            'id' => $c->id,
            'slug' => $c->slug,
            'name' => $c->name,
            'description' => $c->description,
            'value_type' => $c->value_type,
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
        $this->reset(['editingId', 'name', 'tier', 'website_url', 'contact_email', 'phone', 'whatsapp', 'description', 'userEmail', 'userPassword', 'logo', 'existingLogoPath']);
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
        $this->contact_email = $sponsor->contact_email;
        $this->phone = $sponsor->phone;
        $this->whatsapp = $sponsor->whatsapp;
        $this->description = $sponsor->description;
        $this->is_active = (bool)$sponsor->is_active;
        $this->existingLogoPath = $sponsor->logo_path;
        $this->logo = null;
        $this->createUserAccount = false;
        $this->showModal = true;
    }

    public function removeLogo()
    {
        $this->logo = null;
        $this->existingLogoPath = null;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'tier' => 'required|in:kontribusi,bronze,silver,gold,platinum,diamond',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'logo.image' => 'Berkas harus berupa gambar valid (JPG, PNG, atau WEBP). Dokumen non-gambar seperti PDF tidak diizinkan.',
            'logo.mimes' => 'Format file yang diperbolehkan hanya JPG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran berkas logo maksimal 2MB.',
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

        if ($this->editingId) {
            $sponsor = Sponsor::findOrFail($this->editingId);
            $sponsor->update([
                'name' => $this->name,
                'tier' => $this->tier,
                'tier_id' => $tierId,
                'weight' => (int)$sov,
                'logo_path' => $logoPath,
                'website_url' => $this->website_url,
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
                'website_url' => $this->website_url,
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
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Manajemen Sponsor
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola kartu mitra sponsor, pantau kuota terpakai bulan ini, dan atur matriks benefit 6 tier.
            </p>
        </div>
        @if ($activeTab === 'sponsors')
            <button wire:click="openCreateModal" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Sponsor Baru
            </button>
        @endif
    </div>

    <!-- Alert Notifikasi Simpan / Aksi -->
    @if ($saveSuccessMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2 font-medium">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ $saveSuccessMessage }}
            </div>
            <button wire:click="$set('saveSuccessMessage', '')" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold">✕ Tutup</button>
        </div>
    @endif

    <!-- Tabs Navigasi Utama -->
    <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-3">
        <button wire:click="$set('activeTab', 'sponsors')" class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'sponsors' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Daftar Sponsor (SPONSOR-02)
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'sponsors' ? 'bg-blue-100 text-blue-800' : 'bg-zinc-100 text-zinc-600' }}">{{ count($this->sponsors) }}</span>
        </button>

        <button wire:click="$set('activeTab', 'matrix')" class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'matrix' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
            Benefit per Tier (SPONSOR-01)
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 font-semibold">6 Tier × 14 Benefit</span>
        </button>
    </div>

    <!-- TAB 1: DAFTAR SPONSOR (SPONSOR-02 CARDS & FILTERS) -->
    @if ($activeTab === 'sponsors')
        <div class="space-y-4">
            <!-- Filter & Search Toolbar -->
            <div class="p-4 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Search Input -->
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-zinc-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" wire:model.live.debounce.250ms="search" placeholder="Cari nama sponsor, email, atau no. telepon..." class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white focus:outline-blue-500">
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2 shrink-0">
                    <select wire:model.live="filterTier" class="px-3 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white font-medium">
                        <option value="">Semua Tier</option>
                        <option value="diamond">👑 Diamond</option>
                        <option value="platinum">⭐ Platinum</option>
                        <option value="gold">🥇 Gold</option>
                        <option value="silver">🥈 Silver</option>
                        <option value="bronze">🥉 Bronze</option>
                        <option value="kontribusi">🤝 Kontribusi</option>
                    </select>

                    <select wire:model.live="filterStatus" class="px-3 py-2 text-xs rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white font-medium">
                        <option value="">Semua Status</option>
                        <option value="1">🟢 Aktif Saja</option>
                        <option value="0">🔴 Nonaktif Saja</option>
                    </select>
                </div>
            </div>

            <!-- Kartu Sponsor (Card Grid) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @forelse ($this->sponsors as $sponsor)
                    @php
                        $tSlug = strtolower($sponsor->tier ?? 'kontribusi');
                        $maxQuota = $sponsor->resolveBenefit('kuota_produk');
                        $usedCount = $sponsor->products_count ?? 0;
                        $isUnlimited = is_null($maxQuota);
                        $quotaPercent = (!$isUnlimited && $maxQuota > 0) ? min(100, round(($usedCount / $maxQuota) * 100)) : 0;
                        $isOverQuota = (!$isUnlimited && $usedCount >= $maxQuota);

                        $tierBadgeStyle = match($tSlug) {
                            'diamond' => 'bg-cyan-50 dark:bg-cyan-950/40 text-cyan-800 dark:text-cyan-300 border-cyan-300',
                            'platinum' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 border-purple-300',
                            'gold' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-300 border-amber-300',
                            'silver' => 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-300',
                            'bronze' => 'bg-orange-50 dark:bg-orange-950/40 text-orange-900 dark:text-orange-300 border-orange-300',
                            default => 'bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border-blue-300',
                        };
                    @endphp

                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
                        <!-- Top: Logo, Nama, Website & Status Toggle -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <!-- Logo Thumbnail -->
                                <div class="w-12 h-12 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center overflow-hidden shrink-0">
                                    @if ($sponsor->logo_path)
                                        <img src="{{ asset('storage/' . $sponsor->logo_path) }}" alt="{{ $sponsor->name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="font-black text-sm text-zinc-500 uppercase">{{ substr($sponsor->name, 0, 2) }}</span>
                                    @endif
                                </div>

                                <div>
                                    <h3 class="font-bold text-zinc-900 dark:text-white text-base leading-snug line-clamp-1">
                                        {{ $sponsor->name }}
                                    </h3>
                                    @if ($sponsor->website_url)
                                        <a href="{{ $sponsor->website_url }}" target="_blank" class="text-xs text-blue-600 hover:underline flex items-center gap-1 mt-0.5 truncate max-w-[160px]">
                                            <span>Kunjungi Toko</span>
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @else
                                        <span class="text-xs text-zinc-400">Belum ada link toko</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Toggle -->
                            <button type="button" wire:click="toggleStatus({{ $sponsor->id }})" title="Klik untuk mengaktifkan / menonaktifkan sponsor" class="px-2.5 py-1 text-[11px] font-bold rounded-full border transition flex items-center gap-1.5 shrink-0 {{ $sponsor->is_active ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-300' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-rose-300' }}">
                                <span class="w-2 h-2 rounded-full {{ $sponsor->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $sponsor->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </div>

                        <!-- Mid 1: Quick Tier Switcher (Single click without leaving page) -->
                        <div class="space-y-1">
                            <label class="block text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Tiering Sponsor</label>
                            <div class="relative">
                                <select wire:change="changeTier({{ $sponsor->id }}, $event.target.value)" class="w-full pl-3 pr-8 py-1.5 text-xs font-bold rounded-xl border {{ $tierBadgeStyle }} bg-white dark:bg-zinc-800 cursor-pointer focus:ring-blue-500 focus:outline-none">
                                    <option value="diamond" {{ $tSlug === 'diamond' ? 'selected' : '' }}>👑 Diamond (Prioritas #1)</option>
                                    <option value="platinum" {{ $tSlug === 'platinum' ? 'selected' : '' }}>⭐ Platinum (Top 3)</option>
                                    <option value="gold" {{ $tSlug === 'gold' ? 'selected' : '' }}>🥇 Gold (Top 5)</option>
                                    <option value="silver" {{ $tSlug === 'silver' ? 'selected' : '' }}>🥈 Silver (Official)</option>
                                    <option value="bronze" {{ $tSlug === 'bronze' ? 'selected' : '' }}>🥉 Bronze (Partner)</option>
                                    <option value="kontribusi" {{ $tSlug === 'kontribusi' ? 'selected' : '' }}>🤝 Kontribusi (Kontributor)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Mid 2: Indikator Kuota Terpakai Bulan Ini -->
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-800 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-zinc-600 dark:text-zinc-400 font-medium">Pemakaian Kuota Bulan Ini:</span>
                                <span class="font-bold {{ $isOverQuota ? 'text-rose-600' : 'text-zinc-900 dark:text-white' }}">
                                    @if ($isUnlimited)
                                        Terpakai {{ $usedCount }} dari <span class="text-purple-600 font-black">∞ Bebas</span>
                                    @else
                                        Terpakai {{ $usedCount }} dari {{ $maxQuota }}
                                    @endif
                                </span>
                            </div>

                            @if (!$isUnlimited && $maxQuota > 0)
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all duration-300 {{ $isOverQuota ? 'bg-rose-500' : ($quotaPercent > 80 ? 'bg-amber-500' : 'bg-blue-600') }}" style="width: {{ $quotaPercent }}%"></div>
                                </div>
                            @elseif ($isUnlimited)
                                <div class="w-full bg-purple-100 dark:bg-purple-900/30 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full bg-purple-500 w-full"></div>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-[11px] text-zinc-500 dark:text-zinc-400 pt-1 border-t border-zinc-200 dark:border-zinc-700/60">
                                <span>Akun Portal:</span>
                                @if ($sponsor->user)
                                    <span class="font-semibold text-emerald-600 truncate max-w-[150px]">{{ $sponsor->user->email }}</span>
                                @else
                                    <span class="italic text-zinc-400">Belum ada akun</span>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom: Action Buttons -->
                        <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-800 text-xs">
                            <div class="flex items-center gap-1.5">
                                <button type="button" wire:click="openDetailModal({{ $sponsor->id }})" class="px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-semibold hover:bg-zinc-200 dark:hover:bg-zinc-700 transition flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Detail
                                </button>
                                <button type="button" wire:click="editSponsor({{ $sponsor->id }})" class="px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold hover:bg-blue-100 transition">
                                    Edit
                                </button>
                            </div>

                            <button type="button" wire:click="deleteSponsor({{ $sponsor->id }})" wire:confirm="Yakin ingin menghapus mitra sponsor '{{ $sponsor->name }}'?" class="p-1.5 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-zinc-800 rounded-lg transition" title="Hapus sponsor">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 text-zinc-500">
                        <svg class="w-12 h-12 text-zinc-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tidak ada mitra sponsor yang cocok dengan kriteria pencarian / filter.
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
                            @foreach ($matrixCategories as $cat)
                                @php
                                    $catSlug = $cat['slug'];
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
                                                        <label class="inline-flex items-center gap-1 cursor-pointer select-none">
                                                            <input type="checkbox" wire:click="toggleUnlimited('{{ $catSlug }}', '{{ $tSlug }}')" {{ $isUnlimited ? 'checked' : '' }} class="w-3 h-3 rounded text-purple-600 border-zinc-300 dark:border-zinc-600">
                                                            <span class="text-[9px] font-semibold text-zinc-500">∞ Bebas</span>
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

    <!-- MODAL FORM TAMBAH / EDIT SPONSOR (Tanpa start_date / end_date & tanpa manual weight) -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $editingId ? 'Edit Mitra Sponsor' : 'Tambah Mitra Sponsor Baru' }}
                    </h2>
                    <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600 font-bold">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Nama Perusahaan / Sponsor *</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white focus:outline-blue-500">
                    </div>

                    <!-- Upload Logo Sponsor (SPONSOR-03) -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Logo Resmi Sponsor (JPG / PNG / WEBP, maks 2MB)</label>
                        <div class="flex items-center gap-4 p-3 rounded-xl border border-dashed border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                            <!-- Thumbnail / Preview -->
                            <div class="w-14 h-14 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center overflow-hidden shrink-0 shadow-sm relative group">
                                @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                                    <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-full h-full object-contain p-1">
                                @elseif ($existingLogoPath)
                                    <img src="{{ asset('storage/' . $existingLogoPath) }}" alt="Logo" class="w-full h-full object-contain p-1">
                                @else
                                    <span class="text-xs font-bold text-zinc-400 uppercase">{{ substr($name ?: 'SP', 0, 2) }}</span>
                                @endif
                            </div>

                            <!-- Upload Input & Action -->
                            <div class="flex-1 space-y-1">
                                <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/jpg,image/webp" class="text-xs text-zinc-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950 dark:file:text-blue-300 cursor-pointer">
                                <div wire:loading wire:target="logo" class="text-[11px] text-blue-600 font-semibold">Mengunggah berkas...</div>
                                @error('logo')
                                    <span class="text-xs text-rose-500 font-medium block">{{ $message }}</span>
                                @enderror
                                @if ($logo || $existingLogoPath)
                                    <button type="button" wire:click="removeLogo" class="text-[11px] text-rose-600 hover:underline block font-semibold">Hapus logo</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tier Sponsor *</label>
                        <select wire:model="tier" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white font-medium">
                            <option value="diamond">👑 Diamond (Prioritas #1 - Bebas Kuota)</option>
                            <option value="platinum">⭐ Platinum (Top 3)</option>
                            <option value="gold">🥇 Gold (Top 5)</option>
                            <option value="silver">🥈 Silver (Official)</option>
                            <option value="bronze">🥉 Bronze (Partner)</option>
                            <option value="kontribusi">🤝 Kontribusi (Kontributor)</option>
                        </select>
                        <span class="text-[11px] text-zinc-400 mt-0.5 block">*Bobot tayang dan kuota produk otomatis mengikuti benefit tier yang dipilih.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">WhatsApp Order</label>
                            <input type="text" wire:model="whatsapp" placeholder="62812345678" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Website / Marketplace URL</label>
                            <input type="url" wire:model="website_url" placeholder="https://shopee.co.id/..." class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Email Kontak</label>
                        <input type="email" wire:model="contact_email" placeholder="contact@sponsor.com" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Deskripsi Singkat</label>
                        <textarea wire:model="description" rows="2" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>

                    <div class="pt-1">
                        <label class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-white cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded text-blue-600">
                            Status Akun Aktif (Dapat tayang di aplikasi)
                        </label>
                        <span class="text-[11px] text-zinc-400 ml-5 block">Ketika kontrak berakhir, cukup nonaktifkan status ini.</span>
                    </div>

                    @if (!$editingId)
                        <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/80 border border-zinc-200 dark:border-zinc-700 space-y-2">
                            <label class="flex items-center gap-2 text-xs font-bold text-zinc-900 dark:text-white cursor-pointer">
                                <input type="checkbox" wire:model.live="createUserAccount" class="rounded text-blue-600">
                                Sekaligus Terbitkan Akun Login Portal Sponsor
                            </label>
                            @if ($createUserAccount)
                                <div class="grid grid-cols-2 gap-2 pt-2">
                                    <div>
                                        <label class="block text-[11px] text-zinc-500 mb-0.5">Email Login</label>
                                        <input type="email" wire:model="userEmail" placeholder="sponsor@toko.com" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-zinc-500 mb-0.5">Password</label>
                                        <input type="password" wire:model="userPassword" placeholder="******" class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t dark:border-zinc-800">
                    <button wire:click="$set('showModal', false)" class="px-4 py-2 text-sm font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                        Batal
                    </button>
                    <button wire:click="save" class="px-4 py-2 text-sm font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                        Simpan Data
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>