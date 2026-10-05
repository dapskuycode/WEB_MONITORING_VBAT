<x-layouts::admin
    :title="__('Super Dashboard')"
    :breadcrumb="[['label' => 'Dashboard']]"
>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:admin.analytics-dashboard />
    </div>
</x-layouts::admin>