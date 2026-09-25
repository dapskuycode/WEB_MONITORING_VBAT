@props([
    'name' => 'modal',
    'title' => '',
    'open' => false,
])

@if($open)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-{{ $name }}-title"
    x-data="{ open: true }"
    x-show="open"
    x-transition.opacity
>
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
            <h2 id="modal-{{ $name }}-title" class="text-lg font-bold text-zinc-900 dark:text-white">
                {{ $title }}
            </h2>
            <button type="button" wire:click="$dispatch('close-{{ $name }}')" aria-label="Close" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                <flux:icon icon="x-mark" variant="micro" />
            </button>
        </div>
        <div>
            {{ $slot }}
        </div>
    </div>
</div>
@endif