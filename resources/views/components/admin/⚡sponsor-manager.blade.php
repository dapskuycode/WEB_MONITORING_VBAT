<?php

use Livewire\Component;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\BenefitCategory;
use App\Models\TierBenefit;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $activeTab = 'sponsors'; // 'sponsors' | 'matrix'

    // Sponsors list & form state
    public $sponsors;
    public $showModal = false;
    public $editingId = null;

    public $name = '';
    public $tier = 'gold';
    public $weight = 35;
    public $website_url = '';
    public $contact_email = '';
    public $phone = '';
    public $whatsapp = '';
    public $description = '';
    public $is_active = true;

    public $createUserAccount = true;
    public $userEmail = '';
    public $userPassword = '';

    // Matrix state (SPONSOR-01)
    public $matrixTiers = [];
    public $matrixCategories = [];
    public $matrix = []; // [category_slug => [tier_slug => [value, is_unlimited, is_disabled, initial_value, last_number]]]

    public $showResetConfirmModal = false;
    public $resetType = 'row'; // 'row' | 'all'
    public $resetTargetCategorySlug = null;
    public $resetDiff = [];
    public $saveSuccessMessage = '';

    public function mount()
    {
        $this->loadSponsors();
        $this->loadMatrix();
    }

    public function loadSponsors()
    {
        $this->sponsors = Sponsor::with(['user', 'sponsorTier'])
            ->withCount('products', 'campaigns')
            ->orderByDesc('weight')
            ->get();
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

        // If no differences found, still allow confirm
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
                    // Restore unlimited status based on initial
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

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'tier', 'weight', 'website_url', 'contact_email', 'phone', 'whatsapp', 'description', 'userEmail', 'userPassword']);
        $this->tier = 'gold';
        $this->weight = 35;
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
        $this->weight = $sponsor->weight ?? 35;
        $this->website_url = $sponsor->website_url;
        $this->contact_email = $sponsor->contact_email;
        $this->phone = $sponsor->phone;
        $this->whatsapp = $sponsor->whatsapp;
        $this->description = $sponsor->description;
        $this->is_active = (bool)$sponsor->is_active;
        $this->createUserAccount = false;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'tier' => 'required|in:kontribusi,bronze,silver,gold,platinum,diamond',
            'weight' => 'required|integer|min:0|max:100',
        ]);

        $tierModel = SponsorTier::where('slug', $this->tier)->first();
        $tierId = $tierModel?->id;

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

        if ($this->editingId) {
            $sponsor = Sponsor::findOrFail($this->editingId);
            $sponsor->update([
                'name' => $this->name,
                'tier' => $this->tier,
                'tier_id' => $tierId,
                'weight' => $this->weight,
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
                'weight' => $this->weight,
                'website_url' => $this->website_url,
                'contact_email' => $this->contact_email,
                'phone' => $this->phone,
                'whatsapp' => $this->whatsapp,
                'description' => $this->description,
                'is_active' => $this->is_active,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ]);
        }

        $this->showModal = false;
        $this->loadSponsors();
    }

    public function toggleStatus($id)
    {
        $s = Sponsor::findOrFail($id);
        $s->is_active = !$s->is_active;
        $s->save();
        $this->loadSponsors();
    }

    public function deleteSponsor($id)
    {
        Sponsor::destroy($id);
        $this->loadSponsors();
    }
};
?>

<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Manajemen Sponsor & Benefit per Tier
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Kelola seluruh akun mitra sponsor dan sesuaikan matriks benefit 6 tier secara terpusat.
            </p>
        </div>
        @if ($activeTab === 'sponsors')
            <button wire:click="openCreateModal" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Sponsor Baru
            </button>
        @endif
    </div>

    <!-- Alert Notifikasi Simpan -->
    @if ($saveSuccessMessage)
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center justify-between">
            <div class="flex items-center gap-2 font-medium">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ $saveSuccessMessage }}
            </div>
            <button wire:click="$set('saveSuccessMessage', '')" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold">✕ Tutup</button>
        </div>
    @endif

    <!-- Tabs Navigasi Utama -->
    <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-3">
        <button wire:click="$set('activeTab', 'sponsors')" class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'sponsors' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Daftar Mitra Sponsor
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'sponsors' ? 'bg-blue-100 text-blue-800' : 'bg-zinc-100 text-zinc-600' }}">{{ count($sponsors) }}</span>
        </button>

        <button wire:click="$set('activeTab', 'matrix')" class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2 {{ $activeTab === 'matrix' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
            Matriks Benefit per Tier (SPONSOR-01)
            <span class="ml-1 text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 font-semibold">6 Tier × 14 Benefit</span>
        </button>
    </div>

    <!-- TAB 1: DAFTAR SPONSOR -->
    @if ($activeTab === 'sponsors')
        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-xs font-semibold text-zinc-500 uppercase border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="px-6 py-4">Nama Sponsor</th>
                            <th class="px-6 py-4">Tier Resmi</th>
                            <th class="px-6 py-4">Akun Portal</th>
                            <th class="px-6 py-4">Kontak & Marketplace</th>
                            <th class="px-6 py-4">Status Tayang</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($sponsors as $sponsor)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-zinc-900 dark:text-white text-base">{{ $sponsor->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $sponsor->products_count }} Produk • {{ $sponsor->campaigns_count }} Banner Iklan</div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $tSlug = strtolower($sponsor->tier ?? 'kontribusi');
                                        $badgeColors = [
                                            'diamond' => 'bg-cyan-100 text-cyan-800 border-cyan-300',
                                            'platinum' => 'bg-purple-100 text-purple-800 border-purple-300',
                                            'gold' => 'bg-amber-100 text-amber-900 border-amber-300',
                                            'silver' => 'bg-slate-100 text-slate-800 border-slate-300',
                                            'bronze' => 'bg-orange-100 text-orange-900 border-orange-300',
                                            'kontribusi' => 'bg-blue-100 text-blue-800 border-blue-300',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase border {{ $badgeColors[$tSlug] ?? 'bg-zinc-100 text-zinc-800' }}">
                                        👑 {{ $sponsor->sponsorTier?->name ?? strtoupper($sponsor->tier) }}
                                    </span>
                                    <div class="text-xs text-zinc-400 mt-1 font-medium">Share of Voice: {{ $sponsor->weight }}%</div>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if ($sponsor->user)
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $sponsor->user->email }}</div>
                                        <span class="text-emerald-600 font-medium">✓ Aktif</span>
                                    @else
                                        <span class="text-zinc-400 italic">Belum dibuatkan akun</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-zinc-600 dark:text-zinc-300">
                                    <div>WA: {{ $sponsor->whatsapp ?? '-' }}</div>
                                    <a href="{{ $sponsor->website_url }}" target="_blank" class="text-blue-600 hover:underline truncate block max-w-[150px]">
                                        {{ $sponsor->website_url ?? '-' }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <button wire:click="toggleStatus({{ $sponsor->id }})" class="px-3 py-1 text-xs rounded-full font-bold {{ $sponsor->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $sponsor->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button wire:click="editSponsor({{ $sponsor->id }})" class="px-3 py-1.5 text-xs bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 text-zinc-800 dark:text-zinc-200 rounded-lg font-semibold">
                                        Edit
                                    </button>
                                    <button wire:click="deleteSponsor({{ $sponsor->id }})" wire:confirm="Hapus sponsor ini?" class="px-3 py-1.5 text-xs bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg font-semibold">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-500">Belum ada mitra sponsor.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

    <!-- MODAL KONFIRMASI RESET (Diff Before vs After) -->
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

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tier Sponsor *</label>
                            <select wire:model="tier" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                                <option value="diamond">Diamond (Prioritas #1)</option>
                                <option value="platinum">Platinum (Top 3)</option>
                                <option value="gold">Gold (Top 5)</option>
                                <option value="silver">Silver</option>
                                <option value="bronze">Bronze</option>
                                <option value="kontribusi">Kontribusi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Bobot Share of Voice (%) *</label>
                            <input type="number" wire:model="weight" min="0" max="100" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white">
                        </div>
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
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Deskripsi Singkat</label>
                        <textarea wire:model="description" rows="2" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 dark:text-white"></textarea>
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