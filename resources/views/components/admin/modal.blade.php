@props([
    'name' => 'modal',
    'title' => '',
    'subtitle' => null,
    'maxWidth' => 'md', // sm (~400px), md (~500px), lg (~600px), xl (~750px), 2xl (~900px), full
    'open' => false,
    'hasFooter' => true,
    'noPadding' => false,
])

@php
$widthClasses = [
    'sm' => 'max-w-[420px]',
    'md' => 'max-w-[500px]',
    'lg' => 'max-w-[600px]',
    'xl' => 'max-w-[750px]',
    '2xl' => 'max-w-[900px]',
    'full' => 'max-w-5xl',
][$maxWidth] ?? 'max-w-[500px]';
@endphp

@if($open)
<div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs transition-opacity animate-fade-in"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-{{ $name }}-title"
    x-data="{ open: true }"
    x-show="open"
    @keydown.escape.window="$dispatch('close-{{ $name }}')"
>
    <div 
        class="bg-white dark:bg-zinc-900 rounded-[16px] w-full {{ $widthClasses }} shadow-[0_20px_50px_rgba(0,0,0,0.15)] border border-slate-100 dark:border-zinc-800 flex flex-col max-h-[90vh] overflow-hidden transition-all transform animate-in fade-in zoom-in-95 duration-150"
        @click.outside="$dispatch('close-{{ $name }}')"
    >
        {{-- Sticky Header --}}
        <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4.5 flex items-center justify-between border-b border-slate-100 dark:border-zinc-800 shrink-0">
            <div class="min-w-0 pr-4">
                <h2 id="modal-{{ $name }}-title" class="text-base font-bold text-[#212121] dark:text-white leading-snug truncate">
                    {{ $title }}
                </h2>
                @if($subtitle)
                    <p class="text-xs text-[#828282] dark:text-zinc-400 mt-0.5 truncate">{{ $subtitle }}</p>
                @endif
            </div>
            <button 
                type="button" 
                wire:click="$dispatch('close-{{ $name }}')" 
                @click="$dispatch('close-{{ $name }}')"
                aria-label="Tutup Modal" 
                class="w-8 h-8 rounded-lg flex items-center justify-center text-[#828282] hover:text-[#212121] dark:hover:text-white hover:bg-slate-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer shrink-0"
            >
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- Body / Content (Strict 24px padding all around unless noPadding is true) --}}
        <div class="flex-1 overflow-y-auto {{ $noPadding ? 'p-0' : 'p-6' }} text-slate-800 dark:text-zinc-200">
            {{ $slot }}
        </div>

        {{-- Sticky Footer (Subtle 1px gray top border, Buttons right-aligned) --}}
        @if(isset($footer) && $hasFooter)
            <div class="sticky bottom-0 z-10 bg-white dark:bg-zinc-900 px-6 py-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-zinc-800 shrink-0">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
@endif