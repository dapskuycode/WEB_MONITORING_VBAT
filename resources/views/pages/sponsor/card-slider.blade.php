<x-layouts::admin 
    :title="__('Ajukan Card Slider')"
    pageIcon="fa-th-large"
    pageTitle="Card Slider"
    pageDescription="Tampilkan kartu promosi penawaran khusus dan suku cadang sponsor."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-card-slider />
    </div>
</x-layouts::admin>
