<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('layouts.admin')] #[Title('Pengaturan tampilan')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading', [
        'pageIcon' => 'fa-palette',
        'pageTitle' => __('Tampilan'),
        'pageDescription' => __('Perbarui preferensi tema tampilan untuk akun Anda')
    ])

    <!-- Settings Navigation Tabs -->
    <div class="flex gap-1 mb-6 border-b border-gray-200 dark:border-zinc-800">
        <a href="{{ route('profile.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('profile.edit') ? 'border-b-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-semibold' : 'text-[#828282] dark:text-zinc-400 hover:text-[#212121] dark:hover:text-zinc-200' }}">
            {{ __('Profil') }}
        </a>
        <a href="{{ route('security.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('security.edit') ? 'border-b-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-semibold' : 'text-[#828282] dark:text-zinc-400 hover:text-[#212121] dark:hover:text-zinc-200' }}">
            {{ __('Keamanan') }}
        </a>
        <a href="{{ route('appearance.edit') }}" wire:navigate class="px-4 py-3 text-sm font-medium {{ request()->routeIs('appearance.edit') ? 'border-b-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-semibold' : 'text-[#828282] dark:text-zinc-400 hover:text-[#212121] dark:hover:text-zinc-200' }}">
            {{ __('Tampilan') }}
        </a>
    </div>

    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-slate-200/80 dark:border-zinc-800 shadow-sm overflow-hidden">
        <div class="px-6 py-6" x-data="{
            currentTheme: localStorage.getItem('theme') || 'light',
            setTheme(mode) {
                this.currentTheme = mode;
                if (mode === 'dark') {
                    localStorage.setItem('theme', 'dark');
                    localStorage.setItem('flux.appearance', 'dark');
                    document.documentElement.classList.add('dark');
                } else if (mode === 'light') {
                    localStorage.setItem('theme', 'light');
                    localStorage.setItem('flux.appearance', 'light');
                    document.documentElement.classList.remove('dark');
                } else {
                    localStorage.removeItem('theme');
                    localStorage.removeItem('flux.appearance');
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }
        }">
            <div class="space-y-3">
                <!-- Mode Terang (Light) -->
                <button 
                    type="button" 
                    @click="setTheme('light')"
                    class="w-full flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all cursor-pointer"
                    :class="currentTheme === 'light' 
                        ? 'bg-blue-50/70 dark:bg-blue-950/40 border-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-bold' 
                        : 'bg-white dark:bg-zinc-800/80 border border-slate-200 dark:border-zinc-700 text-[#212121] dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                >
                    <i class="fas fa-sun text-xl w-6 text-center" :class="currentTheme === 'light' ? 'text-[#1a73e8] dark:text-blue-400' : 'text-[#828282] dark:text-zinc-400'"></i>
                    <div class="flex-1 text-left">
                        <p class="text-sm font-medium">{{ __('Terang (Light Mode)') }}</p>
                    </div>
                    <i x-show="currentTheme === 'light'" class="fas fa-check text-[#1a73e8] dark:text-blue-400"></i>
                </button>

                <!-- Mode Gelap (Dark) -->
                <button 
                    type="button" 
                    @click="setTheme('dark')"
                    class="w-full flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all cursor-pointer"
                    :class="currentTheme === 'dark' 
                        ? 'bg-blue-50/70 dark:bg-blue-950/40 border-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-bold' 
                        : 'bg-white dark:bg-zinc-800/80 border border-slate-200 dark:border-zinc-700 text-[#212121] dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                >
                    <i class="fas fa-moon text-xl w-6 text-center" :class="currentTheme === 'dark' ? 'text-[#1a73e8] dark:text-blue-400' : 'text-[#828282] dark:text-zinc-400'"></i>
                    <div class="flex-1 text-left">
                        <p class="text-sm font-medium">{{ __('Gelap (Dark Mode)') }}</p>
                    </div>
                    <i x-show="currentTheme === 'dark'" class="fas fa-check text-[#1a73e8] dark:text-blue-400"></i>
                </button>

                <!-- Mode Sistem -->
                <button 
                    type="button" 
                    @click="setTheme('system')"
                    class="w-full flex items-center gap-4 px-4 py-3.5 rounded-xl transition-all cursor-pointer"
                    :class="currentTheme === 'system' 
                        ? 'bg-blue-50/70 dark:bg-blue-950/40 border-2 border-[#1a73e8] dark:border-blue-500 text-[#1a73e8] dark:text-blue-400 font-bold' 
                        : 'bg-white dark:bg-zinc-800/80 border border-slate-200 dark:border-zinc-700 text-[#212121] dark:text-zinc-200 hover:bg-slate-50 dark:hover:bg-zinc-800'"
                >
                    <i class="fas fa-desktop text-xl w-6 text-center" :class="currentTheme === 'system' ? 'text-[#1a73e8] dark:text-blue-400' : 'text-[#828282] dark:text-zinc-400'"></i>
                    <div class="flex-1 text-left">
                        <p class="text-sm font-medium">{{ __('Sistem (Ikuti Perangkat)') }}</p>
                    </div>
                    <i x-show="currentTheme === 'system'" class="fas fa-check text-[#1a73e8] dark:text-blue-400"></i>
                </button>
            </div>
        </div>
    </div>
</section>
