<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased selection:bg-amber-500/30 selection:text-white flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md mx-auto" style="max-width: 440px; width: 100%; margin: 0 auto;">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 shadow-2xl shadow-black/80 p-8 sm:p-9">
                {{ $slot }}
            </div>
            
            <p class="mt-6 text-center text-xs text-zinc-600">
                &copy; {{ date('Y') }} VBAT Ponsel &bull; Hak cipta dilindungi.
            </p>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
