<x-layouts::admin
    :title="__('Event & Diskon Shop')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Event & Diskon Shop']]"
    pageIcon="fa-calendar-alt"
    pageTitle="Event & Diskon Shop"
    pageDescription="Kelola event dan program diskon untuk toko teknisi dan pengguna"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.discount-event-manager />
    </div>
</x-layouts::admin>