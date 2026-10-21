<x-layouts::admin 
    :title="__('Pengajuan Card Slider Beranda (4:5 / 1:1)')"
    pageIcon="fa-th-large"
    pageTitle="Pengajuan Card Slider Beranda (4:5 / 1:1)"
    pageDescription="Tampilkan kartu promosi penawaran khusus dan suku cadang dalam susunan grid mosaic beranda."
>
    <x-slot:actions>
        <button 
            type="button"
            onclick="Livewire.dispatch('trigger-create-card')" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
            <i class="fas fa-plus text-xs"></i>
            <span>Ajukan Card Slider Baru</span>
        </button>
    </x-slot:actions>

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-card-slider />
    </div>
</x-layouts::admin>
