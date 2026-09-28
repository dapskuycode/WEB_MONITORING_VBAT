<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500/30 selection:text-white">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col items-center gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-xl tracking-tight text-white hover:opacity-90 transition-opacity" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-900 border border-zinc-700/80 text-white font-black text-lg shadow-sm">
                        V
                    </span>
                    <span>VBAT<span class="text-amber-500">Ponsel</span></span>
                </a>

                <div class="w-full">
                    <div class="rounded-2xl border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/40">
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
