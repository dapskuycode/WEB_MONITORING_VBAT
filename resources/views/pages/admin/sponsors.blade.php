<x-layouts::admin
    :title="__('Manajemen Sponsor')"
    :pageTitle="__('Manajemen Akun Sponsor & Tiering')"
    :pageDescription="__('Kelola data mitra sponsor, terbitkan akun login portal sponsor, dan atur prioritas tier (Platinum, Gold, Silver).')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Manajemen Sponsor']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.sponsor-manager />
    </div>
</x-layouts::admin>