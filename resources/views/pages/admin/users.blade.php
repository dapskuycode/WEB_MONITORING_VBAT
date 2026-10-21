<x-layouts::admin
    :title="__('Daftar Akun User')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Daftar Akun User']]"
    pageIcon="fa-users"
    pageTitle="Daftar Akun Pengguna Terdaftar"
    pageDescription="Kelola dan pantau seluruh akun pengguna yang terdaftar di ekosistem VBAT Ponsel (Siswa, Sponsor, Admin, dan Owner)."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.user-manager />
    </div>
</x-layouts::admin>
