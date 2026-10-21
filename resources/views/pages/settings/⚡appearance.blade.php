<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use function Livewire\Volt\{layout};

layout('layouts.admin');

new #[Title('Pengaturan tampilan')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading', [
        'pageIcon' => 'fa-palette',
        'pageTitle' => __('Tampilan'),
        'pageDescription' => __('Perbarui pengaturan tampilan untuk akun Anda')
    ])

    <!-- Settings Navigation Tabs -->
    <div class="flex gap-1 mb-6 border-b border-gray-200">
        <a href="{{ route('profile.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('profile.edit') ? 'border-b-2 border-[#1a73e8] text-[#1a73e8]' : 'text-[#828282] hover:text-[#212121]' }}">
            {{ __('Profil') }}
        </a>
        <a href="{{ route('security.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('security.edit') ? 'border-b-2 border-[#1a73e8] text-[#1a73e8]' : 'text-[#828282] hover:text-[#212121]' }}">
            {{ __('Keamanan') }}
        </a>
        <a href="{{ route('appearance.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('appearance.edit') ? 'border-b-2 border-[#1a73e8] text-[#1a73e8]' : 'text-[#828282] hover:text-[#212121]' }}">
            {{ __('Tampilan') }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-6">
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
        </div>
    </div>
</section>
