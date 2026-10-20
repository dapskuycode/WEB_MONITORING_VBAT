<div class="relative w-full pt-2 border-t border-slate-100 dark:border-zinc-800" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        class="w-full flex items-center justify-between p-2 rounded-xl text-start hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors group cursor-pointer focus:outline-none"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <div class="flex items-center gap-3 min-w-0" :class="sidebarOpen ? '' : 'justify-center w-full'">
            <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-slate-700 dark:text-zinc-200 shrink-0">
                {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
            </div>
            <div x-show="sidebarOpen" x-transition.opacity class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 dark:text-zinc-100 truncate leading-tight">
                    {{ auth()->user()->name ?? 'Admin' }}
                </p>
                <p class="text-xs text-slate-400 dark:text-zinc-400 truncate leading-tight mt-0.5">
                    Administrator
                </p>
            </div>
        </div>
        <svg x-show="sidebarOpen" class="w-4 h-4 text-slate-400 group-hover:text-slate-600 dark:text-zinc-500 dark:group-hover:text-zinc-300 shrink-0 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
        </svg>
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
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
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
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    <span>Keluar (Log out)</span>
                </button>
            </form>
        </div>
    </div>
</div>
