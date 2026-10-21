@props([
    'type' => 'success', // success | error | warning | info
    'message' => '',
])

@php
$colors = [
    'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-800',
    'error' => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-900/30 dark:text-rose-200 dark:border-rose-800',
    'warning' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-200 dark:border-amber-800',
    'info' => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800',
];
$icons = [
    'success' => 'circle-check',
    'error' => 'circle-xmark',
    'warning' => 'triangle-exclamation',
    'info' => 'circle-info',
];
@endphp

<div
    role="alert"
    {{ $attributes->merge(['class' => 'flex items-center gap-3 p-4 rounded-xl border text-sm font-medium ' . ($colors[$type] ?? $colors['info'])]) }}
    x-data="{ show: true }"
    x-show="show"
    x-transition
>
    <i class="fas fa-{{ $icons[$type] ?? $icons['info'] }} text-base shrink-0"></i>
    <span class="flex-1">{{ $message }}</span>
    <button type="button" @click="show = false" aria-label="Dismiss" class="hover:opacity-70 transition cursor-pointer shrink-0">
        <i class="fas fa-times text-sm"></i>
    </button>
</div>