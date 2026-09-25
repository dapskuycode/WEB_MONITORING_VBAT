@props([
    'message' => 'Terjadi kesalahan saat memuat data.',
    'retry' => null, // wire:click action string
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }} role="alert">
    <div class="w-16 h-16 rounded-full bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center mb-4">
        <flux:icon icon="exclamation-triangle" variant="outline" class="w-8 h-8 text-rose-600" />
    </div>
    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-1">{{ __('Gagal memuat data') }}</p>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4 max-w-sm">{{ $message }}</p>
    @if($retry)
    <button type="button" wire:click="{{ $retry }}" class="px-4 py-2 text-xs font-semibold rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 transition">
        {{ __('Coba lagi') }}
    </button>
    @endif
    {{ $slot }}
</div>