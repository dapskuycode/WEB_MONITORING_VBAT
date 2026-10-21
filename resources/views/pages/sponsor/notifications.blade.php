<x-layouts::admin 
    :title="__('Push Notification Sponsor')"
    pageIcon="fa-bell"
    pageTitle="Push Notification"
    pageDescription="Ajukan siaran pesan notifikasi resmi kepada pengguna aplikasi."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-notifications />
    </div>
</x-layouts::admin>
