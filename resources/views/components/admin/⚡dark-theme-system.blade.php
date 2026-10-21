<?php

use Livewire\Component;

new class extends Component
{
    // Interactive state for dark mode presentation board
    public $formInputText = 'Sparepart Original & Tools';
    public $searchQuery = 'Baterai Double Power';
    public $activeNav = 'campaigns'; // 'analytics', 'campaigns', 'products', 'notifications'
    public $selectedTab = 'components'; // 'components', 'palette', 'elevation'

    public function setActiveNav($item)
    {
        $this->activeNav = $item;
    }
};
?>

<div class="space-y-8">

    <!-- Top Design System Overview Banner (Dark Mode Specifications) -->
    <div class="rounded-[16px] p-6 bg-[#242424] border border-[#3F3F46] text-[#F3F4F6]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2.5">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#1E3A8A] text-[#60A5FA] border border-[#3B82F6]/30 uppercase tracking-wide">
                        Enterprise Dark Theme Spec
                    </span>
                    <span class="text-xs text-[#9CA3AF]">• WCAG 2.1 AA Compliant (Zero Eye Strain)</span>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#27272A] text-[#34D399] border border-[#3F3F46]">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#34D399]"></span> No Neon / No Pure Black
                    </span>
                </div>
                <h1 class="text-2xl font-bold text-[#F3F4F6] tracking-tight">
                    Dark Mode UI Theme System — VBAT Ponsel
                </h1>
                <p class="text-xs sm:text-sm text-[#9CA3AF] max-w-3xl leading-relaxed">
                    Sistem hierarki visual berbasis tingkat kecerahan (<strong class="text-[#F3F4F6]">Lightness Depth Elevation</strong>) menggantikan drop shadow. Menggunakan latar deep charcoal <code class="text-[#60A5FA] font-mono text-xs">#18181B</code>, kartu & sidebar <code class="text-[#60A5FA] font-mono text-xs">#242424</code>, input <code class="text-[#60A5FA] font-mono text-xs">#333338</code>, dan tipografi off-white <code class="text-[#60A5FA] font-mono text-xs">#F3F4F6</code>.
                </p>
            </div>

            <!-- Quick Elevation Tokens Legend -->
            <div class="grid grid-cols-3 gap-2 bg-[#18181B] p-2.5 rounded-xl border border-[#3F3F46] text-center">
                <div class="px-3 py-2 rounded-lg bg-[#18181B] border border-[#3F3F46]/60">
                    <span class="block text-[10px] text-[#9CA3AF] uppercase font-mono">Level 0</span>
                    <span class="block text-xs font-bold text-[#F3F4F6] mt-0.5 font-mono">#18181B</span>
                    <span class="text-[9px] text-[#9CA3AF]">App BG</span>
                </div>
                <div class="px-3 py-2 rounded-lg bg-[#242424] border border-[#3F3F46]">
                    <span class="block text-[10px] text-[#60A5FA] uppercase font-mono">Level 1</span>
                    <span class="block text-xs font-bold text-[#F3F4F6] mt-0.5 font-mono">#242424</span>
                    <span class="text-[9px] text-[#9CA3AF]">Cards & Nav</span>
                </div>
                <div class="px-3 py-2 rounded-lg bg-[#333338] border border-[#3F3F46]">
                    <span class="block text-[10px] text-[#34D399] uppercase font-mono">Level 2</span>
                    <span class="block text-xs font-bold text-[#F3F4F6] mt-0.5 font-mono">#333338</span>
                    <span class="text-[9px] text-[#9CA3AF]">Inputs & Hover</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN PRESENTATION BOARD (Canvas in Strict Dark Mode Elevation Level 0 #18181B) -->
    <div class="rounded-[20px] bg-[#18181B] border border-[#3F3F46] p-6 sm:p-8 space-y-8">
        
        <!-- Board Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-[#3F3F46] gap-3">
            <div>
                <span class="text-[11px] font-mono text-[#60A5FA] uppercase tracking-wider block mb-1">Canvas Stage • Level 0 Background (#18181B)</span>
                <h2 class="text-xl font-bold text-[#F3F4F6] tracking-tight">
                    Dark Mode Core Component Presentation Board
                </h2>
                <p class="text-xs text-[#9CA3AF] mt-0.5">
                    Demonstrasi 3 elemen utama sesuai spesifikasi: Data Card, Form Input (Level 2 + Focus State), dan Sidebar Navigation Snippet.
                </p>
            </div>
            
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#242424] border border-[#3F3F46] text-xs font-medium text-[#9CA3AF]">
                <i class="fas fa-eye text-[#60A5FA]"></i>
                <span>Strict Dark Theme View</span>
            </div>
        </div>

        <!-- 3 Components Showcase Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- ========================================================================= --}}
            {{-- ELEMENT 1: A DATA CARD (Level 1 #242424, Subtle Border, Title, Subtitle, Blue Button) --}}
            {{-- ========================================================================= --}}
            <div class="lg:col-span-5 space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-[#1E3A8A] text-[#60A5FA] font-bold text-[11px] flex items-center justify-center">1</span>
                        <h3 class="font-bold text-xs sm:text-sm text-[#F3F4F6]">A Data Card</h3>
                    </div>
                    <span class="text-[10px] font-mono text-[#9CA3AF] bg-[#242424] px-2 py-0.5 rounded border border-[#3F3F46]">
                        Level 1 Background (#242424)
                    </span>
                </div>

                <!-- Card Container: Strict Level 1 #242424, Border #3F3F46, No Shadows -->
                <div class="rounded-[16px] bg-[#242424] border border-[#3F3F46] p-6 flex flex-col justify-between space-y-5 transition-all">
                    
                    <!-- Card Top Meta & Status Badge -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-[#333338] border border-[#3F3F46] text-[#60A5FA] flex items-center justify-center text-sm">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div>
                                <span class="text-[10px] font-mono text-[#9CA3AF] uppercase">KAMPANYE HERO SLIDER</span>
                                <span class="text-xs font-semibold text-[#F3F4F6] block">Periode Q4 2026</span>
                            </div>
                        </div>

                        <!-- Semantic Warning Badge (Muted/Pastel Yellow #FBBF24) -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-[#FBBF24]/10 text-[#FBBF24] border border-[#FBBF24]/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#FBBF24]"></span>
                            <span>Menunggu Review</span>
                        </span>
                    </div>

                    <!-- Card Body: Primary Title (#F3F4F6) & Secondary Text (#9CA3AF) -->
                    <div class="space-y-2">
                        <h4 class="text-base sm:text-lg font-bold text-[#F3F4F6] leading-snug">
                            Flash Sale Promo Spesial Teknisi Ponsel
                        </h4>
                        <p class="text-xs text-[#9CA3AF] leading-relaxed">
                            Banner promosi alat servis digital presisi tinggi dengan potongan harga hingga 50% untuk seluruh teknisi terverifikasi di Jawa Timur & sekitarnya.
                        </p>
                    </div>

                    <!-- Key Metrics / Data Points Preview inside Card -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-[#18181B] border border-[#3F3F46]">
                        <div>
                            <span class="text-[10px] text-[#9CA3AF] uppercase block">Batas Tayang</span>
                            <span class="text-xs font-semibold text-[#F3F4F6]">1.500 Kuota / Hari</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-[#9CA3AF] uppercase block">Rasio Banner</span>
                            <span class="text-xs font-semibold text-[#60A5FA]">16:9 Landscape</span>
                        </div>
                    </div>

                    <!-- Card Footer: Secondary Action + Primary Brand Blue Button (#3B82F6 / #60A5FA) -->
                    <div class="flex items-center justify-between pt-4 border-t border-[#3F3F46]">
                        <span class="text-xs text-[#9CA3AF]">
                            Diperbarui 10 menit lalu
                        </span>
                        
                        <!-- Primary Blue Button: Adjusted for Dark Mode (Brighter accessible blue, no neon glow) -->
                        <button type="button" 
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#3B82F6] hover:bg-[#2563EB] active:bg-[#1D4ED8] text-[#F3F4F6] text-xs font-semibold transition-colors cursor-pointer">
                            <span>Kelola Kampanye</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- ELEMENT 2: A FORM INPUT (Level 2 #333338, Light Gray Text, Focused Primary Blue Border) --}}
            {{-- ========================================================================= --}}
            <div class="lg:col-span-4 space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-[#1E3A8A] text-[#60A5FA] font-bold text-[11px] flex items-center justify-center">2</span>
                        <h3 class="font-bold text-xs sm:text-sm text-[#F3F4F6]">A Form Input System</h3>
                    </div>
                    <span class="text-[10px] font-mono text-[#9CA3AF] bg-[#242424] px-2 py-0.5 rounded border border-[#3F3F46]">
                        Level 2 (#333338)
                    </span>
                </div>

                <!-- Input Showcase Box: Level 1 Container -->
                <div class="rounded-[16px] bg-[#242424] border border-[#3F3F46] p-6 space-y-5">
                    
                    <div>
                        <h4 class="text-xs font-bold text-[#F3F4F6] uppercase tracking-wider mb-0.5">Input States & Interaction</h4>
                        <p class="text-[11px] text-[#9CA3AF]">Latar input menggunakan Level 2 (#333338) dengan teks off-white (#F3F4F6).</p>
                    </div>

                    <!-- 1. Focused State Simulation (Primary Blue Border #3B82F6) -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-[#F3F4F6]">
                                Nama Produk / Suku Cadang <span class="text-[#F87171]">*</span>
                            </label>
                            <span class="text-[10px] font-mono text-[#60A5FA] bg-[#1E3A8A]/50 px-1.5 py-0.2 rounded border border-[#3B82F6]/40">
                                Focused State
                            </span>
                        </div>
                        
                        <!-- Input with Level 2 BG (#333338), Text #F3F4F6, Focused Border #3B82F6 -->
                        <div class="relative">
                            <input type="text" 
                                   wire:model="formInputText"
                                   class="w-full px-3.5 py-2.5 text-xs rounded-xl bg-[#333338] border-2 border-[#3B82F6] text-[#F3F4F6] placeholder-[#9CA3AF] focus:outline-none transition shadow-none font-medium">
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[#60A5FA] text-xs">
                                <i class="fas fa-pen-to-square"></i>
                            </div>
                        </div>
                        <span class="text-[11px] text-[#9CA3AF] block">
                            Border biru terang <code class="text-[#60A5FA] font-mono text-[10px]">#3B82F6</code> memperjelas fokus tanpa efek glow.
                        </span>
                    </div>

                    <!-- 2. Standard Idle / Default State -->
                    <div class="space-y-1.5 pt-2 border-t border-[#3F3F46]/60">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-semibold text-[#F3F4F6]">
                                Pencarian Cepat Katalog
                            </label>
                            <span class="text-[10px] font-mono text-[#9CA3AF]">Default / Idle State</span>
                        </div>
                        
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] text-xs">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   wire:model="searchQuery"
                                   placeholder="Ketik kata kunci..."
                                   class="w-full pl-9 pr-3.5 py-2.5 text-xs rounded-xl bg-[#333338] border border-[#3F3F46] text-[#F3F4F6] placeholder-[#9CA3AF] hover:border-[#9CA3AF]/60 focus:border-[#3B82F6] focus:outline-none transition">
                        </div>
                    </div>

                    <!-- 3. Semantic Validation State (Error Muted Red #F87171) -->
                    <div class="space-y-1.5 pt-2 border-t border-[#3F3F46]/60">
                        <label class="block text-xs font-semibold text-[#F3F4F6]">
                            Target Link URL
                        </label>
                        <input type="text" 
                               value="http://invalid-url" 
                               readonly
                               class="w-full px-3.5 py-2 text-xs rounded-xl bg-[#333338] border border-[#F87171] text-[#F3F4F6]">
                        <p class="text-[11px] text-[#F87171] flex items-center gap-1 font-medium mt-1">
                            <i class="fas fa-circle-exclamation text-[10px]"></i>
                            <span>URL tidak valid. Wajib diawali dengan https://</span>
                        </p>
                    </div>

                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- ELEMENT 3: SIDEBAR NAVIGATION SNIPPET (Active Dark Blue #1E3A8A + Bright Blue #60A5FA) --}}
            {{-- ========================================================================= --}}
            <div class="lg:col-span-3 space-y-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-[#1E3A8A] text-[#60A5FA] font-bold text-[11px] flex items-center justify-center">3</span>
                        <h3 class="font-bold text-xs sm:text-sm text-[#F3F4F6]">Sidebar Snippet</h3>
                    </div>
                    <span class="text-[10px] font-mono text-[#9CA3AF] bg-[#242424] px-2 py-0.5 rounded border border-[#3F3F46]">
                        Active: #1E3A8A
                    </span>
                </div>

                <!-- Sidebar Frame Container: Level 1 Background (#242424), Border #3F3F46 -->
                <div class="rounded-[16px] bg-[#242424] border border-[#3F3F46] p-4 space-y-4">
                    
                    <!-- Sidebar Section Label -->
                    <div class="px-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#9CA3AF]">
                            NAVIGASI SPONSOR
                        </span>
                    </div>

                    <!-- Menu Items List -->
                    <div class="space-y-1.5">
                        
                        <!-- 1. Active Item: Dark Blue BG (#1E3A8A), Bright Blue Text/Icon (#60A5FA) -->
                        <button type="button" 
                                wire:click="setActiveNav('campaigns')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition cursor-pointer text-left {{ $activeNav === 'campaigns' ? 'bg-[#1E3A8A] text-[#60A5FA] border border-[#3B82F6]/40' : 'text-[#9CA3AF] hover:text-[#F3F4F6] hover:bg-[#333338]' }}">
                            <div class="w-5 h-5 flex items-center justify-center shrink-0">
                                <i class="fas fa-bullhorn {{ $activeNav === 'campaigns' ? 'text-[#60A5FA]' : 'text-[#9CA3AF]' }}"></i>
                            </div>
                            <span class="truncate">Moderasi Kampanye</span>
                            @if($activeNav === 'campaigns')
                                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#60A5FA]"></span>
                            @endif
                        </button>

                        <!-- 2. Inactive Item 1 (Secondary Gray #9CA3AF) -->
                        <button type="button" 
                                wire:click="setActiveNav('analytics')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition cursor-pointer text-left {{ $activeNav === 'analytics' ? 'bg-[#1E3A8A] text-[#60A5FA] border border-[#3B82F6]/40' : 'text-[#9CA3AF] hover:text-[#F3F4F6] hover:bg-[#333338]' }}">
                            <div class="w-5 h-5 flex items-center justify-center shrink-0">
                                <i class="fas fa-chart-line {{ $activeNav === 'analytics' ? 'text-[#60A5FA]' : 'text-[#9CA3AF]' }}"></i>
                            </div>
                            <span class="truncate">Analitik Sponsor</span>
                        </button>

                        <!-- 3. Inactive Item 2 (Secondary Gray #9CA3AF) -->
                        <button type="button" 
                                wire:click="setActiveNav('products')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition cursor-pointer text-left {{ $activeNav === 'products' ? 'bg-[#1E3A8A] text-[#60A5FA] border border-[#3B82F6]/40' : 'text-[#9CA3AF] hover:text-[#F3F4F6] hover:bg-[#333338]' }}">
                            <div class="w-5 h-5 flex items-center justify-center shrink-0">
                                <i class="fas fa-cube {{ $activeNav === 'products' ? 'text-[#60A5FA]' : 'text-[#9CA3AF]' }}"></i>
                            </div>
                            <span class="truncate">Katalog Produk</span>
                        </button>

                        <!-- 4. Inactive Item 3 with Badge -->
                        <button type="button" 
                                wire:click="setActiveNav('notifications')"
                                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition cursor-pointer text-left {{ $activeNav === 'notifications' ? 'bg-[#1E3A8A] text-[#60A5FA] border border-[#3B82F6]/40' : 'text-[#9CA3AF] hover:text-[#F3F4F6] hover:bg-[#333338]' }}">
                            <div class="w-5 h-5 flex items-center justify-center shrink-0">
                                <i class="fas fa-bell {{ $activeNav === 'notifications' ? 'text-[#60A5FA]' : 'text-[#9CA3AF]' }}"></i>
                            </div>
                            <span class="truncate">Push Notifikasi</span>
                            <span class="ml-auto px-1.5 py-0.5 rounded text-[10px] font-mono bg-[#333338] text-[#F3F4F6]">3</span>
                        </button>

                    </div>

                    <!-- Mini User Snippet in Sidebar Bottom -->
                    <div class="pt-3 border-t border-[#3F3F46] flex items-center gap-2.5 px-1">
                        <div class="w-7 h-7 rounded-full bg-[#333338] border border-[#3F3F46] text-[#F3F4F6] flex items-center justify-center text-xs font-bold">
                            T
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-semibold text-[#F3F4F6] truncate block">TITAN Tools</span>
                            <span class="text-[10px] text-[#9CA3AF] block leading-tight">Gold Partner</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Semantic Palette Calibration Matrix (Brand, Success, Danger, Warning) -->
        <div class="pt-6 border-t border-[#3F3F46] space-y-4">
            <div>
                <h3 class="text-sm font-bold text-[#F3F4F6]">Adjusted Semantic Color Palette (Dark Mode Compliant)</h3>
                <p class="text-xs text-[#9CA3AF]">Dikalibrasi agar kontras tetap tajam tanpa getaran mata (no optical vibration).</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                
                <!-- Brand Primary Blue -->
                <div class="p-4 rounded-xl bg-[#242424] border border-[#3F3F46] space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#F3F4F6]">Primary Brand</span>
                        <span class="w-3.5 h-3.5 rounded-full bg-[#3B82F6]"></span>
                    </div>
                    <div class="h-8 rounded-lg bg-[#3B82F6] flex items-center px-3 text-[#F3F4F6] text-xs font-mono font-bold">
                        #3B82F6 / #60A5FA
                    </div>
                    <span class="text-[11px] text-[#9CA3AF] block leading-tight">
                        Lebih cerah dari solid blue light-mode agar pop jelas di atas background gelap.
                    </span>
                </div>

                <!-- Semantic Success Green -->
                <div class="p-4 rounded-xl bg-[#242424] border border-[#3F3F46] space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#F3F4F6]">Success</span>
                        <span class="w-3.5 h-3.5 rounded-full bg-[#34D399]"></span>
                    </div>
                    <div class="h-8 rounded-lg bg-[#34D399] flex items-center px-3 text-slate-950 text-xs font-mono font-bold">
                        #34D399 (Muted Green)
                    </div>
                    <span class="text-[11px] text-[#9CA3AF] block leading-tight">
                        Warna pastel emerald, tidak menusuk mata saat dipakai pada badge status disetujui.
                    </span>
                </div>

                <!-- Semantic Danger / Error Red -->
                <div class="p-4 rounded-xl bg-[#242424] border border-[#3F3F46] space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#F3F4F6]">Danger / Error</span>
                        <span class="w-3.5 h-3.5 rounded-full bg-[#F87171]"></span>
                    </div>
                    <div class="h-8 rounded-lg bg-[#F87171] flex items-center px-3 text-slate-950 text-xs font-mono font-bold">
                        #F87171 (Muted Red)
                    </div>
                    <span class="text-[11px] text-[#9CA3AF] block leading-tight">
                        Soft pastel red untuk tombol destructive atau pesan penolakan kampanye.
                    </span>
                </div>

                <!-- Semantic Warning Yellow -->
                <div class="p-4 rounded-xl bg-[#242424] border border-[#3F3F46] space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#F3F4F6]">Warning</span>
                        <span class="w-3.5 h-3.5 rounded-full bg-[#FBBF24]"></span>
                    </div>
                    <div class="h-8 rounded-lg bg-[#FBBF24] flex items-center px-3 text-slate-950 text-xs font-mono font-bold">
                        #FBBF24 (Muted Amber)
                    </div>
                    <span class="text-[11px] text-[#9CA3AF] block leading-tight">
                        Warna amber hangat untuk indikator pending approval tanpa efek neon.
                    </span>
                </div>

            </div>
        </div>

    </div>

</div>
