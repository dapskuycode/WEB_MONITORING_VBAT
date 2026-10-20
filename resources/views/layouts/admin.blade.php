<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#F4F6F8] dark:bg-zinc-950 text-[#212121] dark:text-zinc-100 antialiased font-sans selection:bg-blue-600/15 selection:text-blue-600"
          x-data="{ 
              sidebarOpen: true,
              toggleSidebar() {
                  this.sidebarOpen = !this.sidebarOpen;
              }
          }"
    >
        <div class="flex min-h-screen">
            <!-- Sidebar (Left) -->
            <aside 
                class="fixed inset-y-0 left-0 z-40 bg-white dark:bg-zinc-900 border-r border-slate-200/80 dark:border-zinc-800 transition-all duration-300 flex flex-col justify-between"
                :class="sidebarOpen ? 'w-64' : 'w-20'"
            >
                <!-- Subtle Collapse / Minimize Icon floating on sidebar border edge -->
                <button 
                    type="button"
                    @click="toggleSidebar()"
                    class="hidden lg:flex items-center justify-center w-6 h-6 rounded-full bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-all cursor-pointer focus:outline-none shadow-md z-50 absolute -right-3 top-6"
                    :title="sidebarOpen ? 'Perkecil Sidebar' : 'Perluas Sidebar'"
                    aria-label="Toggle collapse sidebar"
                >
                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>

                <!-- Top Brand Header -->
                <div class="relative flex items-center justify-center p-4 border-b border-slate-100 dark:border-zinc-800/80 min-h-[73px] overflow-hidden">
                    <!-- Expanded Brand: vbat_logo_shadow.png -->
                    <a href="{{ route('dashboard') }}" x-show="sidebarOpen" x-transition.opacity class="flex items-center overflow-hidden select-none focus:outline-none w-full">
                        <img src="{{ asset('assets/images/vbat_logo_shadow.png') }}" alt="VBAT Ponsel" class="h-9 w-auto object-contain shrink-0" />
                    </a>

                    <!-- Collapsed Brand: vbat_logo_simple.png (centered when minimized) -->
                    <a href="{{ route('dashboard') }}" x-show="!sidebarOpen" x-transition.opacity class="flex items-center justify-center w-full select-none focus:outline-none">
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
                class="flex-1 flex flex-col min-w-0 transition-all duration-300"
                :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'"
            >
                <!-- Top Bar -->
                <header class="sticky top-0 z-30 flex items-center justify-between h-20 px-6 sm:px-8 bg-transparent">
                    <!-- Left: Page Title -->
                    <div>
                        <h1 class="text-2xl sm:text-[26px] font-bold text-[#212121] dark:text-white tracking-tight">
                            @if(request()->routeIs('admin.analytics'))
                                Analitik Aplikasi - Overview
                            @elseif(request()->routeIs('admin.sponsors'))
                                Manajemen Sponsor
                            @elseif(request()->routeIs('admin.products'))
                                Manajemen Produk Part
                            @else
                                {{ $title ?? $pageTitle ?? 'Analitik Sponsor' }}
                            @endif
                        </h1>
                    </div>

                    <!-- Right: Actions & Tools -->
                    <div class="flex items-center gap-3.5">
                        @if(request()->routeIs('admin.analytics'))
                        <!-- Date Range Picker in Top Bar -->
                        <div class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-50 dark:bg-zinc-800/80 border border-slate-200 dark:border-zinc-700 text-xs font-semibold text-[#212121] dark:text-zinc-200 shadow-xs cursor-pointer hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors">
                            <svg class="w-3.5 h-3.5 text-[#828282] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                            </svg>
                            <span>01 Oct 2026 - 31 Oct 2026</span>
                            <svg class="w-3 h-3 text-slate-400 ml-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                        @endif

                        @if(request()->routeIs('dashboard') || request()->routeIs('admin.analytics'))
                        <!-- Export to Excel Button (Consistent Green Style) -->
                        <button 
                            type="button" 
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800 text-xs sm:text-sm font-semibold transition-colors cursor-pointer shadow-xs"
                        >
                            <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21.17 3.25q.33 0 .59.25.24.24.24.58v15.84q0 .34-.24.58-.26.25-.59.25H7.83q-.33 0-.58-.25-.25-.24-.25-.58V17H2.83q-.33 0-.58-.24-.25-.26-.25-.59V7.83q0-.33.25-.59.25-.24.58-.24h4.17V4.08q0-.34.25-.58.25-.25.58-.25zm-.75 1.58H8.58v2.34h11.84zm0 3.92H8.58v6.5h11.84zm0 8.08H8.58v2.34h11.84zm-14-6.5H3.58v3.34h2.84zm0-4.91H3.58v3.33h2.84zm-.09 6.27-1.12-2.14h-.05l-1.12 2.14H3.04l1.83-3.03-1.74-2.88h1.28l1.04 2.05h.05l1.04-2.05h1.28l-1.75 2.88 1.83 3.03z"/>
                            </svg>
                            <span>Export to Excel</span>
                        </button>
                        @endif

                        <!-- Light/Dark Mode Switcher (Pill Toggle with Active Sun Icon) -->
                        <div 
                            x-data="{
                                isDark: document.documentElement.classList.contains('dark'),
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
                            class="relative inline-flex items-center w-16 h-8 p-1 rounded-full bg-slate-200/90 dark:bg-zinc-800 border border-slate-300/80 dark:border-zinc-700 shadow-xs cursor-pointer select-none transition-all"
                        >
                            <!-- Animated Active Knob -->
                            <span 
                                class="absolute top-1 bottom-1 w-6 h-6 rounded-full bg-white dark:bg-zinc-700 shadow-xs transition-transform duration-200 ease-out"
                                :class="isDark ? 'translate-x-[32px]' : 'translate-x-0'"
                            ></span>

                            <!-- Sun Icon (left) -->
                            <span 
                                class="relative z-10 flex items-center justify-center w-6 h-6 transition-colors duration-200"
                                :class="!isDark ? 'text-[#1a68f0]' : 'text-slate-400 dark:text-zinc-500'"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="4"></circle>
                                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                                </svg>
                            </span>

                            <!-- Moon Icon (right) -->
                            <span 
                                class="relative z-10 flex items-center justify-center w-6 h-6 transition-colors duration-200 ml-auto"
                                :class="isDark ? 'text-blue-400' : 'text-slate-400 dark:text-zinc-500'"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
                                </svg>
                            </span>
                        </div>

                        <!-- Notification Bell with Red Dot -->
                        <div class="relative">
                            <button 
                                type="button" 
                                class="w-9 h-9 rounded-full bg-white dark:bg-zinc-900 border border-slate-200/80 dark:border-zinc-800 flex items-center justify-center text-slate-500 hover:text-slate-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors shadow-xs cursor-pointer focus:outline-none"
                                aria-label="Notifikasi"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                            </button>
                            <!-- Notification indicator -->
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-zinc-900"></span>
                        </div>
                    </div>
                </header>

                <!-- Page Content Area -->
                <main class="flex-1 px-6 sm:px-8 pb-10">
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
