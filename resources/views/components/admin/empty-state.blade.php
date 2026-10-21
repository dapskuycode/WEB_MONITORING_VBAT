@props([
    'message' => 'Tidak ada data untuk ditampilkan.',
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-16 text-center']) }}>
    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-zinc-800 flex items-center justify-center mb-4 text-slate-400 dark:text-zinc-500 text-2xl">
        <i class="fas fa-inbox"></i>
    </div>
    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $message }}</p>
    @isset($action)
    <div class="mt-4">{{ $action }}</div>
    @endisset
</div>