<?php

use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $events = [];
    public $showModal = false;
    public $editingId = null;

    // Filters
    public $search = '';
    public $statusFilter = '';
    public $typeFilter = '';

    // Form fields
    public $name = '';
    public $description = '';
    public $banner_image = '';
    public $discount_type = 'percentage';
    public $discount_value = '';
    public $start_date = '';
    public $end_date = '';
    public $selected_products = [];
    public $is_active = true;

    public function mount()
    {
        $this->loadMockData();
    }

    public function loadMockData()
    {
        // Mock data untuk demo UI
        $this->events = [
            [
                'id' => 1,
                'name' => 'Flash Sale 10.10 Alat Servis',
                'banner' => '/images/mock/banner-flash-sale.jpg',
                'discount_type' => 'Diskon Persentase',
                'discount_value' => '25%',
                'start_date' => '10 Okt 2026',
                'end_date' => '15 Okt 2026',
                'product_count' => 24,
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Promo Sparepart HP Original',
                'banner' => '/images/mock/banner-sparepart.jpg',
                'discount_type' => 'Diskon Nominal',
                'discount_value' => 'Rp 50.000',
                'start_date' => '08 Okt 2026',
                'end_date' => '20 Okt 2026',
                'product_count' => 18,
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Bundling Teknisi Profesional',
                'banner' => '/images/mock/banner-bundling.jpg',
                'discount_type' => 'Diskon Persentase',
                'discount_value' => '15%',
                'start_date' => '05 Okt 2026',
                'end_date' => '12 Okt 2026',
                'product_count' => 8,
                'status' => 'active',
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Promo Akhir Tahun Aksesori',
                'banner' => '/images/mock/banner-aksesori.jpg',
                'discount_type' => 'Diskon Persentase',
                'discount_value' => '30%',
                'start_date' => '01 Sep 2026',
                'end_date' => '30 Sep 2026',
                'product_count' => 42,
                'status' => 'finished',
                'is_active' => false,
            ],
            [
                'id' => 5,
                'name' => 'Flash Deal Layar LCD Premium',
                'banner' => '/images/mock/banner-lcd.jpg',
                'discount_type' => 'Diskon Nominal',
                'discount_value' => 'Rp 100.000',
                'start_date' => '15 Agu 2026',
                'end_date' => '31 Agu 2026',
                'product_count' => 12,
                'status' => 'finished',
                'is_active' => false,
            ],
        ];
    }

    public function openCreateModal()
    {
        $this->reset(['editingId', 'name', 'description', 'banner_image', 'discount_type', 'discount_value', 'start_date', 'end_date', 'selected_products']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEditModal($eventId)
    {
        $event = collect($this->events)->firstWhere('id', $eventId);
        if ($event) {
            $this->editingId = $eventId;
            $this->name = $event['name'];
            $this->showModal = true;
        }
    }

    public function deleteEvent($eventId)
    {
        $this->events = collect($this->events)->reject(fn($e) => $e['id'] == $eventId)->values()->toArray();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['editingId', 'name', 'description', 'banner_image', 'discount_type', 'discount_value', 'start_date', 'end_date']);
    }
};

?>

<div class="space-y-6 text-[#212121]">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <h1 class="text-2xl font-bold text-[#212121]">Manajemen Event & Diskon Shop</h1>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-full border border-emerald-200">
                    <i class="fas fa-arrow-right"></i>
                    Bikin Event → Pilih Produk
                </span>
            </div>
            <p class="text-sm text-[#828282] max-w-2xl">
                Kelola promo, diskon, dan event khusus untuk produk di shop. Pilih produk sponsor yang masuk ke event diskon.
            </p>
        </div>

        <button 
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors shrink-0"
        >
            <i class="fas fa-plus"></i>
            <span>Buat Event Baru</span>
        </button>
    </div>

    <!-- Search & Filter Bar -->
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

        <!-- Filter Dropdowns on Right -->
        <div class="flex items-center gap-2.5 shrink-0">
            <select 
                wire:model.live="typeFilter" 
                class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer shadow-xs transition"
            >
                <option value="">Semua Tipe Diskon</option>
                <option value="percentage">Diskon Persentase</option>
                <option value="nominal">Diskon Nominal</option>
            </select>

            <select 
                wire:model.live="statusFilter" 
                class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-lg border border-slate-200 bg-white text-[#212121] focus:outline-none focus:border-[#1a68f0] focus:ring-2 focus:ring-[#1a68f0]/10 cursor-pointer shadow-xs transition"
            >
                <option value="">Semua Status</option>
                <option value="active">Aktif</option>
                <option value="finished">Selesai</option>
            </select>
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
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wide">Produk Terpilih</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold text-[#212121] uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold text-[#212121] uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($events as $event)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <!-- Column 1: NAMA EVENT & BANNER -->
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-32 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 overflow-hidden shrink-0">
                                        <i class="fas fa-image text-2xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-sm text-[#212121] truncate">{{ $event['name'] }}</div>
                                        <div class="text-xs text-[#828282] mt-0.5">ID: #EVT-{{ str_pad($event['id'], 3, '0', STR_PAD_LEFT) }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 2: TIPE & NILAI DISKON -->
                            <td class="px-4 py-4">
                                <div>
                                    <div class="text-xs text-[#828282] mb-1">{{ $event['discount_type'] }}</div>
                                    <span class="inline-flex items-center px-2.5 py-1 bg-orange-50 text-orange-700 text-sm font-bold rounded-md border border-orange-200">
                                        Potongan {{ $event['discount_value'] }}
                                    </span>
                                </div>
                            </td>

                            <!-- Column 3: PERIODE AKTIF -->
                            <td class="px-4 py-4">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-calendar-alt text-slate-400 text-sm mt-0.5"></i>
                                    <div class="text-sm">
                                        <div class="text-[#212121] font-medium">{{ $event['start_date'] }}</div>
                                        <div class="text-[#828282]">{{ $event['end_date'] }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Column 4: PRODUK TERPILIH -->
                            <td class="px-4 py-4">
                                <a href="#" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-[#1a68f0] text-sm font-semibold rounded-lg border border-blue-200 hover:bg-blue-100 transition-colors">
                                    <i class="fas fa-box-open text-xs"></i>
                                    <span>Lihat {{ $event['product_count'] }} Produk</span>
                                </a>
                            </td>

                            <!-- Column 5: STATUS -->
                            <td class="px-4 py-4">
                                @if ($event['status'] === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-full border border-emerald-200">
                                        <i class="fas fa-circle text-[6px]"></i>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full border border-slate-200">
                                        <i class="fas fa-circle text-[6px]"></i>
                                        Selesai
                                    </span>
                                @endif
                            </td>

                            <!-- Column 6: AKSI -->
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button 
                                        type="button"
                                        wire:click="openEditModal({{ $event['id'] }})"
                                        class="p-2 text-slate-500 hover:text-[#1a68f0] hover:bg-blue-50 rounded-lg transition-colors"
                                        title="Edit Event"
                                    >
                                        <i class="fas fa-pencil-alt text-sm"></i>
                                    </button>
                                    <button 
                                        type="button"
                                        wire:click="deleteEvent({{ $event['id'] }})"
                                        wire:confirm="Yakin hapus event ini?"
                                        class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Hapus Event"
                                    >
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-calendar-times text-slate-400 text-2xl"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-[#212121] mb-1">Belum Ada Event</div>
                                        <div class="text-sm text-[#828282]">Klik tombol "Buat Event Baru" untuk memulai</div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
