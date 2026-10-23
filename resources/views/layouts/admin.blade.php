@props([
    'title' => null,
    'pageTitle' => null,
    'pageIcon' => null,
    'pageDescription' => null,
    'breadcrumb' => null,
    'actions' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F4F6F8] dark:bg-zinc-950 text-[#212121] dark:text-zinc-100 antialiased font-sans selection:bg-blue-600/15 selection:text-blue-600"
          x-data="{ 
              isMobile: window.innerWidth < 1024,
              sidebarOpen: window.innerWidth >= 1024 ? {{ auth()->user()?->isSponsor() ? 'false' : 'true' }} : true,
              sidebarPinned: window.innerWidth >= 1024 ? {{ auth()->user()?->isSponsor() ? 'false' : 'true' }} : true,
              mobileOpen: false,
              init() {
                  const check = () => {
                      this.isMobile = window.innerWidth < 1024;
                      if (!this.isMobile) {
                          this.mobileOpen = false;
                      } else {
                          this.sidebarOpen = true;
                      }
                  };
                  check();
                  window.addEventListener('resize', check);
                  document.addEventListener('livewire:navigated', () => {
                      check();
                      this.mobileOpen = false;
                  });
              },
              toggleSidebar() {
                  if (this.isMobile) {
                      this.mobileOpen = !this.mobileOpen;
                      if (this.mobileOpen) this.sidebarOpen = true;
                  } else {
                      this.sidebarOpen = !this.sidebarOpen;
                      this.sidebarPinned = this.sidebarOpen;
                  }
              },
              closeMobile() {
                  this.mobileOpen = false;
              }
          }"
    >
        @php
            $homeUrl = auth()->user()?->isSponsor() ? route('sponsor.dashboard') : route('dashboard');
        @endphp
        <div class="flex min-h-screen">
            <!-- Mobile Drawer Backdrop Overlay -->
            <div 
                x-show="mobileOpen" 
                x-cloak 
                @click="mobileOpen = false" 
                class="fixed inset-0 z-50 bg-black/60 lg:hidden"
                style="display: none;"
                aria-hidden="true"
            ></div>

            <!-- Mobile Drawer (Off-Canvas) -->
            <aside 
                x-show="mobileOpen" 
                x-cloak 
                class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] bg-white dark:bg-zinc-900 border-r border-slate-200/80 dark:border-zinc-800 flex flex-col justify-between shadow-2xl lg:hidden"
                style="display: none;"
            >
                <!-- Top Brand Header with explicit Close (✕) Button -->
                <div class="flex items-center justify-between p-4 border-b border-slate-100 dark:border-zinc-800 min-h-[73px]">
                    <a href="{{ $homeUrl }}" wire:navigate @click="mobileOpen = false" class="flex items-center overflow-hidden select-none">
                        <img src="{{ asset('assets/images/vbat_logo_shadow.png') }}" alt="VBAT Ponsel" class="h-8 w-auto object-contain shrink-0" />
                    </a>

                    <!-- Mobile Close Button (✕) -->
                    <button 
                        type="button" 
                        @click="mobileOpen = false" 
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-white bg-slate-100 dark:bg-zinc-800 hover:bg-slate-200 dark:hover:bg-zinc-700 transition cursor-pointer shrink-0"
                        title="Tutup Menu"
                        aria-label="Tutup Menu Navigasi"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Mobile Navigation List -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4" @click="if($event.target.closest('a')) mobileOpen = false">
                    @include('partials.sidebar-nav')
                </div>

                <!-- Bottom User Profile -->
                <div class="p-3 border-t border-slate-100 dark:border-zinc-800">
                    <x-desktop-user-menu />
                </div>
            </aside>

            <!-- Desktop Sidebar (Left, only rendered on lg screens and up) -->
            <aside 
                class="hidden lg:flex fixed inset-y-0 left-0 z-40 bg-white dark:bg-zinc-900 border-r border-slate-200/80 dark:border-zinc-800 transition-all duration-300 flex-col justify-between"
                :class="sidebarOpen ? 'w-64' : 'w-20'"
                @mouseenter="if(!sidebarPinned) sidebarOpen = true"
                @mouseleave="if(!sidebarPinned) sidebarOpen = false"
            >
                <!-- Subtle Collapse / Minimize Icon floating on sidebar border edge (Desktop only) -->
                <button 
                    type="button"
                    @click="toggleSidebar()"
                    class="flex items-center justify-center w-8 h-8 rounded-full bg-white dark:bg-zinc-800 border-2 border-slate-300 dark:border-zinc-700 text-slate-600 hover:text-[#1a73e8] dark:text-zinc-400 dark:hover:text-blue-400 transition-all cursor-pointer focus:outline-none shadow-md z-50 absolute -right-4 top-6"
                    @mouseenter="if(!sidebarPinned) sidebarOpen = true"
                    :title="sidebarOpen ? 'Perkecil Sidebar' : 'Perluas Sidebar'"
                    aria-label="Toggle collapse sidebar"
                >
                    <i class="fas fa-chevron-right w-5 h-5 transition-transform duration-300" :class="sidebarOpen ? 'rotate-180' : ''"></i>
                </button>

                <!-- Top Brand Header -->
                <div class="relative flex items-center justify-center p-4 border-b border-slate-100 dark:border-zinc-800/80 min-h-[73px] overflow-hidden">
                    <!-- Expanded Brand: vbat_logo_shadow.png -->
                    <a href="{{ $homeUrl }}" wire:navigate x-show="sidebarOpen" x-transition.opacity class="flex items-center overflow-hidden select-none focus:outline-none w-full">
                        <img src="{{ asset('assets/images/vbat_logo_shadow.png') }}" alt="VBAT Ponsel" class="h-9 w-auto object-contain shrink-0" />
                    </a>

                    <!-- Collapsed Brand: vbat_logo_simple.png (Desktop only when minimized) -->
                    <a href="{{ $homeUrl }}" wire:navigate x-show="!sidebarOpen" x-transition.opacity class="flex items-center justify-center w-full select-none focus:outline-none">
                        <img src="{{ asset('assets/images/vbat_logo_simple.png') }}" alt="VBAT" class="h-8 w-auto object-contain shrink-0" />
                    </a>
                </div>

                <!-- Navigation List -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4">
                    @include('partials.sidebar-nav')
                </div>

                <!-- Bottom User Profile (ChatGPT Style Button) -->
                <div class="p-3">
                    <x-desktop-user-menu />
                </div>
            </aside>

            <!-- Main Page Layout -->
            <div 
                class="flex-1 flex flex-col min-w-0 transition-all duration-300 w-full"
                :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'"
            >
                <!-- Top Bar -->
                <header class="sticky top-0 z-30 flex items-center justify-between min-h-20 py-3 sm:py-4 px-4 sm:px-6 md:px-8 bg-[#F4F6F8]/90 dark:bg-zinc-950/90 backdrop-blur-md border-b border-slate-200/50 dark:border-zinc-800/50">
                    <!-- Left: Hamburger Button (Mobile) + Page Title with Icon & Description -->
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <!-- Hamburger Menu Button for Mobile/Tablet -->
                        <button 
                            type="button" 
                            @click="mobileOpen = true" 
                            class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-200 hover:bg-slate-100 dark:hover:bg-zinc-700 transition cursor-pointer shrink-0 shadow-xs"
                            title="Buka Menu Navigasi"
                            aria-label="Toggle Sidebar Menu"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <div class="flex items-start gap-3 sm:gap-4 min-w-0">
                            @php
                                $currentPageIcon = $pageIcon ?? $attributes->get('pageIcon') ?? $attributes->get('page-icon');
                                $currentPageTitle = $pageTitle ?? $attributes->get('pageTitle') ?? $attributes->get('page-title') ?? $title ?? 'Dashboard';
                                $currentPageDescription = $pageDescription ?? $attributes->get('pageDescription') ?? $attributes->get('page-description') ?? '';
                                
                                // Route-specific fallbacks if not explicitly passed
                                if (!$currentPageIcon || !$currentPageDescription) {
                                    if (request()->routeIs('admin.analytics')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-chart-line';
                                        $currentPageTitle = $currentPageTitle ?? 'Analitik Aplikasi';
                                        $currentPageDescription = $currentPageDescription ?: 'Pantau kinerja aplikasi, engagement user, dan performa sponsor secara real‑time.';
                                    } elseif (request()->routeIs('dashboard')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-home';
                                        $currentPageTitle = $currentPageTitle ?? 'Dashboard Utama';
                                        $currentPageDescription = $currentPageDescription ?: 'Ringkasan performa aplikasi dan aktivitas terkini.';
                                    } elseif (request()->routeIs('admin.sponsors')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-handshake';
                                        $currentPageTitle = $currentPageTitle ?? 'Manajemen Sponsor';
                                        $currentPageDescription = $currentPageDescription ?: 'Kelola sponsor, verifikasi dokumen, dan kelola benefit tiap tier.';
                                    } elseif (request()->routeIs('admin.products')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-cube';
                                        $currentPageTitle = $currentPageTitle ?? 'Manajemen Katalog Produk';
                                        $currentPageDescription = $currentPageDescription ?: 'Kelola produk sponsor, harga, stok, kategori, dan ketersediaan.';
                                    } elseif (request()->routeIs('admin.campaigns')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-bullhorn';
                                        $currentPageTitle = $currentPageTitle ?? 'Moderasi Kampanye';
                                        $currentPageDescription = $currentPageDescription ?: 'Review dan kelola kampanye promosi dari sponsor sebelum dipublikasikan.';
                                    } elseif (request()->routeIs('admin.events')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-calendar-alt';
                                        $currentPageTitle = $currentPageTitle ?? 'Event & Diskon Shop';
                                        $currentPageDescription = $currentPageDescription ?: 'Kelola event dan program diskon untuk toko teknisi dan pengguna.';
                                    } elseif (request()->routeIs('admin.bulk-upload')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-cloud-upload-alt';
                                        $currentPageTitle = $currentPageTitle ?? 'Bulk Upload Materi Kelas Video';
                                        $currentPageDescription = $currentPageDescription ?: 'Unggah materi pembelajaran dalam jumlah banyak menggunakan template Excel atau CSV.';
                                    } elseif (request()->routeIs('admin.notifications')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-bell';
                                        $currentPageTitle = $currentPageTitle ?? 'Push Notification Manager (FCM)';
                                        $currentPageDescription = $currentPageDescription ?: 'Kelola permintaan notifikasi sponsor dan kirim broadcast ke pengguna.';
                                    } elseif (request()->routeIs('admin.users')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-users';
                                        $currentPageTitle = $currentPageTitle ?? 'Daftar Akun Pengguna Terdaftar';
                                        $currentPageDescription = $currentPageDescription ?: 'Kelola dan pantau seluruh akun pengguna yang terdaftar di ekosistem VBAT Ponsel (Siswa, Sponsor, Admin, dan Owner).';
                                    } elseif (request()->routeIs('sponsor.dashboard')) {
                                        $currentPageIcon = $currentPageIcon ?? 'fa-home';
                                        $currentPageTitle = $currentPageTitle ?? 'Dashboard Sponsor';
                                        $currentPageDescription = $currentPageDescription ?: 'Ringkasan pemakaian kuota promosi dan katalog produk resmi mitra.';
                                    }
                                }
                            @endphp
                            
                            @if($currentPageIcon)
                                <i class="fas {{ $currentPageIcon }} text-[#1a73e8] text-2xl sm:text-3xl md:text-4xl mt-1 shrink-0"></i>
                            @endif
                            <div class="min-w-0">
                                <h1 class="text-xl sm:text-2xl md:text-[26px] font-bold text-[#212121] dark:text-white tracking-tight leading-tight">{{ $currentPageTitle }}</h1>
                                @if($currentPageDescription)
                                    <p class="text-xs sm:text-sm text-[#828282] dark:text-zinc-400 mt-1 sm:mt-1.5 leading-relaxed">{{ $currentPageDescription }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Actions & Tools -->
                    <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                        @if(request()->routeIs('admin.analytics') || request()->routeIs('dashboard'))
                        <!-- Date Range Picker in Top Bar with Interactive Modal/Dropdown -->
                        <div 
                            x-data="{
                                openDateFilter: false,
                                start: '{{ request('start_date', '2026-10-01') }}',
                                end: '{{ request('end_date', '2026-10-31') }}',
                                get label() {
                                    return this.start + ' s/d ' + this.end;
                                },
                                setPreset(days) {
                                    const d = new Date();
                                    const endStr = d.toISOString().split('T')[0];
                                    d.setDate(d.getDate() - days);
                                    const startStr = d.toISOString().split('T')[0];
                                    this.start = startStr;
                                    this.end = endStr;
                                    this.applyFilter();
                                },
                                applyFilter() {
                                    const url = new URL(window.location.href);
                                    url.searchParams.set('start_date', this.start);
                                    url.searchParams.set('end_date', this.end);
                                    window.location.href = url.toString();
                                }
                            }" 
                            class="relative"
                        >
                            <button 
                                type="button" 
                                @click="openDateFilter = !openDateFilter"
                                class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-slate-50 dark:bg-zinc-800/80 border border-slate-200 dark:border-zinc-700 text-xs font-semibold text-[#212121] dark:text-zinc-200 shadow-xs cursor-pointer hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors"
                                title="Klik untuk mengubah rentang tanggal"
                            >
                                <svg class="w-3.5 h-3.5 text-[#1a68f0] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                <span x-text="label"></span>
                                <svg class="w-3 h-3 text-slate-400 ml-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <!-- Date Range Dropdown Panel -->
                            <div 
                                x-show="openDateFilter" 
                                x-cloak
                                @click.outside="openDateFilter = false"
                                x-transition
                                class="absolute right-0 mt-2 w-72 sm:w-80 p-4 rounded-2xl bg-white dark:bg-[#242424] border border-slate-200 dark:border-[#3F3F46] shadow-2xl z-50 text-xs"
                            >
                                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-[#3F3F46] mb-3">
                                    <span class="font-bold text-[#212121] dark:text-[#F3F4F6]">Filter Rentang Tanggal</span>
                                    <button type="button" @click="openDateFilter = false" class="text-slate-400 hover:text-slate-600">✕</button>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mb-1">Tanggal Mulai</label>
                                        <input type="date" x-model="start" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-500 dark:text-zinc-400 mb-1">Tanggal Selesai</label>
                                        <input type="date" x-model="end" class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-zinc-700 bg-white dark:bg-[#333338] text-[#212121] dark:text-[#F3F4F6] focus:outline-none focus:border-[#1a68f0]">
                                    </div>

                                    <div class="pt-1">
                                        <span class="block text-[10px] font-semibold text-slate-400 mb-1 uppercase">Pilihan Cepat:</span>
                                        <div class="flex flex-wrap gap-1.5">
                                            <button type="button" @click="setPreset(7)" class="px-2 py-1 rounded bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300 hover:bg-slate-200 text-[11px]">7 Hari</button>
                                            <button type="button" @click="setPreset(30)" class="px-2 py-1 rounded bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300 hover:bg-slate-200 text-[11px]">30 Hari</button>
                                            <button type="button" @click="setPreset(90)" class="px-2 py-1 rounded bg-slate-100 dark:bg-[#333338] text-slate-700 dark:text-zinc-300 hover:bg-slate-200 text-[11px]">3 Bulan</button>
                                            <button type="button" @click="start = '2026-10-01'; end = '2026-10-31'; applyFilter()" class="px-2 py-1 rounded bg-blue-50 text-[#1a68f0] dark:bg-blue-950/60 dark:text-blue-300 text-[11px] font-semibold">Bulan Ini</button>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-[#3F3F46]">
                                        <button type="button" @click="openDateFilter = false" class="px-3 py-1.5 rounded-lg text-slate-600 dark:text-zinc-400 font-semibold hover:bg-slate-100 dark:hover:bg-zinc-800">Batal</button>
                                        <button type="button" @click="applyFilter()" class="px-3.5 py-1.5 rounded-lg bg-[#1a68f0] hover:bg-blue-700 text-white font-bold shadow-xs">Terapkan Filter</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if(isset($actions))
                            <div class="flex items-center gap-1.5 sm:gap-2">
                                {{ $actions }}
                            </div>
                        @endif

                        @if(request()->routeIs('dashboard') || request()->routeIs('admin.analytics'))
                        <!-- Export to Excel Button (Functional Real Excel Downloader) -->
                        <a 
                            href="{{ route('admin.export.analytics', ['source' => request()->routeIs('dashboard') ? 'dashboard' : 'analytics', 'start_date' => request('start_date', '2026-10-01'), 'end_date' => request('end_date', '2026-10-31')]) }}" 
                            class="inline-flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3.5 py-1.5 sm:py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs sm:text-sm font-semibold transition-colors cursor-pointer shadow-xs select-none shrink-0"
                            title="Unduh Laporan Format Excel (.xlsx)"
                            download
                        >
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21.17 3.25q.33 0 .59.25.24.24.24.58v15.84q0 .34-.24.58-.26.25-.59.25H7.83q-.33 0-.58-.25-.25-.24-.25-.58V17H2.83q-.33 0-.58-.24-.25-.26-.25-.59V7.83q0-.33.25-.59.25-.24.58-.24h4.17V4.08q0-.34.25-.58.25-.25.58-.25zm-.75 1.58H8.58v2.34h11.84zm0 3.92H8.58v6.5h11.84zm0 8.08H8.58v2.34h11.84zm-14-6.5H3.58v3.34h2.84zm0-4.91H3.58v3.33h2.84zm-.09 6.27-1.12-2.14h-.05l-1.12 2.14H3.04l1.83-3.03-1.74-2.88h1.28l1.04 2.05h.05l1.04-2.05h1.28l-1.75 2.88 1.83 3.03z"/>
                            </svg>
                            <span class="hidden md:inline">Export to Excel</span>
                        </a>
                        @endif

                        <!-- Light/Dark Mode Switcher (Pill Toggle with Active Sun Icon) -->
                        <div 
                            x-data="{
                                isDark: document.documentElement.classList.contains('dark'),
                                init() {
                                    this.isDark = document.documentElement.classList.contains('dark');
                                    document.addEventListener('livewire:navigated', () => {
                                        this.isDark = document.documentElement.classList.contains('dark');
                                    });
                                },
                                toggle() {
                                    this.isDark = !this.isDark;
                                    if (this.isDark) {
                                        document.documentElement.classList.add('dark');
                                        try {
                                            localStorage.setItem('theme', 'dark');
                                            localStorage.setItem('flux.appearance', 'dark');
                                        } catch (e) {}
                                    } else {
                                        document.documentElement.classList.remove('dark');
                                        try {
                                            localStorage.setItem('theme', 'light');
                                            localStorage.setItem('flux.appearance', 'light');
                                        } catch (e) {}
                                    }
                                }
                            }"
                            @click="toggle()"
                            role="button"
                            tabindex="0"
                            title="Toggle Mode Terang / Gelap"
                            class="relative inline-flex items-center w-14 sm:w-16 h-7 sm:h-8 p-1 rounded-full bg-slate-200/90 dark:bg-zinc-800 border border-slate-300/80 dark:border-zinc-700 shadow-xs cursor-pointer select-none transition-all shrink-0"
                        >
                            <!-- Animated Active Knob -->
                            <span 
                                class="absolute top-1 bottom-1 w-5 h-5 sm:w-6 sm:h-6 rounded-full bg-white dark:bg-zinc-700 shadow-xs transition-transform duration-200 ease-out"
                                :class="isDark ? 'translate-x-[26px] sm:translate-x-[32px]' : 'translate-x-0'"
                            ></span>

                            <!-- Sun Icon (left) -->
                            <span 
                                class="relative z-10 flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 transition-colors duration-200"
                                :class="!isDark ? 'text-[#1a68f0]' : 'text-slate-400 dark:text-zinc-500'"
                            >
                                <i class="fas fa-sun text-[10px] sm:text-xs"></i>
                            </span>

                            <!-- Moon Icon (right) -->
                            <span 
                                class="relative z-10 flex items-center justify-center w-5 h-5 sm:w-6 sm:h-6 transition-colors duration-200 ml-auto"
                                :class="isDark ? 'text-blue-400' : 'text-slate-400 dark:text-zinc-500'"
                            >
                                <i class="fas fa-moon text-[10px] sm:text-xs"></i>
                            </span>
                        </div>

                        <!-- Notification Bell (Livewire Notification Center Component) -->
                        <livewire:admin.notification-center />
                    </div>
                </header>

                <!-- Page Content Area -->
                <main class="flex-1 px-3.5 sm:px-6 md:px-8 pb-10 pt-4 w-full max-w-full overflow-x-hidden">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
