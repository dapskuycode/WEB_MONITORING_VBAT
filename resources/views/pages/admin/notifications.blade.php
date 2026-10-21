<x-layouts::admin
    :title="__('Push Notification')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Push Notification']]"
    pageIcon="fa-bell"
    pageTitle="Push Notification Manager (FCM)"
    pageDescription="Kelola permintaan notifikasi sponsor dan kirim broadcast ke pengguna"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.push-notification-manager />
    </div>
</x-layouts::admin>