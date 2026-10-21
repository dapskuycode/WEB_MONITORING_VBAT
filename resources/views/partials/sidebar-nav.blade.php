<div class="flex flex-col gap-6 py-2">
    @if(auth()->user()->isSponsor())
        {{-- SPONSOR MENU --}}
        <div>
            <p x-show="sidebarOpen" x-transition.opacity class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-500 mb-2 truncate">
                SPONSOR
            </p>
            <div class="flex flex-col gap-1">
                <a 
                    href="{{ route('sponsor.dashboard') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('sponsor.dashboard') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Dashboard Sponsor' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-home text-base {{ request()->routeIs('sponsor.dashboard') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Dashboard Sponsor</span>
                </a>

                <a 
                    href="{{ route('sponsor.products') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('sponsor.products') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Katalog Produk Saya' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-cube text-base {{ request()->routeIs('sponsor.products') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Katalog Produk Saya</span>
                </a>
            </div>
        </div>

        {{-- KAMPANYE --}}
        <div>
            <p x-show="sidebarOpen" x-transition.opacity class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-zinc-400 mb-2 truncate">
                KAMPANYE PROMOSI
            </p>
            <div x-show="!sidebarOpen" class="w-8 h-px bg-slate-200 dark:bg-zinc-700 mx-auto my-2"></div>
            <div class="flex flex-col gap-1">
                <a 
                    href="{{ route('sponsor.campaigns.hero') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('sponsor.campaigns.hero') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Hero Slider' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-images text-base {{ request()->routeIs('sponsor.campaigns.hero') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Hero Slider</span>
                </a>

                <a 
                    href="{{ route('sponsor.campaigns.horizontal') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('sponsor.campaigns.horizontal') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Horizontal Slider' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-sliders-h text-base {{ request()->routeIs('sponsor.campaigns.horizontal') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Horizontal Slider</span>
                </a>

                <a 
                    href="{{ route('sponsor.campaigns.card') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('sponsor.campaigns.card') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Card Slider' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-th-large text-base {{ request()->routeIs('sponsor.campaigns.card') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Card Slider</span>
                </a>

                <a 
                    href="{{ route('sponsor.campaigns.popup') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('sponsor.campaigns.popup') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Pop Up Iklan' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-bullhorn text-base {{ request()->routeIs('sponsor.campaigns.popup') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Pop Up Iklan</span>
                </a>

                <a 
                    href="{{ route('sponsor.notifications') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('sponsor.notifications') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Notifikasi' : ''"
                >
                    <div class="w-6 h-6 flex items-center justify-center shrink-0">
                        <i class="fas fa-bell text-base {{ request()->routeIs('sponsor.notifications') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    </div>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Notifikasi</span>
                </a>
            </div>
        </div>
    @else
        {{-- ADMIN MENU --}}
        <div>
            <p x-show="sidebarOpen" x-transition.opacity class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-500 mb-2 truncate">
                DASHBOARDS
            </p>
            <div class="flex flex-col gap-1">
                <a 
                    href="{{ route('admin.analytics') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.analytics') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Analitik Aplikasi' : ''"
                >
                    <i class="fas fa-chart-line w-5 h-5 shrink-0 {{ request()->routeIs('admin.analytics') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Analitik Aplikasi</span>
                </a>

                <a 
                    href="{{ route('dashboard') }}" 
                    wire:navigate 
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                    :class="sidebarOpen ? '' : 'justify-center px-0'"
                    :title="!sidebarOpen ? 'Dashboard Utama' : ''"
                >
                    <i class="fas fa-home w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                    <span x-show="sidebarOpen" x-transition.opacity class="truncate">Dashboard Utama</span>
                </a>
            </div>
        </div>
    @endif

    @if(!auth()->user()->isSponsor())
        {{-- Group Manajemen --}}
    <div>
        <p x-show="sidebarOpen" x-transition.opacity class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-zinc-400 mb-2 truncate">
            MANAJEMEN
        </p>
        <div class="flex flex-col gap-1">
            <a 
                href="{{ route('admin.sponsors') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.sponsors') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Manajemen Sponsor' : ''"
            >
                <i class="fas fa-handshake w-5 h-5 shrink-0 {{ request()->routeIs('admin.sponsors') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Manajemen Sponsor</span>
            </a>

            <a 
                href="{{ route('admin.products') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.products') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Katalog Produk' : ''"
            >
                <i class="fas fa-cube w-5 h-5 shrink-0 {{ request()->routeIs('admin.products') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Katalog Produk
            </a>

            <a 
                href="{{ route('admin.campaigns') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.campaigns') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Moderasi Kampanye' : ''"
            >
                <i class="fas fa-bullhorn w-5 h-5 shrink-0 {{ request()->routeIs('admin.campaigns') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Moderasi Kampanye</span>
            </a>

            <a 
                href="{{ route('admin.events') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.events') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Event & Diskon Shop' : ''"
            >
                <i class="fas fa-calendar-alt w-5 h-5 shrink-0 {{ request()->routeIs('admin.events') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Event & Diskon Shop</span>
            </a>

            <a 
                href="{{ route('admin.bulk-upload') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.bulk-upload') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Bulk Upload Materi Kelas' : ''"
            >
                <i class="fas fa-cloud-upload-alt w-5 h-5 shrink-0 {{ request()->routeIs('admin.bulk-upload') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Bulk Upload Materi Kelas</span>
            </a>

            <a 
                href="{{ route('admin.notifications') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.notifications') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Push Notification' : ''"
            >
                <i class="fas fa-bell w-5 h-5 shrink-0 {{ request()->routeIs('admin.notifications') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Push Notification</span>
            </a>

            <a 
                href="{{ route('admin.users') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.users') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-800 dark:text-zinc-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Akun User' : ''"
            >
                <i class="fas fa-users w-5 h-5 shrink-0 {{ request()->routeIs('admin.users') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-500 dark:text-zinc-400' }}"></i>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Akun User</span>
            </a>
        </div>
    </div>
    @endif
</div>
