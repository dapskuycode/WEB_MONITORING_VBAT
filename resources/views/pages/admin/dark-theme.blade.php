<x-layouts::admin
    :title="__('Dark Mode UI Theme System')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Design System'], ['label' => 'Dark Mode Theme']]"
    pageIcon="fa-moon"
    pageTitle="Dark Mode UI Theme System"
    pageDescription="Spesifikasi tema Dark Mode enterprise beraksesibilitas tinggi dengan sistem elevasi kedalaman berbasis lightness, palet warna semantik terkalibrasi, dan WCAG compliant."
>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:admin.dark-theme-system />
    </div>
</x-layouts::admin>
