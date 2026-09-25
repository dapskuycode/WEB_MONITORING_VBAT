<x-layouts::admin
    :title="__('Program Best Deal')"
    :pageTitle="__('Program Best Deal Management')"
    :pageDescription="__('Kelola program best deal dan penawaran spesial untuk user VBAT Ponsel.')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Program Best Deal']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.best-deal-manager />
    </div>
</x-layouts::admin>