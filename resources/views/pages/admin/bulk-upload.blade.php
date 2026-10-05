<x-layouts::admin
    :title="__('Bulk Upload Kelas')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Bulk Upload Kelas']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.class-bulk-upload />
    </div>
</x-layouts::admin>