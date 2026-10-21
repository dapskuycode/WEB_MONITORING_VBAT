<x-layouts::admin 
    :title="__('Ajukan Hero Slide')"
    pageIcon="fa-images"
    pageTitle="Hero Slider (16:9)"
    pageDescription="Ajukan materi promosi banner utama di halaman beranda aplikasi."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-hero-slider />
    </div>
</x-layouts::admin>
