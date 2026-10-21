<x-layouts::admin
    :title="__('Pop-up Modals UI Kit')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Design System'], ['label' => 'Modals UI Kit']]"
    pageIcon="fa-window-restore"
    pageTitle="Pop-up Modals UI Kit"
    pageDescription="Design system dan presentation board standar modal pop-up Shopee Enterprise Light Mode untuk VBAT Ponsel."
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.modal-ui-kit />
    </div>
</x-layouts::admin>
