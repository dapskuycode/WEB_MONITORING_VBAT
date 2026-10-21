<x-layouts::admin
    :title="__('Manajemen Sponsor')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Manajemen Sponsor']]"
    pageIcon="fa-handshake"
    pageTitle="Manajemen Sponsor"
    pageDescription="Kelola akun sponsor, tier keanggotaan, benefit, dan profil perusahaan"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.sponsor-manager />
    </div>
</x-layouts::admin>