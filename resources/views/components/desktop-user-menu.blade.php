<div class="relative w-full" x-data="{ open: false }">
    <button
        type="button"
        @click="open = !open"
        class="w-full flex items-center gap-2.5 px-2 py-2 rounded-xl text-start hover:bg-zinc-200/60 dark:hover:bg-zinc-800 transition-colors group cursor-pointer focus:outline-none"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <div class="w-8 h-8 rounded-lg bg-zinc-700 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-xs">
            {{ auth()->user()->initials() }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 truncate">
                {{ auth()->user()->name }}
            </p>
            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">
                {{ auth()->user()->email }}
            </p>
        </div>
        <flux:icon icon="chevrons-up-down" variant="micro" class="text-zinc-400 group-hover:text-zinc-600 dark:text-zinc-500 dark:group-hover:text-zinc-300 shrink-0" />
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
        class="absolute bottom-full left-0 mb-2 w-64 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700/80 shadow-2xl p-2 z-50 focus:outline-none"
    >
        <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
            <p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ auth()->user()->email }}</p>
            <span class="inline-block mt-1 text-[10px] font-semibold px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-500 border border-amber-500/20 uppercase tracking-wider">
                {{ str_replace('_', ' ', auth()->user()->role ?? 'Admin') }}
            </span>
        </div>

        <div class="py-1">
            <a
                href="{{ route('profile.edit') }}"
                wire:navigate
                class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-xl transition-colors"
            >
                <flux:icon icon="cog" variant="micro" class="text-zinc-400" />
                <span>Pengaturan Akun</span>
            </a>
        </div>

        <div class="pt-1 border-t border-zinc-100 dark:border-zinc-800">
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button
                    type="submit"
                    class="w-full flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-colors cursor-pointer text-start"
                >
                    <flux:icon icon="arrow-right-start-on-rectangle" variant="micro" class="text-red-500" />
                    <span>Keluar (Log out)</span>
                </button>
            </form>
        </div>
    </div>
</div>
