<x-layouts::admin 
    :title="__('Ajukan Pop Up Iklan')"
    pageIcon="fa-bullhorn"
    pageTitle="Pop Up Iklan"
    pageDescription="Ajukan banner pop-up modal interaktif saat user membuka aplikasi."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-popup-ad />
    </div>
</x-layouts::admin>
