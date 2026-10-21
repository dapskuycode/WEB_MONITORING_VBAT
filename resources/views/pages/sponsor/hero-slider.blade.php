<x-layouts::admin 
    :title="__('Pengajuan Banner Hero Slide (16:9)')"
    pageIcon="fa-images"
    pageTitle="Pengajuan Banner Hero Slide (16:9)"
    pageDescription="Ajukan materi promosi banner utama di halaman beranda aplikasi."
>
    <x-slot:actions>
        <button 
            type="button"
            onclick="Livewire.dispatch('trigger-create-hero')" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
            <i class="fas fa-plus text-xs"></i>
            <span>Ajukan Hero Slide Baru</span>
        </button>
    </x-slot:actions>

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-hero-slider />
    </div>
</x-layouts::admin>
