@props([
    'placeholder' => 'Cari...',
])

<form {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3 mb-4']) }} method="GET">
    <div class="relative flex-1 min-w-[200px] max-w-md">
        <flux:icon icon="magnifying-glass" variant="micro" class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" />
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="{{ $placeholder }}"
            class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 dark:text-white focus:outline-blue-500 focus:border-blue-500 transition"
        />
    </div>
    {{ $slot }}
    @if(request()->has('q') || request()->has('filter'))
    <a href="{{ url()->current() }}" class="text-xs text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
        {{ __('Reset') }}
    </a>
    @endif
</form>