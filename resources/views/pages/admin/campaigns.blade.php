<x-layouts::admin
    :title="__('Moderasi Kampanye')"
    :breadcrumb="[['label' => 'Admin', 'url' => route('dashboard')], ['label' => 'Moderasi Kampanye']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.campaign-moderation />
    </div>
</x-layouts::admin>