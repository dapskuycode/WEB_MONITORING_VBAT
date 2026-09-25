<x-layouts::admin
    :title="__('Event & Diskon Shop')"
    :pageTitle="__('Event & Diskon Shop Management')"
    :pageDescription="__('Atur event spesial dan diskon yang muncul di toko VBAT Ponsel.')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Event & Diskon Shop']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.discount-event-manager />
    </div>
</x-layouts::admin>