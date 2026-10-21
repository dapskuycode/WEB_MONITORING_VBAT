<x-layouts::admin 
    :title="__('Katalog Produk & Suku Cadang Saya')"
    pageIcon="fa-cube"
    pageTitle="Katalog Produk & Suku Cadang Saya"
    pageDescription="Kelola katalog produk resmi dan suku cadang yang terdaftar untuk akun mitra."
>
    <x-slot:actions>
        <button 
            type="button"
            onclick="Livewire.dispatch('trigger-create-product')" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
            <i class="fas fa-plus text-xs"></i>
            <span>Tambah Produk Baru</span>
        </button>
    </x-slot:actions>

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-products />
    </div>
</x-layouts::admin>


