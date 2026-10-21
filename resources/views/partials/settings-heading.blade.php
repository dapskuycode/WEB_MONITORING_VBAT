<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        @if(isset($pageIcon))
        <div class="w-10 h-10 rounded-lg bg-[#EBF3FE] flex items-center justify-center">
            <i class="fas {{ $pageIcon }} text-[#1a73e8] text-lg"></i>
        </div>
        @endif
        <h1 class="text-3xl font-bold text-[#212121]">{{ $pageTitle ?? __('Pengaturan') }}</h1>
    </div>
    <p class="text-sm text-[#828282] mt-1">{{ $pageDescription ?? __('Kelola profil dan pengaturan akun Anda') }}</p>
</div>
