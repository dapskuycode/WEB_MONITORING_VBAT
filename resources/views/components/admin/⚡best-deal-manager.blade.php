<?php

use Livewire\Component;
use App\Models\BestDeal;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $deal;
    public $dealProducts;
    public $sponsors;

    // Modal Add Products
    public $showAddModal = false;
    public $searchAvailable = '';
    public $sponsorFilter = '';

    public $message = '';

    public function mount()
    {
        $this->sponsors = Sponsor::orderByRaw("CASE WHEN LOWER(slug) = 'quantum' OR LOWER(name) = 'quantum' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        $this->loadDealAndProducts();
    }

    public function loadDealAndProducts()
    {
        $this->deal = BestDeal::with([
            'products' => function ($q) {
                $q->orderBy('best_deal_products.order', 'asc');
            },
            'products.sponsor'
        ])->latest()->first();

        if (!$this->deal) {
            $this->deal = BestDeal::create([
                'title' => 'SUPER BEST DEALS VBAT',
                'description' => 'Kurasi produk unggulan mitra resmi VBAT',
                'banner_path' => 'assets/images/banner_promo_diskon.png',
                'is_active' => true,
            ]);
            $this->deal = BestDeal::with('products.sponsor')->find($this->deal->id);
        }

        $this->dealProducts = $this->deal->products;
    }

    public function reorderProducts($orderedIds)
    {
        if (!$this->deal || empty($orderedIds)) return;

        foreach ($orderedIds as $index => $id) {
            DB::table('best_deal_products')
                ->where('best_deal_id', $this->deal->id)
                ->where('sponsor_product_id', $id)
                ->update([
                    'order' => $index + 1,
                    'updated_at' => now(),
                ]);
        }

        $this->loadDealAndProducts();
        $this->message = 'Urutan produk Best Deal berhasil diperbarui!';
    }

    public function moveProduct($productId, $direction)
    {
        if (!$this->deal) return;

        $items = $this->deal->products->values();
        $currentIndex = $items->search(fn($p) => $p->id == $productId);

        if ($currentIndex === false) return;

        $targetIndex = $direction === 'left' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex < 0 || $targetIndex >= $items->count()) return;

        // Swap order
        $currentProduct = $items[$currentIndex];
        $targetProduct = $items[$targetIndex];

        $currentOrder = $currentProduct->pivot->order ?? ($currentIndex + 1);
        $targetOrder = $targetProduct->pivot->order ?? ($targetIndex + 1);

        DB::table('best_deal_products')
            ->where('best_deal_id', $this->deal->id)
            ->where('sponsor_product_id', $currentProduct->id)
            ->update(['order' => $targetOrder, 'updated_at' => now()]);

        DB::table('best_deal_products')
            ->where('best_deal_id', $this->deal->id)
            ->where('sponsor_product_id', $targetProduct->id)
            ->update(['order' => $currentOrder, 'updated_at' => now()]);

        $this->loadDealAndProducts();
        $this->message = 'Urutan produk berhasil digeser.';
    }

    public function sortByHierarchy()
    {
        if (!$this->deal) return;

        $tierRanks = [
            'platinum' => 1,
            'gold' => 2,
            'silver' => 3,
            'partner' => 4,
        ];

        $sorted = $this->deal->products->sort(function ($a, $b) use ($tierRanks) {
            $aIsQuantum = strtolower($a->sponsor?->slug ?? '') === 'quantum' || strtolower($a->sponsor?->name ?? '') === 'quantum';
            $bIsQuantum = strtolower($b->sponsor?->slug ?? '') === 'quantum' || strtolower($b->sponsor?->name ?? '') === 'quantum';

            if ($aIsQuantum && !$bIsQuantum) return -1;
            if (!$aIsQuantum && $bIsQuantum) return 1;

            $aTier = strtolower($a->sponsor?->tier ?? 'partner');
            $bTier = strtolower($b->sponsor?->tier ?? 'partner');

            $aRank = $tierRanks[$aTier] ?? 5;
            $bRank = $tierRanks[$bTier] ?? 5;

            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }

            return ($a->pivot->order ?? 0) <=> ($b->pivot->order ?? 0);
        })->values();

        foreach ($sorted as $index => $prod) {
            DB::table('best_deal_products')
                ->where('best_deal_id', $this->deal->id)
                ->where('sponsor_product_id', $prod->id)
                ->update([
                    'order' => $index + 1,
                    'updated_at' => now(),
                ]);
        }

        $this->loadDealAndProducts();
        $this->message = 'Produk berhasil diurutkan otomatis berdasarkan hierarki sponsor (Platinum -> Gold -> Silver -> Partner)!';
    }

    public function updateProductBadge($productId, $badgeText)
    {
        if (!$this->deal) return;

        DB::table('best_deal_products')
            ->where('best_deal_id', $this->deal->id)
            ->where('sponsor_product_id', $productId)
            ->update([
                'badge_text' => $badgeText,
                'updated_at' => now(),
            ]);

        $this->loadDealAndProducts();
    }

    public function removeProduct($productId)
    {
        if (!$this->deal) return;

        DB::table('best_deal_products')
            ->where('best_deal_id', $this->deal->id)
            ->where('sponsor_product_id', $productId)
            ->delete();

        // Re-sequence remaining
        $remaining = DB::table('best_deal_products')
            ->where('best_deal_id', $this->deal->id)
            ->orderBy('order')
            ->get();

        foreach ($remaining as $idx => $row) {
            DB::table('best_deal_products')
                ->where('id', $row->id)
                ->update(['order' => $idx + 1]);
        }

        $this->loadDealAndProducts();
        $this->message = 'Produk berhasil dikeluarkan dari Best Deal.';
    }

    public function openAddModal()
    {
        $this->searchAvailable = '';
        $this->sponsorFilter = '';
        $this->showAddModal = true;
    }

    public function addProductToDeal($productId)
    {
        if (!$this->deal) return;

        $exists = DB::table('best_deal_products')
            ->where('best_deal_id', $this->deal->id)
            ->where('sponsor_product_id', $productId)
            ->exists();

        if (!$exists) {
            $nextOrder = DB::table('best_deal_products')
                ->where('best_deal_id', $this->deal->id)
                ->max('order') + 1;

            DB::table('best_deal_products')->insert([
                'best_deal_id' => $this->deal->id,
                'sponsor_product_id' => $productId,
                'badge_text' => 'BEST DEAL',
                'order' => $nextOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->loadDealAndProducts();
        $this->message = 'Produk berhasil ditambahkan ke Best Deal!';
    }
};
?>

<div class="space-y-6">
    <!-- Load SortableJS Library -->
    <script src="{{ asset('assets/js/sortable.min.js') }}"></script>

    <!-- Header Halaman -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                    <flux:icon name="sparkles" class="w-7 h-7 text-amber-500" />
                    Katalog & Urutan Produk Best Deal
                </h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                    {{ $dealProducts->count() }} Produk di Best Deal
                </span>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Atur kartu barang yang tampil di baris Best Deal aplikasi ponsel. Anda dapat <strong>klik tahan kartu/gambar dan menggesernya langsung (Drag and Drop)</strong> untuk mengubah urutan tampil di aplikasi ponsel.
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="sortByHierarchy" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700 dark:text-zinc-200 transition flex items-center gap-1.5 cursor-pointer shadow-xs border border-zinc-200 dark:border-zinc-700" title="Urutkan kartu otomatis: Platinum -> Gold -> Silver -> Partner">
                <flux:icon name="bars-arrow-down" class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                <span>Urutkan Sesuai Hierarki Sponsor</span>
            </button>

            <button wire:click="openAddModal" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-sm flex items-center gap-1.5 transition cursor-pointer">
                <flux:icon name="plus" class="w-4 h-4" />
                <span>+ Tambah Produk ke Best Deal</span>
            </button>
        </div>
    </div>

    <!-- Alert / Toast Message -->
    @if($message)
    <div class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 flex items-center justify-between animate-in fade-in duration-200">
        <div class="flex items-center gap-2">
            <flux:icon name="check-circle" class="w-4 h-4 text-emerald-600 shrink-0" />
            <span class="font-medium">{{ $message }}</span>
        </div>
        <button type="button" wire:click="$set('message', '')" class="text-emerald-600 hover:text-emerald-800 dark:hover:text-white text-xs font-bold">✕</button>
    </div>
    @endif

    <!-- Banner Petunjuk Drag and Drop -->
    <div class="p-3.5 rounded-xl bg-gradient-to-r from-amber-500/10 via-indigo-500/10 to-transparent border border-amber-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-zinc-800 dark:text-zinc-200">
        <div class="flex items-center gap-2.5">
            <span class="p-1.5 rounded-lg bg-amber-500 text-white shadow-xs">
                <flux:icon name="arrows-pointing-out" class="w-4 h-4" />
            </span>
            <span>
                <strong>Mode Drag and Drop Aktif:</strong> Arahkan kursor ke kartu mana saja, <strong>klik & tahan</strong> lalu geser ke kiri/kanan. Posisi kartu akan berpindah otomatis dan urutan di aplikasi Flutter langsung berubah!
            </span>
        </div>
        <div class="flex items-center gap-2 text-[11px] font-semibold text-zinc-500 shrink-0">
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Platinum</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Gold</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-zinc-400"></span> Silver</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span> Partner</span>
        </div>
    </div>

    <!-- KONTENER KARTU PRODUK BEST DEAL (SORTABLE GRID) -->
    <div id="best-deal-sortable-grid" wire:ignore.self class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
        @forelse($dealProducts as $idx => $prod)
        @php
            $tier = strtolower($prod->sponsor?->tier ?? 'partner');
            $badgeColor = match($tier) {
                'platinum' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                'gold' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                'silver' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
                default => 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            };
            $currentBadge = $prod->pivot->badge_text ?? 'BEST DEAL';
            $img = $prod->image_path;
            if ($img && !str_starts_with($img, 'http') && !str_starts_with($img, 'assets/')) {
                $img = asset('storage/' . $img);
            } elseif (!$img) {
                $img = asset('assets/images/product_1.png');
            }
        @endphp
        <div 
            class="deal-card group relative bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xs hover:shadow-lg transition-all duration-200 flex flex-col justify-between overflow-hidden cursor-grab active:cursor-grabbing hover:border-amber-400 dark:hover:border-amber-600"
            data-id="{{ $prod->id }}"
        >
            <!-- Card Header: Handle Drag, Nomor Urutan & Tier Sponsor -->
            <div class="p-3 pb-2 flex items-center justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800/60 bg-zinc-50/70 dark:bg-zinc-800/30">
                <div class="flex items-center gap-1.5 drag-handle" title="Tahan dan geser untuk memindahkan kartu">
                    <span class="p-1 rounded-md text-zinc-400 group-hover:text-amber-500 group-hover:bg-amber-50 dark:group-hover:bg-amber-950/40 transition">
                        <flux:icon name="bars-3" class="w-4 h-4" />
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[11px] font-black bg-zinc-200/80 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200 font-mono">
                        #{{ $idx + 1 }}
                    </span>
                </div>

                <div class="flex items-center gap-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase border {{ $badgeColor }}">
                        {{ strtoupper($tier) }}
                    </span>
                    <button type="button" wire:click="removeProduct({{ $prod->id }})" wire:confirm="Keluarkan produk '{{ $prod->name }}' dari daftar Best Deal?" class="p-1 text-zinc-400 hover:text-rose-600 rounded-lg transition" title="Hapus dari Best Deal">
                        <flux:icon name="trash" class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- Card Image (Bisa ditarik langsung) -->
            <div class="p-3 flex items-center justify-center">
                <div class="w-full h-36 rounded-xl bg-zinc-100 dark:bg-zinc-800 overflow-hidden flex items-center justify-center relative border border-zinc-100 dark:border-zinc-800">
                    <img src="{{ $img }}" alt="{{ $prod->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300 pointer-events-none" loading="lazy" />
                    
                    @if($prod->sponsor && (strtolower($prod->sponsor->slug) === 'quantum' || strtolower($prod->sponsor->name) === 'quantum'))
                    <span class="absolute top-2 right-2 px-1.5 py-0.5 rounded bg-blue-600 text-white text-[9px] font-black tracking-wider shadow-xs">
                        ADMIN
                    </span>
                    @endif
                </div>
            </div>

            <!-- Card Body: Nama, Sponsor, & Harga -->
            <div class="p-3 pt-0 flex-1 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 block truncate" title="{{ $prod->sponsor?->name }}">
                        {{ $prod->sponsor?->name ?? 'Mitra VBAT' }}
                    </span>
                    <h4 class="font-bold text-xs text-zinc-900 dark:text-white line-clamp-2 mt-0.5 leading-snug" title="{{ $prod->name }}">
                        {{ $prod->name }}
                    </h4>
                </div>

                <div>
                    <div class="text-xs font-mono font-black text-zinc-900 dark:text-white">
                        Rp {{ number_format($prod->price, 0, ',', '.') }}
                    </div>

                    <!-- Dropdown Pilihan Badge -->
                    <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-800/60" data-no-drag>
                        <label class="block text-[10px] font-bold text-zinc-500 mb-0.5">Label Badge:</label>
                        <select wire:change="updateProductBadge({{ $prod->id }}, $event.target.value)" class="w-full text-[11px] font-bold px-2 py-1 rounded-lg border border-amber-300 dark:border-amber-800 bg-amber-50/50 dark:bg-zinc-900 text-amber-800 dark:text-amber-300 focus:outline-none cursor-pointer">
                            <option value="BEST DEAL" {{ $currentBadge === 'BEST DEAL' ? 'selected' : '' }}>BEST DEAL</option>
                            <option value="HOT DEAL" {{ $currentBadge === 'HOT DEAL' ? 'selected' : '' }}>HOT DEAL</option>
                            <option value="TOP SELLER" {{ $currentBadge === 'TOP SELLER' ? 'selected' : '' }}>TOP SELLER</option>
                            <option value="RECOMMENDED" {{ $currentBadge === 'RECOMMENDED' ? 'selected' : '' }}>RECOMMENDED</option>
                            <option value="HEMAT 25%" {{ $currentBadge === 'HEMAT 25%' ? 'selected' : '' }}>HEMAT 25%</option>
                            <option value="HEMAT 15%" {{ $currentBadge === 'HEMAT 15%' ? 'selected' : '' }}>HEMAT 15%</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Card Footer: Tombol Geser Cepat (Kiri / Kanan) -->
            <div class="px-3 py-1.5 bg-zinc-50 dark:bg-zinc-800/40 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px] text-zinc-400" data-no-drag>
                <button type="button" wire:click="moveProduct({{ $prod->id }}, 'left')" {{ $idx == 0 ? 'disabled' : '' }} class="p-1 hover:text-amber-600 disabled:opacity-20 disabled:cursor-not-allowed transition" title="Geser ke kiri / urutan sebelumnya">
                    <flux:icon name="chevron-left" class="w-4 h-4" />
                </button>
                <span class="text-[10px] font-mono font-medium">Urutan {{ $idx + 1 }}</span>
                <button type="button" wire:click="moveProduct({{ $prod->id }}, 'right')" {{ $idx == $dealProducts->count() - 1 ? 'disabled' : '' }} class="p-1 hover:text-amber-600 disabled:opacity-20 disabled:cursor-not-allowed transition" title="Geser ke kanan / urutan berikutnya">
                    <flux:icon name="chevron-right" class="w-4 h-4" />
                </button>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 text-center bg-white dark:bg-zinc-900 rounded-2xl border border-dashed border-zinc-300 dark:border-zinc-700">
            <flux:icon name="sparkles" class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-2" />
            <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">Belum Ada Produk di Best Deal</h3>
            <p class="text-xs text-zinc-400 mt-1 max-w-sm mx-auto">
                Klik tombol "+ Tambah Produk ke Best Deal" untuk memilih barang sponsor yang ingin dipajang di baris unggulan aplikasi.
            </p>
            <button wire:click="openAddModal" class="mt-4 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5">
                <flux:icon name="plus" class="w-4 h-4" />
                Tambah Produk Sekarang
            </button>
        </div>
        @endforelse
    </div>

    <!-- MODAL PILIH PRODUK UNTUK BEST DEAL -->
    @if($showAddModal)
    <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3 shrink-0">
                <div>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <flux:icon name="plus-circle" class="w-5 h-5 text-amber-500" />
                        Pilih Produk Masuk ke Best Deal
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Pilih suku cadang atau alat servis dari katalog mitra untuk dijadikan Best Deal di aplikasi.
                    </p>
                </div>
                <button type="button" wire:click="$set('showAddModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 shrink-0">
                <div class="relative">
                    <input wire:model.live.debounce.300ms="searchAvailable" type="text" placeholder="Cari nama produk..." class="w-full pl-9 pr-3 py-2 text-xs bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none dark:text-white">
                    <flux:icon name="magnifying-glass" class="w-4 h-4 text-zinc-400 absolute left-3 top-2.5" />
                </div>
                <select wire:model.live="sponsorFilter" class="w-full px-3 py-2 text-xs bg-zinc-50 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl focus:ring-2 focus:ring-amber-500 focus:outline-none dark:text-white">
                    <option value="">Semua Mitra Sponsor</option>
                    @foreach($sponsors as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }} ({{ strtoupper($sp->tier) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- List Produk Tersedia -->
            <div class="flex-1 overflow-y-auto space-y-2 pr-1 divide-y divide-zinc-100 dark:divide-zinc-800">
                @php
                    $existingIds = $dealProducts->pluck('id')->toArray();
                    $availableQuery = SponsorProduct::with('sponsor')
                        ->where('is_active', true)
                        ->whereNotIn('id', $existingIds)
                        ->when($searchAvailable, fn($q) => $q->where('name', 'like', '%'.$searchAvailable.'%'))
                        ->when($sponsorFilter, fn($q) => $q->where('sponsor_id', $sponsorFilter))
                        ->latest();
                    $availableProducts = $availableQuery->get();
                @endphp

                @forelse($availableProducts as $prod)
                <div class="py-2.5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-xl bg-zinc-100 dark:bg-zinc-800 overflow-hidden flex items-center justify-center shrink-0 border border-zinc-200 dark:border-zinc-700">
                            @php
                                $img = $prod->image_path;
                                if ($img && !str_starts_with($img, 'http') && !str_starts_with($img, 'assets/')) {
                                    $img = asset('storage/' . $img);
                                } elseif (!$img) {
                                    $img = asset('assets/images/product_1.png');
                                }
                            @endphp
                            <img src="{{ $img }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <h5 class="font-bold text-xs text-zinc-900 dark:text-white truncate">{{ $prod->name }}</h5>
                            <div class="flex items-center gap-2 text-[11px] text-zinc-500 mt-0.5">
                                <span>{{ $prod->sponsor?->name }}</span>
                                <span>•</span>
                                <span class="font-mono font-bold text-zinc-700 dark:text-zinc-300">Rp {{ number_format($prod->price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" wire:click="addProductToDeal({{ $prod->id }})" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition shrink-0 flex items-center gap-1">
                        <flux:icon name="plus" class="w-3.5 h-3.5" />
                        <span>Pilih</span>
                    </button>
                </div>
                @empty
                <div class="py-12 text-center text-zinc-400 text-xs">
                    Semua produk sudah masuk Best Deal atau tidak ditemukan produk sesuai pencarian.
                </div>
                @endforelse
            </div>

            <!-- Footer Modal -->
            <div class="flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700 pt-3 shrink-0">
                <span class="text-xs text-zinc-500">
                    Produk terpilih otomatis tersimpan dan dapat digeser posisinya di grid.
                </span>
                <button type="button" wire:click="$set('showAddModal', false)" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white text-xs font-bold rounded-xl transition">
                    Selesai
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

@script
<script>
    let sortable = null;

    function setupSortable() {
        const grid = document.getElementById('best-deal-sortable-grid');
        if (!grid) return;

        if (typeof Sortable === 'undefined') {
            // Jika Sortable belum siap, tunggu sebentar
            setTimeout(setupSortable, 100);
            return;
        }

        if (sortable) {
            sortable.destroy();
        }

        sortable = new Sortable(grid, {
            animation: 250,
            draggable: '.deal-card',
            filter: 'button, select, input, a, [data-no-drag]',
            preventOnFilter: false,
            ghostClass: 'opacity-30',
            chosenClass: 'ring-2',
            dragClass: 'shadow-2xl',
            onEnd: function (evt) {
                const items = grid.querySelectorAll('.deal-card');
                const orderedIds = Array.from(items).map(el => parseInt(el.dataset.id));
                $wire.reorderProducts(orderedIds);
            }
        });
    }

    // Jalankan saat pertama kali dimuat
    setupSortable();

    // Pastikan tetap berjalan setelah morph/update Livewire
    Livewire.hook('morph.updated', () => {
        setupSortable();
    });
</script>
@endscript
