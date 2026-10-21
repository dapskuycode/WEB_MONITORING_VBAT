@props([
    'accept' => '.jpg,.jpeg,.png,.webp,.pdf,.xlsx,.csv',
    'maxSize' => '10MB',
    'wireModel' => 'file',
])

<label class="block">
    <div class="flex flex-col items-center justify-center w-full px-6 py-8 border-2 border-dashed border-slate-200 dark:border-zinc-700 rounded-2xl cursor-pointer hover:border-[#1a68f0] dark:hover:border-blue-500 bg-slate-50/60 dark:bg-zinc-800/30 transition group">
        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-[#1a68f0] dark:text-blue-400 flex items-center justify-center text-xl mb-3 group-hover:scale-105 transition-transform">
            <i class="fas fa-cloud-arrow-up"></i>
        </div>
        <p class="text-sm font-semibold text-slate-800 dark:text-zinc-200">
            {{ __('Klik atau drop file di sini') }}
        </p>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
            {{ __('Maks: :size • Format: :accept', ['size' => $maxSize, 'accept' => $accept]) }}
        </p>
        <input
            type="file"
            wire:model="{{ $wireModel }}"
            accept="{{ $accept }}"
            class="hidden"
            data-test="file-upload-input"
        />
    </div>
    <div wire:loading wire:target="{{ $wireModel }}" class="text-xs text-blue-600 mt-2 flex items-center gap-1">
        <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ __('Mengunggah...') }}
    </div>
</label>