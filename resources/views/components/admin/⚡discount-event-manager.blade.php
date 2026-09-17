<?php

use Livewire\Component;
use App\Models\DiscountEvent;
use App\Models\SponsorProduct;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $events;
    public $showModal = false;
    public $editingId = null;

    // Form Event
    public $name = '';
    public $discount_type = 'percentage';
    public $discount_value = 10;
    public $banner_text = '';
    public $start_at = '';
    public $end_at = '';
    public $is_active = true;

    // Modal Kelola Produk Event
    public $showProductModal = false;
    public $managingEventId = null;
    public $managingEvent = null;
    public $searchProduct = '';
    public $selectedProductIds = [];

    public function mount()
    {
        $this->loadEvents();
    }

    public function loadEvents()
    {
        $this->events = DiscountEvent::withCount('products')->latest()->get();
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'discount_type', 'discount_value', 'banner_text']);
        $this->discount_type = 'percentage';
        $this->discount_value = 10;
        $this->is_active = true;
        $this->start_at = now()->format('Y-m-d\TH:i');
        $this->end_at = now()->addDays(7)->format('Y-m-d\TH:i');
        $this->showModal = true;
    }

    public function editEvent($id)
    {
        $e = DiscountEvent::findOrFail($id);
        $this->editingId = $e->id;
        $this->name = $e->name;
        $this->discount_type = $e->discount_type;
        $this->discount_value = (float)$e->discount_value;
        $this->banner_text = $e->banner_text;
        $this->start_at = $e->start_at->format('Y-m-d\TH:i');
        $this->end_at = $e->end_at->format('Y-m-d\TH:i');
        $this->is_active = (bool)$e->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed_nominal',
            'discount_value' => 'required|numeric|min:1',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
        ]);

        $bannerText = $this->banner_text ?: ($this->discount_type === 'percentage'
            ? "DISKON SPESIAL {$this->discount_value}% PRODUK PILIHAN"
            : "POTONGAN RP " . number_format($this->discount_value) . " PRODUK PILIHAN");

        if ($this->editingId) {
            DiscountEvent::findOrFail($this->editingId)->update([
                'name' => $this->name,
                'discount_type' => $this->discount_type,
                'discount_value' => $this->discount_value,
                'banner_text' => $bannerText,
                'start_at' => $this->start_at,
                'end_at' => $this->end_at,
                'is_active' => $this->is_active,
            ]);
        } else {
            $created = DiscountEvent::create([
                'name' => $this->name,
                'discount_type' => $this->discount_type,
                'discount_value' => $this->discount_value,
                'banner_text' => $bannerText,
                'start_at' => $this->start_at,
                'end_at' => $this->end_at,
                'is_active' => $this->is_active,
            ]);
            $this->managingEventId = $created->id;
        }

        $this->showModal = false;
        $this->loadEvents();
    }

    public function openProductManager($eventId)
    {
        $this->managingEventId = $eventId;
        $this->managingEvent = DiscountEvent::with('products.sponsor')->findOrFail($eventId);
        $this->selectedProductIds = $this->managingEvent->products->pluck('id')->toArray();
        $this->searchProduct = '';
        $this->showProductModal = true;
    }

    public function toggleProductToEvent($productId)
    {
        if (!$this->managingEventId) return;

        $exists = DB::table('discount_event_products')
            ->where('discount_event_id', $this->managingEventId)
            ->where('sponsor_product_id', $productId)
            ->exists();

        if ($exists) {
            DB::table('discount_event_products')
                ->where('discount_event_id', $this->managingEventId)
                ->where('sponsor_product_id', $productId)
                ->delete();
        } else {
            DB::table('discount_event_products')->insert([
                'discount_event_id' => $this->managingEventId,
                'sponsor_product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->managingEvent = DiscountEvent::with('products.sponsor')->findOrFail($this->managingEventId);
        $this->selectedProductIds = $this->managingEvent->products->pluck('id')->toArray();
        $this->loadEvents();
    }

    public function deleteEvent($id)
    {
        DiscountEvent::findOrFail($id)->delete();
        $this->loadEvents();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" class="font-bold">Manajemen Event & Diskon Shop</flux:heading>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                    Bikin Event -> Pilih Produk
                </span>
            </div>
            <flux:subheading size="sm" class="text-zinc-500 dark:text-zinc-400">
                Atur program event diskon khusus. Hanya produk yang Anda pilih yang akan mendapatkan harga diskon dan tampil di section promo event.
            </flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
            Buat Event Baru
        </flux:button>
    </div>

    <!-- Tabel Daftar Event -->
    <div class="bg-white dark:bg-zinc-800/80 rounded-2xl border border-zinc-200 dark:border-zinc-700/80 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs font-semibold text-zinc-500 uppercase bg-zinc-50/50 dark:bg-zinc-900/40">
                        <th class="py-3.5 px-4">Nama Event & Banner</th>
                        <th class="py-3.5 px-4">Tipe & Nilai Diskon</th>
                        <th class="py-3.5 px-4">Periode Aktif</th>
                        <th class="py-3.5 px-4 text-center">Produk Terpilih</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/60">
                    @forelse($events as $e)
                    @php
                        $now = now();
                        $isActiveTime = $e->start_at <= $now && $e->end_at >= $now;
                        $isUpcoming = $e->start_at > $now;
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                        <td class="py-4 px-4">
                            <div class="font-bold text-zinc-900 dark:text-white">{{ $e->name }}</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400 mt-0.5 font-medium">"{{ $e->banner_text }}"</div>
                        </td>
                        <td class="py-4 px-4">
                            @if($e->discount_type === 'percentage')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                    Diskon {{ round($e->discount_value) }}%
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                                    Potongan Rp {{ number_format($e->discount_value, 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                            {{ $e->start_at->format('d M Y H:i') }} <br>s/d {{ $e->end_at->format('d M Y H:i') }}
                        </td>
                        <td class="py-4 px-4 text-center">
                            <button type="button" wire:click="openProductManager({{ $e->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-900/60 border border-blue-200 dark:border-blue-800 transition">
                                <flux:icon name="shopping-bag" class="w-3.5 h-3.5" />
                                <span>{{ $e->products_count }} Produk</span>
                                <flux:icon name="chevron-right" class="w-3 h-3 text-blue-400" />
                            </button>
                        </td>
                        <td class="py-4 px-4 text-center">
                            @if(!$e->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Nonaktif</span>
                            @elseif($isActiveTime)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 animate-pulse">Sedang Berjalan</span>
                            @elseif($isUpcoming)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-400">Mendatang</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">Selesai</span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="openProductManager({{ $e->id }})" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40" title="Kelola Produk Event">
                                    <flux:icon name="squares-plus" class="w-4 h-4" />
                                </button>
                                <button type="button" wire:click="editEvent({{ $e->id }})" class="p-1.5 rounded-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700" title="Edit Event">
                                    <flux:icon name="pencil" class="w-4 h-4" />
                                </button>
                                <button type="button" wire:click="deleteEvent({{ $e->id }})" wire:confirm="Yakin ingin menghapus event ini?" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="Hapus Event">
                                    <flux:icon name="trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-zinc-400 text-sm">
                            Belum ada program Event Diskon. Klik "Buat Event Baru" di atas.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: Form Buat / Edit Event -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-zinc-200 dark:border-zinc-700 space-y-4">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                <h3 class="font-bold text-lg text-zinc-900 dark:text-white">
                    {{ $editingId ? 'Edit Event Diskon' : 'Buat Event Diskon Baru' }}
                </h3>
                <button type="button" wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit.prevent="save" class="space-y-4">
                <flux:input label="Nama Event Diskon" wire:model="name" placeholder="Contoh: FLASH SALE SPESIAL VBAT" required />

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">Tipe Diskon</label>
                        <select wire:model="discount_type" class="w-full px-3 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="percentage">Persentase (%)</option>
                            <option value="fixed_nominal">Potongan Nominal (Rp)</option>
                        </select>
                    </div>
                    <flux:input type="number" step="any" label="Nilai Diskon ({{ $discount_type === 'percentage' ? '%' : 'Rp' }})" wire:model="discount_value" required />
                </div>

                <flux:input label="Teks Banner / Headline Promo" wire:model="banner_text" placeholder="Contoh: DISKON SPESIAL 15% PRODUK PILIHAN!" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="datetime-local" label="Waktu Mulai" wire:model="start_at" required />
                    <flux:input type="datetime-local" label="Waktu Berakhir" wire:model="end_at" required />
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" id="is_active_evt" wire:model="is_active" class="rounded border-zinc-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <label for="is_active_evt" class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Aktifkan Event Ini Sekarang</label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="button" wire:click="$set('showModal', false)">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Simpan Event</flux:button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL 2: Product Picker (Pilih Produk yang Masuk ke Event) -->
    @if($showProductModal && $managingEvent)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm animate-fade-in">
        <div class="bg-white dark:bg-zinc-800 rounded-3xl max-w-4xl w-full p-6 shadow-2xl border border-zinc-200 dark:border-zinc-700 space-y-4 max-h-[92vh] flex flex-col">
            <!-- Header Modal Produk -->
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-lg text-zinc-900 dark:text-white">Pilih Produk Untuk Event Diskon</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                            {{ $managingEvent->name }} ({{ $managingEvent->discount_type === 'percentage' ? round($managingEvent->discount_value).'%' : 'Rp '.number_format($managingEvent->discount_value) }})
                        </span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Centang atau klik "Tambahkan" pada produk yang ingin diberi diskon. Total {{ count($selectedProductIds) }} produk terpilih.
                    </p>
                </div>
                <button type="button" wire:click="$set('showProductModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1">
                    <flux:icon name="x-mark" class="w-6 h-6" />
                </button>
            </div>

            <!-- Search Bar Produk -->
            <div class="shrink-0">
                <flux:input icon="magnifying-glass" wire:model.live.debounce.300ms="searchProduct" placeholder="Cari nama sparepart, LCD, baterai, atau nama sponsor..." />
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
                    $basePrice = (float)$p->price;
                    if ($managingEvent->discount_type === 'percentage') {
                        $discountedPrice = round($basePrice * (1 - ($managingEvent->discount_value / 100)));
                    } else {
                        $discountedPrice = max(0, $basePrice - (float)$managingEvent->discount_value);
                    }
                @endphp
                <div class="py-3 flex items-center justify-between gap-4 {{ $isAttached ? 'bg-emerald-50/40 dark:bg-emerald-950/20 -mx-2 px-2 rounded-xl' : '' }}">
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
                                <span class="line-through text-zinc-400">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">-> Rp {{ number_format($discountedPrice, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="shrink-0">
                        @if($isAttached)
                            <button type="button" wire:click="toggleProductToEvent({{ $p->id }})" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-rose-600 text-white shadow-xs group transition flex items-center gap-1">
                                <span class="group-hover:hidden">✓ Terdaftar</span>
                                <span class="hidden group-hover:inline">✕ Hapus</span>
                            </button>
                        @else
                            <button type="button" wire:click="toggleProductToEvent({{ $p->id }})" class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-zinc-100 hover:bg-blue-600 text-zinc-700 hover:text-white dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-blue-600 transition flex items-center gap-1">
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
                    Perubahan disimpan otomatis secara instan.
                </span>
                <flux:button variant="primary" wire:click="$set('showProductModal', false)">
                    Selesai Memilih
                </flux:button>
            </div>
        </div>
    </div>
    @endif
</div>