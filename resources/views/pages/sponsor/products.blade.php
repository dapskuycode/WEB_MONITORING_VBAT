<x-layouts::admin 
    :title="__('Katalog Produk Saya')"
    pageIcon="fa-cube"
    pageTitle="Katalog Produk Saya"
    pageDescription="Kelola katalog produk resmi dan suku cadang yang terdaftar untuk akun mitra."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-products />
    </div>
</x-layouts::admin>
