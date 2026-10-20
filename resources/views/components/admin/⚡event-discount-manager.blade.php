<?php

use Livewire\Component;

new class extends Component
{
    public $search = '';
    public $typeFilter = '';
    public $statusFilter = '';

    public function openCreateModal()
    {
        // To be implemented
    }

    public function openEditModal($eventId)
    {
        // To be implemented
    }

    public function deleteEvent($eventId)
    {
        // To be implemented
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
                Atur program event diskon khusus. Hanya produk yang Anda pilih yang akan mendapatkan harga diskon dan tampil di section promo event.
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
                <tbody>
                    <!-- Data rows will be populated here -->
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div class="px-4 py-12 text-center border-t border-slate-200">
            <div class="flex flex-col items-center gap-3">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-calendar-times text-slate-400 text-2xl"></i>
                </div>
                <div>
                    <div class="text-sm font-semibold text-[#212121] mb-1">Belum ada program Event Diskon</div>
                    <div class="text-sm text-[#828282]">Klik tombol "Buat Event Baru" di atas untuk membuat event pertama Anda</div>
                </div>
            </div>
        </div>
    </div>
</div>
