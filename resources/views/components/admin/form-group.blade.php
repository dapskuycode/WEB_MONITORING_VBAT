@props([
    'label' => '',
    'name' => '',
    'required' => false,
    'help' => null,
    'error' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if($label)
    <label for="{{ $name }}" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300">
        {{ $label }}
        @if($required)<span class="text-rose-500">*</span>@endif
    </label>
    @endif

    {{ $slot }}

    @if($help && !$error)
    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $help }}</p>
    @endif

    @if($error)
    <p class="text-[11px] text-rose-600 dark:text-rose-400 flex items-center gap-1">
        <flux:icon icon="exclamation-circle" variant="micro" />
        {{ $error }}
    </p>
    @endif
</div>