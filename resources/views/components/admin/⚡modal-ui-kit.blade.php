<?php

use Livewire\Component;

new class extends Component
{
    // Interactive state for presentation board
    public $activeCustomSelect = false;
    public $selectedTier = 'gold';

    // Interactive Demo Modals triggers (can test full-screen modal behavior in real-time)
    public $openDemo1 = false;
    public $openDemo2 = false;
    public $openDemo3 = false;
    public $openDemo4 = false;

    // Form inputs state
    public $tierName = 'Gold Partner VIP';
    public $tierQuota = 1500;
    public $rejectionReason = 'Materi promosi banner belum menyertakan logo resmi beresolusi tinggi atau diskon belum sesuai ketentuan kemitraan.';

    public function selectTier($tier)
    {
        $this->selectedTier = $tier;
        $this->activeCustomSelect = false;
    }
};
?>

<div class="space-y-8 text-[#212121]">

    <!-- Presentation Header & Design System Overview Banner -->
    <div class="bg-white dark:bg-zinc-900 rounded-[16px] p-6 border border-slate-100 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.05)]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5 mb-1.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 text-[#1a68f0] border border-blue-100 dark:bg-blue-950/60 dark:text-blue-400 dark:border-blue-900/50 uppercase tracking-wide">
                        Enterprise Design System
                    </span>
                    <span class="text-xs text-[#828282]">• Shopee Enterprise Light Guidelines</span>
                </div>
                <h1 class="text-2xl font-bold text-[#212121] dark:text-white tracking-tight">
                    Pop-up Modals UI Kit — VBAT Ponsel
                </h1>
                <p class="text-xs sm:text-sm text-[#828282] dark:text-zinc-400 mt-1 max-w-3xl leading-relaxed">
                    Spesifikasi standar komponen pop-up modal tingkat enterprise dengan konsistensi padding 24px, sticky header & footer, border halus 1px, shadow elevasi natural, dan 100% bebas elemen browser native.
                </p>
            </div>

            <!-- Global Modal Anatomy Legend Pills -->
            <div class="flex flex-wrap items-center gap-2 bg-[#F4F6F8] dark:bg-zinc-800/60 p-2.5 rounded-xl border border-slate-200/70 dark:border-zinc-700/60 text-[11px] font-semibold text-slate-700 dark:text-zinc-300">
                <span class="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-zinc-800 rounded-lg shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-slate-900"></span> Backdrop 40% Blur
                </span>
                <span class="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-zinc-800 rounded-lg shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span> Radius 12/16px
                </span>
                <span class="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-zinc-800 rounded-lg shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 24px Content Padding
                </span>
                <span class="flex items-center gap-1.5 px-2 py-1 bg-white dark:bg-zinc-800 rounded-lg shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Sticky Top & Bottom
                </span>
            </div>
        </div>
    </div>

    <!-- Presentation Board: 4 Modal Variants Side-by-Side (Canvas View) -->
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#212121] dark:text-white">Presentation Board — 4 Modal Variants</h2>
                <p class="text-xs text-[#828282] dark:text-zinc-400">Tampilan side-by-side anatomi visual modal dalam kondisi aktif (Light Mode Default)</p>
            </div>
            <div class="text-xs font-semibold text-[#1a68f0] flex items-center gap-1.5">
                <i class="fas fa-layer-group"></i>
                <span>4 Tipe Modal Terstandarisasi</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            
            {{-- ========================================================== --}}
            {{-- 1. STANDARD FORM MODAL (e.g., "Edit Tier Sponsor") ~500px --}}
            {{-- ========================================================== --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-blue-100 text-[#1a68f0] font-bold text-xs flex items-center justify-center">1</span>
                        <h3 class="font-bold text-sm text-[#212121] dark:text-white">Standard Form Modal</h3>
                    </div>
                    <span class="text-[11px] font-mono text-slate-500 bg-slate-100 dark:bg-zinc-800 px-2 py-0.5 rounded">Width: ~500px</span>
                </div>

                <!-- Modal Window Container Simulation -->
                <div class="bg-white dark:bg-zinc-900 rounded-[16px] shadow-[0_20px_50px_rgba(0,0,0,0.12)] border border-slate-100 dark:border-zinc-800 flex flex-col overflow-hidden max-w-[500px] w-full mx-auto transition-all">
                    <!-- Sticky Header -->
                    <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4.5 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 shrink-0">
                        <div>
                            <h4 class="text-base font-bold text-[#212121] dark:text-white leading-snug">Edit Tier Sponsor</h4>
                            <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Konfigurasi batasan benefit dan visibilitas tier</p>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>

                    <!-- Body / Content (Strict 24px padding all around) -->
                    <div class="p-6 space-y-4.5 text-xs text-[#212121] dark:text-zinc-200">
                        <!-- Standard Text Input -->
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                                Nama Tier Sponsor <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   wire:model="tierName"
                                   placeholder="Contoh: Gold Partner VIP" 
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/20 focus:border-[#1a68f0] transition shadow-2xs font-normal">
                            <span class="text-[11px] text-[#828282] dark:text-zinc-400 mt-1 block">Nama label tier yang ditampilkan pada halaman marketplace & badge profil.</span>
                        </div>

                        <!-- Custom Dropdown (No native select) -->
                        <div class="relative" x-data="{ open: false }">
                            <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                                Kategori Prioritas Penayangan <span class="text-rose-500">*</span>
                            </label>
                            
                            <!-- Custom Select Trigger Button -->
                            <button type="button" 
                                    @click="open = !open" 
                                    @click.outside="open = false"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 flex items-center justify-between text-left transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/20 focus:border-[#1a68f0] shadow-2xs cursor-pointer">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $selectedTier === 'platinum' ? 'bg-indigo-600' : ($selectedTier === 'gold' ? 'bg-amber-500' : 'bg-slate-400') }}"></span>
                                    <span class="text-xs font-medium text-[#212121] dark:text-white truncate">
                                        {{ $selectedTier === 'platinum' ? 'Platinum Tier (Prioritas Utama)' : ($selectedTier === 'gold' ? 'Gold Tier (Prioritas Menengah)' : 'Silver Tier (Standar)') }}
                                    </span>
                                </div>
                                <i class="fas fa-chevron-down text-[11px] text-[#828282] transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            <!-- Custom Options Popover Menu -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute z-20 left-0 right-0 mt-1.5 bg-white dark:bg-zinc-800 rounded-xl border border-slate-100 dark:border-zinc-700 shadow-xl overflow-hidden py-1">
                                <button type="button" 
                                        wire:click="selectTier('platinum')" 
                                        @click="open = false"
                                        class="w-full px-3.5 py-2.5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-zinc-700/60 transition text-left cursor-pointer {{ $selectedTier === 'platinum' ? 'bg-blue-50/60 text-[#1a68f0]' : 'text-slate-800 dark:text-zinc-200' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                        <span class="text-xs font-semibold">Platinum Tier (Prioritas Utama)</span>
                                    </div>
                                    @if($selectedTier === 'platinum') <i class="fas fa-check text-xs text-[#1a68f0]"></i> @endif
                                </button>
                                <button type="button" 
                                        wire:click="selectTier('gold')" 
                                        @click="open = false"
                                        class="w-full px-3.5 py-2.5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-zinc-700/60 transition text-left cursor-pointer {{ $selectedTier === 'gold' ? 'bg-blue-50/60 text-[#1a68f0]' : 'text-slate-800 dark:text-zinc-200' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span class="text-xs font-semibold">Gold Tier (Prioritas Menengah)</span>
                                    </div>
                                    @if($selectedTier === 'gold') <i class="fas fa-check text-xs text-[#1a68f0]"></i> @endif
                                </button>
                                <button type="button" 
                                        wire:click="selectTier('silver')" 
                                        @click="open = false"
                                        class="w-full px-3.5 py-2.5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-zinc-700/60 transition text-left cursor-pointer {{ $selectedTier === 'silver' ? 'bg-blue-50/60 text-[#1a68f0]' : 'text-slate-800 dark:text-zinc-200' }}">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                        <span class="text-xs font-semibold">Silver Tier (Standar)</span>
                                    </div>
                                    @if($selectedTier === 'silver') <i class="fas fa-check text-xs text-[#1a68f0]"></i> @endif
                                </button>
                            </div>
                        </div>

                        <!-- Standard Numeric Input -->
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                                Kuota Tayangan Harian
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       wire:model="tierQuota"
                                       class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#1a68f0]/20 focus:border-[#1a68f0] transition shadow-2xs">
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs text-[#828282] font-medium">Tayang/Hari</span>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Footer (Secondary on left, Primary on right) -->
                    <div class="sticky bottom-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-zinc-800 shrink-0">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-50 dark:hover:bg-zinc-800 text-xs font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" class="px-5 py-2 rounded-xl bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-semibold shadow-xs transition cursor-pointer">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- 2. DETAIL / READ-ONLY MODAL (e.g., "Detail Kampanye") ~600px --}}
            {{-- ========================================================== --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 font-bold text-xs flex items-center justify-center">2</span>
                        <h3 class="font-bold text-sm text-[#212121] dark:text-white">Detail / Read-Only Modal</h3>
                    </div>
                    <span class="text-[11px] font-mono text-slate-500 bg-slate-100 dark:bg-zinc-800 px-2 py-0.5 rounded">Width: ~600px</span>
                </div>

                <!-- Modal Window Container Simulation -->
                <div class="bg-white dark:bg-zinc-900 rounded-[16px] shadow-[0_20px_50px_rgba(0,0,0,0.12)] border border-slate-100 dark:border-zinc-800 flex flex-col overflow-hidden max-w-[600px] w-full mx-auto transition-all">
                    <!-- Sticky Header -->
                    <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4.5 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 shrink-0">
                        <div>
                            <h4 class="text-base font-bold text-[#212121] dark:text-white leading-snug">Detail Kampanye Sponsor</h4>
                            <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Informasi ringkas verifikasi materi promosi</p>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>

                    <!-- Body / Content (Strict 24px padding with 2-Column Grid) -->
                    <div class="p-6 space-y-5 text-xs">
                        <!-- Preview Thumbnail Card -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-zinc-800/50 border border-slate-100 dark:border-zinc-700/60 flex items-center gap-4">
                            <div class="w-20 h-12 rounded-lg bg-slate-200 dark:bg-zinc-700 overflow-hidden shrink-0 border border-slate-300/50 relative shadow-2xs">
                                <img src="{{ asset('assets/images/sample-hero.png') }}" 
                                     alt="Preview Banner" 
                                     class="w-full h-full object-cover"
                                     onerror="this.src='https://placehold.co/320x180/1a68f0/ffffff?text=16:9+Banner'">
                            </div>
                            <div class="min-w-0">
                                <span class="font-bold text-sm text-[#212121] dark:text-white truncate block">Mega Promo Solder Digital T12</span>
                                <span class="text-xs text-[#828282] dark:text-zinc-400 block mt-0.5">Format: 16:9 Landscape Banner • Status: <strong class="text-emerald-600">Aktif Tayang</strong></span>
                            </div>
                        </div>

                        <!-- 2-Column Key-Value Pairs Grid -->
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4 pt-1">
                            <div>
                                <span class="text-[11px] font-medium text-[#828282] dark:text-zinc-400 uppercase tracking-wider block">Nama Sponsor</span>
                                <span class="text-xs sm:text-sm font-semibold text-[#212121] dark:text-white mt-0.5 block">TITAN Tools Official</span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-[#828282] dark:text-zinc-400 uppercase tracking-wider block">Tier Sponsor</span>
                                <span class="text-xs sm:text-sm font-semibold text-[#1a68f0] mt-0.5 flex items-center gap-1.5">
                                    <i class="fas fa-medal text-xs"></i> Gold Partner VIP
                                </span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-[#828282] dark:text-zinc-400 uppercase tracking-wider block">Periode Penayangan</span>
                                <span class="text-xs sm:text-sm font-semibold text-[#212121] dark:text-white mt-0.5 block">01 Nov 2026 - 15 Nov 2026</span>
                            </div>
                            <div>
                                <span class="text-[11px] font-medium text-[#828282] dark:text-zinc-400 uppercase tracking-wider block">Posisi Penempatan</span>
                                <span class="text-xs sm:text-sm font-semibold text-[#212121] dark:text-white mt-0.5 block">Hero Slide Beranda (16:9)</span>
                            </div>
                            <div class="col-span-2 pt-1 border-t border-slate-100 dark:border-zinc-800">
                                <span class="text-[11px] font-medium text-[#828282] dark:text-zinc-400 uppercase tracking-wider block">Tautan Arah (Target URL)</span>
                                <a href="#" class="text-xs text-[#1a68f0] font-medium hover:underline mt-0.5 flex items-center gap-1 truncate">
                                    <i class="fas fa-external-link-alt text-[10px]"></i>
                                    <span>https://tokopedia.com/titan-tools/flash-sale-solder-t12</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Footer (Single Button: Ghost gray or Outline Blue) -->
                    <div class="sticky bottom-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4 flex items-center justify-end border-t border-slate-100 dark:border-zinc-800 shrink-0">
                        <button type="button" class="px-5 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#1a68f0] text-slate-700 dark:text-zinc-300 hover:text-[#1a68f0] text-xs font-semibold transition cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- 3. ACTION / MODERATION MODAL (e.g., "Alasan Penolakan") ~500px --}}
            {{-- ========================================================== --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 font-bold text-xs flex items-center justify-center">3</span>
                        <h3 class="font-bold text-sm text-[#212121] dark:text-white">Action / Moderation Modal</h3>
                    </div>
                    <span class="text-[11px] font-mono text-slate-500 bg-slate-100 dark:bg-zinc-800 px-2 py-0.5 rounded">Width: ~500px</span>
                </div>

                <!-- Modal Window Container Simulation -->
                <div class="bg-white dark:bg-zinc-900 rounded-[16px] shadow-[0_20px_50px_rgba(0,0,0,0.12)] border border-slate-100 dark:border-zinc-800 flex flex-col overflow-hidden max-w-[500px] w-full mx-auto transition-all">
                    <!-- Sticky Header -->
                    <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4.5 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center text-xs">
                                <i class="fas fa-triangle-exclamation"></i>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-[#212121] dark:text-white leading-snug">Alasan Penolakan Kampanye</h4>
                                <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Berikan feedback spesifik kepada sponsor pengaju</p>
                            </div>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>

                    <!-- Body / Content (Warning text + large clean textarea) -->
                    <div class="p-6 space-y-4 text-xs">
                        <!-- Warning/Info Banner -->
                        <div class="p-3.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-900/40 text-amber-900 dark:text-amber-200 flex items-start gap-2.5 leading-relaxed">
                            <i class="fas fa-circle-info text-amber-600 dark:text-amber-400 mt-0.5 text-xs shrink-0"></i>
                            <span>Silakan berikan alasan mengapa kampanye ini ditolak. Pesan ini akan otomatis terkirim ke panel sponsor untuk diperbaiki.</span>
                        </div>

                        <!-- Large Clean Text Area -->
                        <div>
                            <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">
                                Catatan Alasan Penolakan <span class="text-rose-500">*</span>
                            </label>
                            <textarea wire:model="rejectionReason"
                                      rows="4" 
                                      placeholder="Jelaskan bagian visual, rasio gambar, atau link yang perlu disesuaikan..." 
                                      class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition shadow-2xs leading-relaxed"></textarea>
                            <span class="text-[11px] text-[#828282] dark:text-zinc-400 mt-1 block">Minimal 10 karakter agar sponsor memahami panduan perbaikan.</span>
                        </div>
                    </div>

                    <!-- Sticky Footer (Secondary Ghost gray + Destructive Clean Red Primary) -->
                    <div class="sticky bottom-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-zinc-800 shrink-0">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-50 dark:hover:bg-zinc-800 text-xs font-semibold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-semibold shadow-xs transition cursor-pointer flex items-center gap-1.5">
                            <i class="fas fa-paper-plane text-xs"></i>
                            <span>Kirim Penolakan</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- 4. MEDIA PREVIEW LIGHTBOX (Max-width ~900px, 16:9 Edge-to-Edge) --}}
            {{-- ========================================================== --}}
            <div class="space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 font-bold text-xs flex items-center justify-center">4</span>
                        <h3 class="font-bold text-sm text-[#212121] dark:text-white">Media Preview Lightbox</h3>
                    </div>
                    <span class="text-[11px] font-mono text-slate-500 bg-slate-100 dark:bg-zinc-800 px-2 py-0.5 rounded">Width: Max ~900px</span>
                </div>

                <!-- Modal Window Container Simulation -->
                <div class="bg-white dark:bg-zinc-900 rounded-[16px] shadow-[0_25px_60px_rgba(0,0,0,0.18)] border border-slate-100 dark:border-zinc-800 flex flex-col overflow-hidden max-w-[600px] w-full mx-auto transition-all">
                    <!-- Sticky Header -->
                    <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <i class="fas fa-image text-slate-400 text-sm"></i>
                            <h4 class="text-sm sm:text-base font-bold text-[#212121] dark:text-white truncate">
                                Preview Banner — Promo Akhir Tahun
                            </h4>
                        </div>
                        <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition cursor-pointer shrink-0">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>

                    <!-- Body / Content (Strictly NO PADDING, Edge-to-Edge 16:9 Aspect Ratio) -->
                    <div class="p-0 bg-slate-900 relative aspect-video flex items-center justify-center overflow-hidden group">
                        <!-- High quality 16:9 banner presentation graphic -->
                        <div class="w-full h-full bg-linear-to-tr from-slate-900 via-blue-900 to-indigo-950 flex flex-col items-center justify-center p-8 text-center text-white relative">
                            <!-- Background Grid Pattern Decor -->
                            <div class="absolute inset-0 opacity-15 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                            
                            <!-- Banner Graphic Content -->
                            <div class="relative z-10 space-y-2 max-w-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold tracking-widest uppercase bg-amber-400 text-slate-950 shadow-md">
                                    PROMO SPESIAL AKHIR TAHUN
                                </span>
                                <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white drop-shadow-md">
                                    DISCON HINGGA 70% SPAREPART
                                </h3>
                                <p class="text-xs text-slate-200">
                                    Peralatan servis profesional dan suku cadang original bergaransi teknisi.
                                </p>
                            </div>
                        </div>

                        <!-- Edge Badge Overlay -->
                        <div class="absolute bottom-3 right-3 px-2.5 py-1 rounded-md bg-black/60 backdrop-blur-xs text-[10px] font-mono text-white/90">
                            1920 × 1080 (16:9)
                        </div>
                    </div>

                    <!-- No Footer (As specified in requirement: Lightbox relies on header close 'X') -->
                </div>
            </div>

        </div>
    </div>

    <!-- Live Testing Launcher Bar (Opens Fullscreen Backdrop Modals) -->
    <div class="bg-white dark:bg-zinc-900 rounded-[16px] p-6 border border-slate-100 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.05)]">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-base text-[#212121] dark:text-white">Uji Interaktif Modal Layar Penuh</h3>
                <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5">Buka modal di atas backdrop blur penuh untuk menguji pengalaman pengguna nyata.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" 
                        wire:click="$set('openDemo1', true)"
                        class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#1a68f0] text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:text-[#1a68f0] transition cursor-pointer">
                    <i class="fas fa-edit mr-1 text-[11px]"></i> Demo Form Modal
                </button>
                <button type="button" 
                        wire:click="$set('openDemo2', true)"
                        class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-[#1a68f0] text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:text-[#1a68f0] transition cursor-pointer">
                    <i class="fas fa-file-lines mr-1 text-[11px]"></i> Demo Detail Modal
                </button>
                <button type="button" 
                        wire:click="$set('openDemo3', true)"
                        class="px-3.5 py-2 rounded-xl border border-slate-200 dark:border-zinc-700 hover:border-rose-500 text-xs font-semibold text-slate-700 dark:text-zinc-300 hover:text-rose-600 transition cursor-pointer">
                    <i class="fas fa-ban mr-1 text-[11px]"></i> Demo Action Modal
                </button>
                <button type="button" 
                        wire:click="$set('openDemo4', true)"
                        class="px-3.5 py-2 rounded-xl bg-[#1a68f0] hover:bg-blue-700 text-white text-xs font-semibold transition cursor-pointer shadow-xs">
                    <i class="fas fa-expand mr-1 text-[11px]"></i> Demo Lightbox (900px)
                </button>
            </div>
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- FULL SCREEN INTERACTIVE MODAL OVERLAYS (USING x-admin.modal) --}}
    {{-- ========================================================== --}}

    {{-- 1. Fullscreen Demo: Standard Form Modal --}}
    <x-admin.modal 
        name="demo1" 
        :open="$openDemo1" 
        maxWidth="md" 
        title="Edit Tier Sponsor" 
        subtitle="Konfigurasi batasan benefit dan visibilitas tier">
        <div class="space-y-4 text-xs">
            <div>
                <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">Nama Tier Sponsor</label>
                <input type="text" wire:model="tierName" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-[#212121] dark:text-zinc-200 mb-1.5">Kuota Tayangan Harian</label>
                <input type="number" wire:model="tierQuota" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-slate-900 dark:text-white">
            </div>
        </div>
        <x-slot:footer>
            <button type="button" wire:click="$set('openDemo1', false)" class="px-4 py-2 rounded-xl border border-slate-200 text-[#828282] hover:text-[#212121] text-xs font-semibold transition">Batal</button>
            <button type="button" wire:click="$set('openDemo1', false)" class="px-5 py-2 rounded-xl bg-[#1a68f0] text-white text-xs font-semibold transition">Simpan Perubahan</button>
        </x-slot:footer>
    </x-admin.modal>

    {{-- 2. Fullscreen Demo: Detail / Read-Only Modal --}}
    <x-admin.modal 
        name="demo2" 
        :open="$openDemo2" 
        maxWidth="lg" 
        title="Detail Kampanye Sponsor" 
        subtitle="Informasi ringkas verifikasi materi promosi">
        <div class="space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-[11px] text-[#828282] uppercase tracking-wider block">Nama Sponsor</span>
                    <span class="font-semibold text-sm text-[#212121] dark:text-white">TITAN Tools Official</span>
                </div>
                <div>
                    <span class="text-[11px] text-[#828282] uppercase tracking-wider block">Tier Sponsor</span>
                    <span class="font-semibold text-sm text-[#1a68f0]">Gold Partner VIP</span>
                </div>
                <div>
                    <span class="text-[11px] text-[#828282] uppercase tracking-wider block">Periode Penayangan</span>
                    <span class="font-semibold text-sm text-[#212121] dark:text-white">01 Nov - 15 Nov 2026</span>
                </div>
                <div>
                    <span class="text-[11px] text-[#828282] uppercase tracking-wider block">Penempatan</span>
                    <span class="font-semibold text-sm text-[#212121] dark:text-white">Hero Slide (16:9)</span>
                </div>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" wire:click="$set('openDemo2', false)" class="px-5 py-2 rounded-xl border border-slate-200 text-[#1a68f0] text-xs font-semibold transition">Tutup</button>
        </x-slot:footer>
    </x-admin.modal>

    {{-- 3. Fullscreen Demo: Action / Moderation Modal --}}
    <x-admin.modal 
        name="demo3" 
        :open="$openDemo3" 
        maxWidth="md" 
        title="Alasan Penolakan Kampanye" 
        subtitle="Berikan feedback spesifik kepada sponsor pengaju">
        <div class="space-y-4 text-xs">
            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 leading-relaxed">
                Silakan berikan alasan mengapa kampanye ini ditolak. Pesan ini akan otomatis terkirim ke panel sponsor.
            </div>
            <div>
                <label class="block text-xs font-semibold text-[#212121] mb-1.5">Catatan Alasan Penolakan</label>
                <textarea wire:model="rejectionReason" rows="3" class="w-full px-3.5 py-2.5 text-xs rounded-xl border border-slate-200"></textarea>
            </div>
        </div>
        <x-slot:footer>
            <button type="button" wire:click="$set('openDemo3', false)" class="px-4 py-2 rounded-xl border border-slate-200 text-[#828282] text-xs font-semibold transition">Batal</button>
            <button type="button" wire:click="$set('openDemo3', false)" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-semibold transition">Kirim Penolakan</button>
        </x-slot:footer>
    </x-admin.modal>

    {{-- 4. Fullscreen Demo: Media Preview Lightbox --}}
    <x-admin.modal 
        name="demo4" 
        :open="$openDemo4" 
        maxWidth="2xl" 
        title="Preview Banner — Promo Akhir Tahun" 
        :hasFooter="false"
        :noPadding="true">
        <div class="aspect-video bg-slate-900 flex flex-col items-center justify-center p-8 text-center text-white relative">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-400 text-slate-950 mb-3">
                PROMO SPESIAL AKHIR TAHUN
            </span>
            <h2 class="text-2xl font-black text-white">DISCON HINGGA 70% SPAREPART</h2>
            <p class="text-xs text-slate-300 mt-2 max-w-md">Format 16:9 Full Width Edge-to-Edge tanpa margin/padding internal sesuai panduan Shopee Enterprise.</p>
        </div>
    </x-admin.modal>

</div>
