<x-layouts::admin 
    :title="__('Pengajuan Push Notification')"
    pageIcon="fa-bell"
    pageTitle="Pengajuan Push Notification"
    pageDescription="Ajukan siaran pesan promosi dan pengumuman untuk dikirimkan ke ponsel pengguna."
>
    <x-slot:actions>
        <button 
            type="button"
            onclick="Livewire.dispatch('trigger-create-notification')" 
            class="inline-flex items-center gap-2 px-4 py-2 bg-[#1a68f0] hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors cursor-pointer shrink-0"
        >
            <i class="fas fa-plus text-xs"></i>
            <span>Ajukan Notifikasi Baru</span>
        </button>
    </x-slot:actions>

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:sponsor.sponsor-notifications />
    </div>
</x-layouts::admin>

