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

<div class="space-y-6 text-[#212121]">
    <!-- Search & Filter Bar with Action Button -->
    <div class="p-3.5 sm:p-4 bg-white rounded-[12px] border border-slate-200/80 shadow-[0_2px_12px_-3px_rgba(0,0,0,0.04)] flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fas fa-search text-sm"></i>
            </div>
            <input 
                type="text" 
                wire:model.live.debounce.250ms="search" 
                placeholder="Cari nama event..." 
                class="w-full pl-10 pr-4 py-2 text-sm text-[#212121] placeholder-slate-400 bg-slate-50/70 rounded-lg border border-slate-200 focus:outline-none focus:bg-white focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 transition-all"
            >
        </div>

        <!-- Filter Dropdowns & Action Button on Right -->
        <div class="flex items-center gap-2.5 shrink-0">
            <select 
                wire:model.live="typeFilter" 
                class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer shadow-xs transition"
            >
                <option value="">Semua Tipe Diskon</option>
                <option value="percentage">Persentase (%)</option>
                <option value="fixed_nominal">Nominal (Rp)</option>
            </select>

            <select 
                wire:model.live="statusFilter" 
                class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer shadow-xs transition"
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
    <div class="bg-white rounded-[12px] border border-slate-200/80 shadow-[0_2px_12px_-3px_rgba(0,0,0,0.04)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-200">
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wide">Nama Event & Banner</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wide">Tipe & Nilai Diskon</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wide">Periode Aktif</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-[#212121] uppercase tracking-wide">Produk Terpilih</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-[#212121] uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3.5 text-right text-xs font-bold text-[#212121] uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($events as $e)
                    @php
                        $now = now();
                        $isActiveTime = $e->start_at <= $now && $e->end_at >= $now;
                        $isUpcoming = $e->start_at > $now;
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-4">
                            <div class="font-semibold text-sm text-[#212121]">{{ $e->name }}</div>
                            <div class="text-xs text-[#828282] mt-0.5 line-clamp-1">\"{{ $e->banner_text }}\"</div>
                        </td>
                        <td class="px-4 py-4">
                            @if($e->discount_type === 'percentage')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                    Diskon {{ round($e->discount_value) }}%
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                    Potongan Rp {{ number_format($e->discount_value, 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-calendar-alt text-slate-400 text-sm mt-0.5"></i>
                                <div class="text-sm">
                                    <div class="text-[#212121] font-medium">{{ $e->start_at->format('d M Y H:i') }}</div>
                                    <div class="text-[#828282]">{{ $e->end_at->format('d M Y H:i') }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <button type="button" wire:click="openProductManager({{ $e->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-[#1a68f0] border border-blue-200 hover:bg-blue-100 transition-colors">
                                <i class="fas fa-box-open text-xs"></i>
                                <span>{{ $e->products_count }} Produk</span>
                                <i class="fas fa-chevron-right text-xs text-blue-400"></i>
                            </button>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if(!$e->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                    Nonaktif
                                </span>
                            @elseif($e->isOngoing())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fas fa-circle text-[6px] animate-pulse"></i>
                                    Sedang Berjalan
                                </span>
                            @elseif($e->isScheduled())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <i class="fas fa-clock text-indigo-600"></i>
                                    Terjadwal
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                    Selesai
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="openProductManager({{ $e->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Kelola Produk">
                                    <i class="fas fa-plus-square text-sm"></i>
                                </button>
                                <button type="button" wire:click="editEvent({{ $e->id }})" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors" title="Edit Event">
                                    <i class="fas fa-pencil-alt text-sm"></i>
                                </button>
                                <button type="button" wire:click="deleteEvent({{ $e->id }})" wire:confirm="Yakin ingin menghapus event ini?" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Event">
                                    <i class="fas fa-trash-alt text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <div class="py-16 text-center bg-white rounded-xl border border-dashed border-[#e2e8f0] mt-6">
                                <div class="w-14 h-14 bg-[#f4f6f8] rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-box text-[#a0aec0] text-xl"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-[#212121] mb-1">Belum ada kampanye dengan filter yang dipilih.</h3>
                                <p class="text-xs text-[#828282] max-w-xs mx-auto mb-4">
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
</div>
