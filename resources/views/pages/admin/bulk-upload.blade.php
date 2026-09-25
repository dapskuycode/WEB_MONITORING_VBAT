<x-layouts::admin
    :title="__('Bulk Upload Kelas')"
    :pageTitle="__('Bulk Upload Kelas / Konten LMS')"
    :pageDescription="__('Upload batch konten kelas dalam format CSV/Excel untuk mempercepat onboarding materi pembelajaran.')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Bulk Upload Kelas']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.class-bulk-upload />
    </div>
</x-layouts::admin>