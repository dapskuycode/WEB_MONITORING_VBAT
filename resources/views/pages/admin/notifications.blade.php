<x-layouts::admin
    :title="__('Push Notification')"
    :pageTitle="__('Push Notification Broadcast')"
    :pageDescription="__('Kirim push notification ke semua user atau segmen tertentu melalui aplikasi VBAT Ponsel.')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Push Notification']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.push-notification-manager />
    </div>
</x-layouts::admin>