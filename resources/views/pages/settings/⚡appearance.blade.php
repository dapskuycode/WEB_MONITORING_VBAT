<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Pengaturan tampilan')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading', [
        'pageIcon' => 'fa-palette',
        'pageTitle' => __('Tampilan'),
        'pageDescription' => __('Perbarui pengaturan tampilan untuk akun Anda')
    ])

    <x-pages::settings.layout :heading="__('Tampilan')" :subheading="__('Perbarui pengaturan tampilan untuk akun Anda')">
        <!-- ponytail: Alpine.js theme toggle ketika backend implement theme persistence -->
        <div class="space-y-3">
            <button type="button" class="w-full flex items-center gap-4 px-4 py-3 bg-white border-2 border-[#1a73e8] rounded-lg transition-colors hover:bg-blue-50">
                <i class="fas fa-sun text-[#1a73e8] text-xl w-6 text-center"></i>
                <div class="flex-1 text-left">
                    <p class="text-sm font-medium text-[#212121]">{{ __('Terang') }}</p>
                </div>
                <i class="fas fa-check text-[#1a73e8]"></i>
            </button>

            <button type="button" class="w-full flex items-center gap-4 px-4 py-3 bg-white border border-gray-200 rounded-lg transition-colors hover:bg-gray-50">
                <i class="fas fa-moon text-[#828282] text-xl w-6 text-center"></i>
                <div class="flex-1 text-left">
                    <p class="text-sm font-medium text-[#212121]">{{ __('Gelap') }}</p>
                </div>
            </button>

            <button type="button" class="w-full flex items-center gap-4 px-4 py-3 bg-white border border-gray-200 rounded-lg transition-colors hover:bg-gray-50">
                <i class="fas fa-desktop text-[#828282] text-xl w-6 text-center"></i>
                <div class="flex-1 text-left">
                    <p class="text-sm font-medium text-[#212121]">{{ __('Sistem') }}</p>
                </div>
            </button>
        </div>
    </x-pages::settings.layout>
</section>
