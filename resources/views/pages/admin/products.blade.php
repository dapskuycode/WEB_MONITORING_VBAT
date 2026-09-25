<x-layouts::admin
    :title="__('Manajemen Produk Part')"
    :pageTitle="__('Manajemen Produk Part Marketplace')"
    :pageDescription="__('Kelola katalog produk part yang ditampilkan di marketplace VBAT Ponsel.')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Produk Part']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.product-manager />
    </div>
</x-layouts::admin>