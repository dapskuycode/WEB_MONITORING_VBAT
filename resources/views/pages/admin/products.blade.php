<x-layouts::admin
    :title="__('Manajemen Katalog Produk')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Katalog Produk']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.product-manager />
    </div>
</x-layouts::admin>