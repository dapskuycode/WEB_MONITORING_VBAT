<x-layouts::admin
    :title="__('Manajemen Sponsor')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Manajemen Sponsor']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.sponsor-manager />
    </div>
</x-layouts::admin>