@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
        <flux:icon :icon="$icon" variant="outline" class="w-8 h-8 text-zinc-400" />
    </div>
    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
    @isset($action)
    <div class="mt-4">{{ $action }}</div>
    @endisset
</div>