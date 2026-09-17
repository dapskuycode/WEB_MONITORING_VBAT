<?php

use Livewire\Component;
use App\Models\BestDeal;
use App\Models\SponsorProduct;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $bestDeals;
    public $showModal = false;
    public $editingId = null;

    // Form Best Deal Program
    public $title = '';
    public $description = '';
    public $start_at = '';
    public $end_at = '';
    public $is_active = true;

    // Modal Product Picker
    public $showProductModal = false;
    public $managingDealId = null;
    public $managingDeal = null;
    public $searchProduct = '';
    public $selectedProductIds = [];

    public function mount()
    {
        $this->loadDeals();
    }

    public function loadDeals()
    {
        $this->bestDeals = BestDeal::withCount('products')->latest()->get();
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'title', 'description']);
        $this->is_active = true;
        $this->start_at = now()->format('Y-m-d\TH:i');
        $this->end_at = now()->addMonths(1)->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function editDeal($id)
    {
        $d = BestDeal::findOrFail($id);
        $this->editingId = $d->id;
        $this->title = $d->title;
        $this->description = $d->description;
        $this->start_at = $d->start_at ? $d->start_at->format('Y-m-d\TH:i') : '';
        $this->end_at = $d->end_at ? $d->end_at->format('Y-m-d\TH:i') : '';
        $this->is_active = (bool)$d->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($this->editingId) {
            BestDeal::findOrFail($this->editingId)->update([
                'title' => $this->title,
                'description' => $this->description,
                'start_at' => $this->start_at ?: null,
                'end_at' => $this->end_at ?: null,
                'is_active' => $this->is_active,
            ]);
        } else {
            $created = BestDeal::create([
                'title' => $this->title,
                'description' => $this->description,
                'banner_path' => 'assets/images/banner_promo_diskon.png',
                'start_at' => $this->start_at ?: null,
                'end_at' => $this->end_at ?: null,
                'is_active' => $this->is_active,
            ]);
            $this->managingDealId = $created->id;
        }

        $this->showModal = false;
        $this->loadDeals();
    }

    public function openProductManager($dealId)
    {
        $this->managingDealId = $dealId;
        $this->managingDeal = BestDeal::with('products.sponsor')->findOrFail($dealId);
        $this->selectedProductIds = $this->managingDeal->products->pluck('id')->toArray();
        $this->searchProduct = '';
        $this->showProductModal = true;
    }

    public function toggleProductToDeal($productId)
    {
        if (!$this->managingDealId) return;

        $exists = DB::table('best_deal_products')
            ->where('best_deal_id', $this->managingDealId)
            ->where('sponsor_product_id', $productId)
            ->exists();

        if ($exists) {
            DB::table('best_deal_products')
                ->where('best_deal_id', $this->managingDealId)
                ->where('sponsor_product_id', $productId)
                ->delete();
        } else {
            $nextOrder = DB::table('best_deal_products')
                ->where('best_deal_id', $this->managingDealId)
                ->max('order') + 1;

            DB::table('best_deal_products')->insert([
                'best_deal_id' => $this->managingDealId,
                'sponsor_product_id' => $productId,
                'badge_text' => 'BEST DEAL',
                'order' => $nextOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->managingDeal = BestDeal::with('products.sponsor')->findOrFail($this->managingDealId);
        $this->selectedProductIds = $this->managingDeal->products->pluck('id')->toArray();
        $this->loadDeals();
    }

    public function updateProductBadge($productId, $badgeText)
    {
        if (!$this->managingDealId) return;

        DB::table('best_deal_products')
            ->where('best_deal_id', $this->managingDealId)
            ->where('sponsor_product_id', $productId)
            ->update([
                'badge_text' => $badgeText,
                'updated_at' => now(),
            ]);

        $this->managingDeal = BestDeal::with('products.sponsor')->findOrFail($this->managingDealId);
    }

    public function deleteDeal($id)
    {
        BestDeal::findOrFail($id)->delete();
        $this->loadDeals();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" class="font-bold">Manajemen Program Best Deal</flux:heading>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300">
                    Bikin Best Deal -> Pilih Produk Masuk
                </span>
            </div>
            <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">
                Kurasi produk unggulan sponsor ke dalam katalog Best Deal khusus aplikasi ponsel. Terpisah dari program Event Diskon.
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
            Buat Program Best Deal
        </flux:button>
    </div>

    <!-- Tabel Daftar Program Best Deal -->
    <div class="bg-white dark:bg-zinc-800/80 rounded-2xl border border-zinc-200 dark:border-zinc-700/80 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs font-semibold text-zinc-500 uppercase bg-zinc-50/50 dark:bg-zinc-900/40">
                        <th class="py-3.5 px-4">Program Best Deal</th>
                        <th class="py-3.5 px-4">Deskripsi Program</th>
                        <th class="py-3.5 px-4">Periode Tayang</th>
                        <th class="py-3.5 px-4 text-center">Produk Terpilih</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                    @forelse($bestDeals as $d)
                    @php
                        $now = now();
                        $isActive = $d->is_active && (!$d->start_at || $d->start_at <= $now) && (!$d->end_at || $d->end_at >= $now);
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                        <td class="py-4 px-4">
                            <div class="font-bold text-zinc-900 dark:text-white">{{ $d->title }}</div>
                            <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Katalog Best Deal</span>
                        </td>
                        <td class="py-4 px-4 text-xs text-zinc-500 dark:text-zinc-400 max-w-xs truncate">
                            {{ $d->description ?: '-' }}
                        </td>
                        <td class="py-4 px-4 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                            {{ $d->start_at ? $d->start_at->format('d M Y') : 'Fleksibel' }} <br>
                            s/d {{ $d->end_at ? $d->end_at->format('d M Y') : 'Seterusnya' }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            <button type="button" wire:click="openProductManager({{ $d->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 transition">
                                <flux:icon name="sparkles" class="w-3.5 h-3.5 text-amber-600" />
                                <span>{{ $d->products_count }} Produk</span>
                                <flux:icon name="chevron-right" class="w-3 h-3 text-amber-400" />
                            </button>
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if($isActive)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 animate-pulse">Aktif</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Nonaktif</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="openProductManager({{ $d->id }})" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40" title="Kelola Produk Best Deal">
                                    <flux:icon name="squares-plus" class="w-4 h-4" />
                                </button>
                                <button type="button" wire:click="editDeal({{ $d->id }})" class="p-1.5 rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700" title="Edit Program">
                                    <flux:icon name="pencil" class="w-4 h-4" />
                                </button>
                                <button type="button" wire:click="deleteDeal({{ $d->id }})" wire:confirm="Yakin ingin menghapus program Best Deal ini?" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Hapus">
                                    <flux:icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-zinc-400 text-sm">
                            Belum ada program Best Deal. Klik "Buat Program Best Deal" di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Form Buat / Edit Best Deal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-zinc-200 dark:border-zinc-700 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                <h3 class="font-bold text-lg text-zinc-900 dark:text-white">
                    {{ $editingId ? 'Edit Program Best Deal' : 'Buat Program Best Deal Baru' }}
                </h3>
                <button type="button" wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                <flux:input label="Judul Program Best Deal" wire:model="title" placeholder="Contoh: SUPER BEST DEALS 2026" required />

                <flux:textarea label="Deskripsi / Highlight Promo" wire:model="description" placeholder="Informasi singkat tentang katalog produk pilihan..." rows="2" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="datetime-local" label="Waktu Mulai" wire:model="start_at" />
                    <flux:input type="datetime-local" label="Waktu Berakhir" wire:model="end_at" />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="is_active_bd" wire:model="is_active" class="rounded border-zinc-300 text-amber-600 shadow-sm focus:ring-amber-500">
                    <label for="is_active_bd" class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Aktifkan Program Ini Sekarang</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="button" wire:click="$set('showModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan Program</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL 2: Product Picker (Pilih Produk yang Masuk ke Best Deal) -->
    @if($showProductModal && $managingDeal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
        <div class="bg-white dark:bg-zinc-800 rounded-3xl max-w-4xl w-full p-6 shadow-2xl border border-zinc-200 dark:border-zinc-700 space-y-4 max-h-[92vh] flex flex-col">
            <!-- Header Modal Produk -->
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-lg text-zinc-900 dark:text-white">Pilih Produk Untuk Masuk Best Deal</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                            {{ $managingDeal->title }}
                        </span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Produk yang Anda pilih di sini akan secara eksklusif tampil di halaman dan carousel Best Deal di ponsel. Total {{ count($selectedProductIds) }} produk terpilih.
                    </p>
                </div>
                <button type="button" wire:click="$set('showProductModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1">
                    <flux:icon name="x-mark" class="w-6 h-6" />
                </button>
            </div>

            <!-- Search Bar Produk -->
            <div class="shrink-0">
                <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="searchProduct" placeholder="Cari nama produk, sparepart, atau nama sponsor..." />
            </div>

            <!-- List Produk Master & Status Terpilih -->
            <div class="flex-1 overflow-y-auto space-y-2 pr-1 divide-y divide-zinc-100 dark:divide-zinc-700/60">
                @php
                    $allProducts = SponsorProduct::with('sponsor')
                        ->where('is_active', true)
                        ->when($searchProduct, function($q) {
                            $q->where('name', 'like', '%'.$this->searchProduct.'%')
                              ->orWhereHas('sponsor', fn($sq) => $sq->where('name', 'like', '%'.$this->searchProduct.'%'));
                        })
                        ->orderBy('name')
                        ->get();
                @endphp

                @forelse($allProducts as $p)
                @php
                    $isAttached = in_array($p->id, $selectedProductIds);
                    $dealProduct = $isAttached ? $managingDeal->products->firstWhere('id', $p->id) : null;
                    $badgeText = $dealProduct ? $dealProduct->pivot->badge_text : 'BEST DEAL';
                @endphp
                <div class="py-3 flex items-center justify-between gap-4 {{ $isAttached ? 'bg-amber-50/40 dark:bg-amber-950/20 -mx-2 px-2 rounded-xl' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-zinc-100 dark:bg-zinc-700 overflow-hidden flex items-center justify-center shrink-0 border border-zinc-200 dark:border-zinc-600">
                            @if($p->image_path)
                                <img src="{{ str_starts_with($p->image_path, 'http') ? $p->image_path : asset($p->image_path) }}" class="w-full h-full object-cover" alt="{{ $p->name }}" />
                            @else
                                <flux:icon name="shopping-bag" class="w-5 h-5 text-zinc-400" />
                            @endif
                        </div>
                        <div>
                            <div class="font-bold text-sm text-zinc-900 dark:text-white">{{ $p->name }}</div>
                            <div class="flex items-center gap-2 text-xs text-zinc-500 mt-0.5">
                                <span>{{ $p->sponsor?->name }}</span>
                                <span>•</span>
                                <span class="font-mono text-zinc-800 dark:text-zinc-200 font-semibold">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($isAttached)
                            <!-- Ubah Badge Kustom -->
                            <select wire:change="updateProductBadge({{ $p->id }}, $event.target.value)" class="text-xs px-2 py-1 rounded-lg border border-amber-300 dark:border-amber-800 bg-white dark:bg-zinc-900 font-semibold text-amber-700 dark:text-amber-400">
                                <option value="BEST DEAL" {{ $badgeText === 'BEST DEAL' ? 'selected' : '' }}>Badge: BEST DEAL</option>
                                <option value="HOT DEAL" {{ $badgeText === 'HOT DEAL' ? 'selected' : '' }}>Badge: HOT DEAL</option>
                                <option value="TOP SELLER" {{ $badgeText === 'TOP SELLER' ? 'selected' : '' }}>Badge: TOP SELLER</option>
                                <option value="RECOMMENDED" {{ $badgeText === 'RECOMMENDED' ? 'selected' : '' }}>Badge: RECOMMENDED</option>
                                <option value="HEMAT 25%" {{ $badgeText === 'HEMAT 25%' ? 'selected' : '' }}>Badge: HEMAT 25%</option>
                            </select>

                            <button type="button" wire:click="toggleProductToDeal({{ $p->id }})" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-600 hover:bg-rose-600 text-white shadow-xs group transition flex items-center gap-1">
                                <span class="group-hover:hidden">✓ Masuk Best Deal</span>
                                <span class="hidden group-hover:inline">✕ Hapus</span>
                            </button>
                        @else
                            <button type="button" wire:click="toggleProductToDeal({{ $p->id }})" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-zinc-100 hover:bg-amber-600 text-zinc-700 hover:text-white dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-amber-600 transition flex items-center gap-1">
                                <flux:icon name="plus" class="w-3.5 h-3.5" />
                                <span>Pilih</span>
                            </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-12 text-center text-zinc-400 text-sm">
                    Tidak ditemukan produk yang sesuai pencarian.
                </div>
                @endforelse
            </div>

            <!-- Footer Modal -->
            <div class="flex items-center justify-between border-t border-zinc-200 dark:border-zinc-700 pt-3 shrink-0">
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    Produk tersimpan otomatis ke dalam daftar Best Deal aplikasi.
                </span>
                <flux:button variant="primary" wire:click="$set('showProductModal', false)">
                    Selesai Memilih
                </flux:button>
            </div>
        </div>
    </div>
    @endif
</div>
