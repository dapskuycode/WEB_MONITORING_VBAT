<x-layouts::admin
    :title="__('Bulk Upload Kelas')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Bulk Upload Kelas']]"
    pageIcon="fa-cloud-upload-alt"
    pageTitle="Bulk Upload Materi Kelas Video"
    pageDescription="Unggah materi pembelajaran dalam jumlah banyak menggunakan template Excel atau CSV"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.class-bulk-upload />
    </div>
</x-layouts::admin>