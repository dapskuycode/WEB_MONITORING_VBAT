<div class="relative w-full pt-2 border-t border-slate-100 dark:border-zinc-800" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        class="w-full flex items-center rounded-xl text-start hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors group cursor-pointer focus:outline-none"
        :class="sidebarOpen ? 'justify-between p-2' : 'justify-center p-1.5'"
        aria-haspopup="true"
        :aria-expanded="open"
        :title="!sidebarOpen ? '{{ auth()->user()->name ?? 'User' }}' : ''"
    >
        <div class="flex items-center gap-3 min-w-0" :class="sidebarOpen ? '' : 'justify-center'">
            <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-zinc-700 border border-slate-200 dark:border-zinc-600 flex items-center justify-center text-xs font-bold text-slate-700 dark:text-zinc-200 shrink-0 shadow-xs">
                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
            </div>
            <div x-show="sidebarOpen" x-transition.opacity class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 dark:text-zinc-100 truncate leading-tight">
                    {{ auth()->user()->name ?? 'Admin' }}
                </p>
                <p class="text-xs text-slate-400 dark:text-zinc-400 truncate leading-tight mt-0.5">
                    {{ auth()->user()?->isSponsor() ? 'Mitra Sponsor' : 'Administrator' }}
                </p>
            </div>
        </div>
        <i x-show="sidebarOpen" class="fas fa-chevron-right text-xs text-slate-400 group-hover:text-slate-600 dark:text-zinc-500 dark:group-hover:text-zinc-300 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''"></i>
    </button>

    <!-- Dropdown Menu (Opens Upward Above Profile Button) -->
    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-2"
        class="absolute bottom-full left-0 mb-2 w-60 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/90 dark:border-zinc-700/80 shadow-xl p-1.5 z-50 focus:outline-none"
    >
        <div class="px-3 py-2 border-b border-slate-100 dark:border-zinc-800">
            <p class="text-xs font-semibold text-slate-800 dark:text-white truncate">{{ auth()->user()->name }}</p>
            <p class="text-[11px] text-slate-400 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</p>
        </div>

        <div class="py-1">
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 rounded-xl transition-colors"
            >
                <i class="fas fa-cog text-slate-400 w-4 h-4 flex items-center justify-center"></i>
                <span>Pengaturan Akun</span>
            </a>
        </div>

        <div class="pt-1 border-t border-slate-100 dark:border-zinc-800">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button
                    type="submit"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-colors cursor-pointer text-start"
                >
                    <i class="fas fa-sign-out-alt text-red-500 w-4 h-4 flex items-center justify-center"></i>
                    <span>Keluar (Log out)</span>
                </button>
            </form>
        </div>
    </div>
</div>
