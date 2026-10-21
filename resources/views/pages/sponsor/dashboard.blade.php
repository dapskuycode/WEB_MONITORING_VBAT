<x-layouts::admin 
    :title="__('Dashboard Sponsor')"
    pageIcon="fa-home"
    pageTitle="Dashboard Sponsor"
    pageDescription="Ringkasan pemakaian kuota promosi dan katalog produk resmi mitra."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-dashboard />
    </div>
</x-layouts::admin>
