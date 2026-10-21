<x-layouts::admin 
    :title="__('Pengajuan Pop Up Iklan (Startup Dialog)')"
    pageIcon="fa-bullhorn"
    pageTitle="Pengajuan Pop Up Iklan (Startup Dialog)"
    pageDescription="Ajukan banner pop-up modal interaktif saat user membuka aplikasi ponsel."
>
    <x-slot:actions>
        <button 
            type="button"
            onclick="Livewire.dispatch('trigger-create-popup')" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
            <i class="fas fa-plus text-xs"></i>
            <span>Ajukan Pop Up Baru</span>
        </button>
    </x-slot:actions>

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-popup-ad />
    </div>
</x-layouts::admin>
