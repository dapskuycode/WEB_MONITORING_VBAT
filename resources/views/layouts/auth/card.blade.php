<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-xl tracking-tight text-zinc-900 dark:text-white hover:opacity-90 transition-opacity" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 font-black text-lg shadow-sm">
                        V
                    </span>
                    <span>VBAT<span class="text-amber-600 dark:text-amber-400">Ponsel</span></span>
                </a>

                <div class="w-full">
                    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900/90 shadow-sm">
                        <div class="p-8 sm:p-10">{{ $slot }}</div>
                    </div>
                </div>
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
