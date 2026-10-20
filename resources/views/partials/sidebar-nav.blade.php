<div class="flex flex-col gap-6 py-2">
    {{-- Group Dashboards --}}
    <div>
        <p x-show="sidebarOpen" x-transition.opacity class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500 mb-2 truncate">
            DASHBOARDS
        </p>
        <div class="flex flex-col gap-1">
            <a 
                href="{{ route('admin.analytics') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.analytics') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Analitik Aplikasi' : ''"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.analytics') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-400 dark:text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Analitik Aplikasi</span>
            </a>

            <a 
                href="{{ route('dashboard') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Analitik Sponsor' : ''"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('dashboard') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-400 dark:text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Analitik Sponsor</span>
            </a>
        </div>
    </div>

    {{-- Group Manajemen --}}
    <div>
        <p x-show="sidebarOpen" x-transition.opacity class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-zinc-500 mb-2 truncate">
            MANAJEMEN
        </p>
        <div class="flex flex-col gap-1">
            <a 
                href="{{ route('admin.sponsors') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.sponsors') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Manajemen Sponsor' : ''"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.sponsors') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-400 dark:text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Manajemen Sponsor</span>
            </a>

            <a 
                href="{{ route('admin.products') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.products') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Katalog Produk' : ''"
            >
                <svg class="w-5 h-5 shrink-0 {{ request()->routeIs('admin.products') ? 'text-[#1a68f0] dark:text-blue-400' : 'text-slate-400 dark:text-zinc-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Katalog Produk
            </a>

            <a 
                href="{{ route('admin.campaigns') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.campaigns') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Moderasi Kampanye' : ''"
            >
                <svg class="w-5 h-5 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Moderasi Kampanye</span>
            </a>

            <a 
                href="{{ route('admin.events') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.events') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Event & Diskon' : ''"
            >
                <svg class="w-5 h-5 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Event & Diskon</span>
            </a>

            <a 
                href="{{ route('admin.bulk-upload') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.bulk-upload') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Bulk Upload Kelas' : ''"
            >
                <svg class="w-5 h-5 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Bulk Upload Kelas</span>
            </a>

            <a 
                href="{{ route('admin.notifications') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.notifications') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Push Notification' : ''"
            >
                <svg class="w-5 h-5 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Push Notification</span>
            </a>

            <a 
                href="{{ route('admin.users') }}" 
                wire:navigate 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors {{ request()->routeIs('admin.users') ? 'bg-[#EBF3FE] text-[#1a68f0] dark:bg-blue-950/50 dark:text-blue-400' : 'text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800' }}"
                :class="sidebarOpen ? '' : 'justify-center px-0'"
                :title="!sidebarOpen ? 'Akun User' : ''"
            >
                <svg class="w-5 h-5 shrink-0 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span x-show="sidebarOpen" x-transition.opacity class="truncate">Akun User</span>
            </a>
        </div>
    </div>
</div>
