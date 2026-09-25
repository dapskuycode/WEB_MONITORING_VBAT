<x-layouts::admin
    :title="__('Dashboard Analitik')"
    :pageTitle="__('Super Dashboard — Analitik & Metrik')"
    :pageDescription="__('Pantau performa platform VBAT: pengguna aktif, kampanye, produk, dan metrik kunci lainnya.')"
    :breadcrumb="[['label' => 'Dashboard']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.analytics-dashboard />
    </div>
</x-layouts::admin>