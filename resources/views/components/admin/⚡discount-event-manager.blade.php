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
    public $publish_mode = 'instant'; // 'instant' | 'scheduled'
    public $start_at = '';
    public $end_at = '';
    public $is_active = true;

    // Modal Kelola Produk Event
    public $showProductModal = false;
    public $managingEventId = null;
    public $managingEvent = null;
    public $searchProduct = '';
    public $selectedProductIds = [];

    // Filter & Search
    public $search = '';
    public $typeFilter = '';
    public $statusFilter = '';

    public function mount()
    {
        $this->loadEvents();
    }

    public function updatedSearch() { $this->loadEvents(); }
    public function updatedTypeFilter() { $this->loadEvents(); }
    public function updatedStatusFilter() { $this->loadEvents(); }

    public function loadEvents()
    {
        $query = DiscountEvent::withCount('products')->latest();

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->typeFilter)) {
            $query->where('discount_type', $this->typeFilter);
        }

        if (!empty($this->statusFilter)) {
            $now = now();
            if ($this->statusFilter === 'active') {
                $query->where('is_active', true)
                      ->where('start_at', '<=', $now)
                      ->where('end_at', '>=', $now);
            } elseif ($this->statusFilter === 'finished') {
                $query->where(function($q) use ($now) {
                    $q->where('is_active', false)
                      ->orWhere('end_at', '<', $now);
                });
            }
        }

        $this->events = $query->get();
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'discount_type', 'discount_value', 'banner_text']);
        $this->publish_mode = 'instant';
        $this->discount_type = 'percentage';
        $this->discount_value = 10;
        $this->is_active = true;
        $now = now()->setTimezone('Asia/Jakarta');
        $this->start_at = $now->format('Y-m-d\TH:i');
        $this->end_at = $now->copy()->addDays(7)->format('Y-m-d\TH:i');
        $this->resetValidation();
        $this->showModal = true;
    }

    public function editEvent($id)
    {
        $this->resetValidation();
        $e = DiscountEvent::findOrFail($id);
        $this->editingId = $e->id;
        $this->name = $e->name;
        $this->discount_type = $e->discount_type;
        $this->discount_value = (float)$e->discount_value;
        $this->banner_text = $e->banner_text;

        $now = now();
        if ($e->start_at && $e->start_at > $now) {
            $this->publish_mode = 'scheduled';
            $this->start_at = $e->start_at->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i');
        } else {
            $this->publish_mode = 'instant';
            $this->start_at = now()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i');
        }

        if (!$e->end_at || $e->end_at <= $now) {
            $this->end_at = now()->setTimezone('Asia/Jakarta')->addDays(7)->format('Y-m-d\TH:i');
        } else {
            $this->end_at = $e->end_at->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i');
        }

        $this->is_active = (bool)$e->is_active;
        $this->showModal = true;
    }

    public function updatedPublishMode($val)
    {
        $this->resetValidation();
        $now = now()->setTimezone('Asia/Jakarta');
        if ($val === 'instant') {
            $this->start_at = $now->format('Y-m-d\TH:i');
            try {
                if (empty($this->end_at) || \Illuminate\Support\Carbon::parse($this->end_at)->lte($now)) {
                    $this->end_at = $now->copy()->addDays(7)->format('Y-m-d\TH:i');
                }
            } catch (\Exception $e) {
                $this->end_at = $now->copy()->addDays(7)->format('Y-m-d\TH:i');
            }
        } else {
            if (empty($this->start_at)) {
                $this->start_at = $now->format('Y-m-d\TH:i');
            }
            try {
                $start = \Illuminate\Support\Carbon::parse($this->start_at);
                if (empty($this->end_at) || \Illuminate\Support\Carbon::parse($this->end_at)->lte($start)) {
                    $this->end_at = $start->copy()->addDays(7)->format('Y-m-d\TH:i');
                }
            } catch (\Exception $e) {
                $this->end_at = $now->copy()->addDays(7)->format('Y-m-d\TH:i');
            }
        }
    }

    public function updatedStartAt($val)
    {
        try {
            $start = \Illuminate\Support\Carbon::parse($val);
            if (!empty($this->end_at)) {
                $end = \Illuminate\Support\Carbon::parse($this->end_at);
                if ($end->lte($start)) {
                    $this->end_at = $start->copy()->addDays(7)->format('Y-m-d\TH:i');
                }
            } else {
                $this->end_at = $start->copy()->addDays(7)->format('Y-m-d\TH:i');
            }
        } catch (\Exception $e) {}
    }

    public function setEndDurationDays(int $days)
    {
        $base = ($this->publish_mode === 'scheduled' && !empty($this->start_at))
            ? \Illuminate\Support\Carbon::parse($this->start_at)
            : now()->setTimezone('Asia/Jakarta');

        $this->end_at = $base->copy()->addDays($days)->format('Y-m-d\TH:i');
        $this->resetValidation('end_at');
    }

    public function toggleStatus($id)
    {
        $e = DiscountEvent::findOrFail($id);
        $e->is_active = !$e->is_active;
        $e->save();
        $this->loadEvents();
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed_nominal',
            'discount_value' => 'required|numeric|min:1',
            'publish_mode' => 'required|in:instant,scheduled',
        ];

        if ($this->publish_mode === 'instant') {
            $rules['end_at'] = 'required|date|after:now';
            $startTime = now()->subMinutes(1);
            $isActive = true;
        } else {
            $rules['start_at'] = 'required|date';
            $rules['end_at'] = 'required|date|after:start_at';
            $startTime = $this->start_at;
            $isActive = true;
        }

        $messages = [
            'name.required' => 'Nama event wajib diisi.',
            'discount_value.required' => 'Nilai diskon wajib diisi.',
            'discount_value.min' => 'Nilai diskon minimal 1.',
            'start_at.required' => 'Waktu mulai promo wajib ditentukan.',
            'end_at.required' => 'Waktu berakhir promo wajib ditentukan.',
            'end_at.after' => $this->publish_mode === 'instant'
                ? 'Waktu berakhir harus lebih dari waktu saat ini.'
                : 'Waktu berakhir promo harus setelah waktu mulai.',
        ];

        $this->validate($rules, $messages);

        $bannerText = $this->banner_text ?: ($this->discount_type === 'percentage'
            ? "DISKON SPESIAL {$this->discount_value}% PRODUK PILIHAN"
            : "POTONGAN RP " . number_format($this->discount_value) . " PRODUK PILIHAN");

        $data = [
            'name' => $this->name,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'banner_text' => $bannerText,
            'start_at' => $startTime,
            'end_at' => $this->end_at,
            'is_active' => $isActive,
        ];

        if ($this->editingId) {
            DiscountEvent::findOrFail($this->editingId)->update($data);
        } else {
            $created = DiscountEvent::create($data);
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

    public function getAvailableProductsProperty()
    {
        return SponsorProduct::with('sponsor')
            ->when($this->searchProduct, function ($q) {
                $q->where('name', 'like', '%' . $this->searchProduct . '%');
            })
            ->latest()
            ->take(30)
            ->get();
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

<div class="space-y-6 text-[#212121] dark:text-zinc-100">
    <!-- Search & Filter Bar with Action Button -->
    <div class="p-3.5 sm:p-4 bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-zinc-500">
                <i class="fas fa-search text-sm"></i>
            </div>
            <input 
                type="text" 
                wire:model.live.debounce.250ms="search" 
                placeholder="Cari nama event..." 
                class="w-full pl-10 pr-4 py-2 text-sm text-[#212121] dark:text-zinc-100 placeholder-slate-400 dark:placeholder-zinc-500 bg-slate-50/70 dark:bg-zinc-800/80 rounded-lg border border-slate-200 dark:border-zinc-700 focus:outline-none focus:bg-white dark:focus:bg-zinc-800 focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all"
            >
        </div>

        <!-- Filter Dropdowns & Action Button on Right -->
        <div class="flex items-center gap-2.5 shrink-0">
            <select 
                wire:model.live="typeFilter" 
                class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer transition"
            >
                <option value="">Semua Tipe Diskon</option>
                <option value="percentage">Persentase (%)</option>
                <option value="fixed_nominal">Nominal (Rp)</option>
            </select>

            <select 
                wire:model.live="statusFilter" 
                class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-[#212121] dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer transition"
            >
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="finished">Selesai</option>
            </select>

            <button 
                type="button"
                wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors"
            >
                <i class="fas fa-plus"></i>
                <span>Buat Event Baru</span>
            </button>
        </div>
    </div>

    <!-- Events Table -->
    <div class="bg-white dark:bg-zinc-900 rounded-[12px] border border-slate-200/80 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[650px]">
                <thead>
                    <tr class="bg-slate-50/70 dark:bg-zinc-800/60 border-b border-slate-200 dark:border-zinc-800">
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] dark:text-zinc-200 uppercase tracking-wide">Nama Event & Banner</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] dark:text-zinc-200 uppercase tracking-wide">Tipe & Nilai Diskon</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] dark:text-zinc-200 uppercase tracking-wide">Periode Aktif</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-[#212121] dark:text-zinc-200 uppercase tracking-wide">Produk Terpilih</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-[#212121] dark:text-zinc-200 uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-[#212121] dark:text-zinc-200 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                    @forelse($events as $e)
                    @php
                        $now = now();
                        $isActiveTime = $e->start_at <= $now && $e->end_at >= $now;
                        $isUpcoming = $e->start_at > $now;
                    @endphp
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                        <td class="px-4 py-4">
                            <div class="font-semibold text-sm text-[#212121] dark:text-zinc-100">{{ $e->name }}</div>
                            <div class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 line-clamp-1">"{{ $e->banner_text }}"</div>
                        </td>
                        <td class="px-4 py-4">
                            @if($e->discount_type === 'percentage')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-50 dark:bg-orange-950/40 text-orange-700 dark:text-orange-400 border border-orange-200 dark:border-orange-900/60">
                                    Diskon {{ round($e->discount_value) }}%
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-900/60">
                                    Potongan Rp {{ number_format($e->discount_value, 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-calendar-alt text-slate-400 dark:text-zinc-500 text-sm mt-0.5"></i>
                                <div class="text-sm">
                                    <div class="text-[#212121] dark:text-zinc-200 font-medium">{{ $e->start_at->format('d M Y H:i') }}</div>
                                    <div class="text-[#828282] dark:text-zinc-400">{{ $e->end_at->format('d M Y H:i') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <button type="button" wire:click="openProductManager({{ $e->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 dark:bg-blue-950/40 text-[#1a68f0] dark:text-blue-400 border border-blue-200 dark:border-blue-900/60 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                <i class="fas fa-box-open text-xs"></i>
                                <span>{{ $e->products_count }} Produk</span>
                                <i class="fas fa-chevron-right text-xs text-blue-400"></i>
                            </button>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if(!$e->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-600 dark:text-zinc-400 border border-slate-200 dark:border-zinc-700">
                                    Nonaktif
                                </span>
                            @elseif($e->isOngoing())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/60">
                                    <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                    Sedang Berjalan
                                </span>
                            @elseif($e->isScheduled())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/60">
                                    <i class="fas fa-clock text-indigo-600 dark:text-indigo-400"></i>
                                    Terjadwal
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 border border-slate-200 dark:border-zinc-700">
                                    Selesai
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="openProductManager({{ $e->id }})" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition-colors" title="Kelola Produk">
                                    <i class="fas fa-plus-square text-sm"></i>
                                </button>
                                <button type="button" wire:click="editEvent({{ $e->id }})" class="p-2 text-slate-600 dark:text-zinc-400 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-lg transition-colors" title="Edit Event">
                                    <i class="fas fa-pencil-alt text-sm"></i>
                                </button>
                                <button type="button" wire:click="deleteEvent({{ $e->id }})" wire:confirm="Yakin ingin menghapus event ini?" class="p-2 text-red-600 dark:text-rose-400 hover:bg-red-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Hapus Event">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="py-16 text-center bg-white dark:bg-zinc-900 rounded-xl border border-dashed border-[#e2e8f0] dark:border-zinc-800 mt-6">
                                <div class="w-14 h-14 bg-[#f4f6f8] dark:bg-zinc-800 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-calendar text-[#a0aec0] dark:text-zinc-500 text-xl"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-[#212121] dark:text-zinc-200 mb-1">Belum ada event promo yang sesuai.</h3>
                                <p class="text-xs text-[#828282] dark:text-zinc-400 max-w-xs mx-auto mb-4">
                                    Event dan diskon akan muncul di sini setelah Anda membuat program promo.
                                </p>
                                <button 
                                    type="button"
                                    wire:click="openCreateModal"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors"
                                >
                                    <i class="fas fa-plus"></i>
                                    <span>Buat Event Baru</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah / Edit Event -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/75 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4 text-zinc-900 dark:text-zinc-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-3">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">
                        {{ $editingId ? 'Edit Event Promo' : 'Buat Event Promo Baru' }}
                    </h2>
                    <button wire:click="$set('showModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Nama Event</label>
                        <input type="text" wire:model="name" placeholder="Misal: Promo Flash Sale Gajian" class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]">
                        @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Tipe Diskon</label>
                            <select wire:model.live="discount_type" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]">
                                <option value="percentage">Persentase (%)</option>
                                <option value="fixed_nominal">Nominal Tetap (Rp)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                {{ $discount_type === 'percentage' ? 'Besaran Diskon (%)' : 'Potongan Nominal (Rp)' }}
                            </label>
                            <input type="number" wire:model="discount_value" min="1" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]">
                            @error('discount_value') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Teks Banner / Headline Promo</label>
                        <input type="text" wire:model="banner_text" placeholder="Misal: DISKON 20% SEMUA SPAREPART" class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Waktu Publikasi</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-zinc-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-zinc-800">
                                <input type="radio" wire:model.live="publish_mode" value="instant" class="text-[#1a68f0]">
                                <span class="text-xs font-medium">Mulai Sekarang</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-zinc-700 cursor-pointer hover:bg-slate-50 dark:hover:bg-zinc-800">
                                <input type="radio" wire:model.live="publish_mode" value="scheduled" class="text-[#1a68f0]">
                                <span class="text-xs font-medium">Jadwalkan</span>
                            </label>
                        </div>
                    </div>

                    @if($publish_mode === 'scheduled')
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Waktu Mulai</label>
                            <input type="datetime-local" wire:model="start_at" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]">
                            @error('start_at') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">Waktu Berakhir</label>
                        <input type="datetime-local" wire:model="end_at" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]">
                        @error('end_at') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-zinc-800">
                        <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-slate-200 dark:hover:bg-zinc-700">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold rounded-lg bg-[#1a68f0] hover:bg-blue-700 text-white shadow-sm">Simpan Event</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Kelola Produk Terpilih Event -->
    @if ($showProductModal && $managingEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/75 p-4 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-2xl w-full max-w-2xl p-6 shadow-2xl space-y-4 text-zinc-900 dark:text-zinc-100 max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Kelola Produk: {{ $managingEvent->name }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Pilih produk yang akan diikutsertakan ke dalam diskon event ini</p>
                    </div>
                    <button wire:click="$set('showProductModal', false)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="relative">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400 text-sm"></i>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchProduct" 
                        placeholder="Cari katalog produk sponsor..." 
                        class="w-full pl-10 pr-4 py-2 text-sm rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-[#1a68f0]"
                    >
                </div>

                <div class="overflow-y-auto flex-1 divide-y divide-slate-100 dark:divide-zinc-800 max-h-[400px]">
                    @forelse($this->availableProducts as $prod)
                        @php
                            $isSelected = in_array($prod->id, $selectedProductIds);
                        @endphp
                        <div class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50 dark:hover:bg-zinc-800/50 px-2 rounded-lg transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 flex items-center justify-center overflow-hidden shrink-0">
                                    @if($prod->image_url)
                                        <img src="{{ asset('storage/' . $prod->image_url) }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="fas fa-box text-slate-300 dark:text-zinc-600"></i>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-zinc-900 dark:text-white">{{ $prod->name }}</h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $prod->sponsor?->name ?? 'Sponsor' }} • Rp {{ number_format($prod->price, 0, ',', '.') }}</p>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                wire:click="toggleProductToEvent({{ $prod->id }})" 
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors {{ $isSelected ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-slate-200 dark:hover:bg-zinc-700' }}"
                            >
                                <i class="fas {{ $isSelected ? 'fa-check' : 'fa-plus' }} mr-1"></i>
                                {{ $isSelected ? 'Terpilih' : 'Tambahkan' }}
                            </button>
                        </div>
                    @empty
                        <div class="py-8 text-center text-zinc-400 text-xs">
                            Tidak ditemukan produk katalog.
                        </div>
                    @endforelse
                </div>

                <div class="flex justify-between items-center pt-3 border-t border-slate-100 dark:border-zinc-800">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($selectedProductIds) }} produk aktif dalam event</span>
                    <button wire:click="$set('showProductModal', false)" class="px-4 py-2 text-xs font-bold rounded-lg bg-[#1a68f0] text-white">Selesai</button>
                </div>
            </div>
        </div>
    @endif
</div>
