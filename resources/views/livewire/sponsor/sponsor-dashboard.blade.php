<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] #[Title('Dashboard Sponsor')] class extends Component {
    public function render()
    {
        return <<<'HTML'
        <x-layouts::admin :title="__('Dashboard Sponsor')">
            <div class="flex flex-col gap-7 select-none">
                <!-- Header Profile & Actions Section -->
                <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <!-- Left: Partner Profile -->
                        <div class="flex items-center gap-6">
                            <!-- Avatar with Upload Button -->
                            <div class="relative">
                                <div class="w-20 h-20 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-2xl shrink-0">
                                    SP
                                </div>
                                <button type="button" class="absolute -bottom-1 -right-1 w-7 h-7 bg-[#1a68f0] dark:bg-blue-600 rounded-full text-white flex items-center justify-center hover:bg-blue-700 dark:hover:bg-blue-500 transition-colors shadow-md">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.141.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Profile Info & Links -->
                            <div class="flex-1 min-w-0">
                                <h2 class="text-2xl font-extrabold text-[#212121] dark:text-white tracking-tight">Sponsor Partner</h2>
                                <div class="flex items-center flex-wrap gap-2 mt-2">
                                    <span class="inline-block px-3 py-1 bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 text-xs font-semibold rounded-full">
                                        TIER PARTNER
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-3 text-sm">
                                    <a href="#" class="font-medium text-[#1a68f0] dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">Ubah Profil</a>
                                    <span class="text-slate-300 dark:text-zinc-600">|</span>
                                    <a href="#" class="font-medium text-[#1a68f0] dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">Gambar Header</a>
                                    <span class="text-slate-300 dark:text-zinc-600">|</span>
                                    <a href="#" class="font-medium text-[#1a68f0] dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">Upload Logo</a>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Action Buttons -->
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <button type="button" class="px-4 py-2.5 border border-slate-300 dark:border-zinc-600 text-slate-700 dark:text-zinc-200 font-medium text-sm rounded-xl hover:bg-slate-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-5.971m0 0h4.992m-4.993 0a3.972 3.972 0 0 1 3.679-2.012v-3.62m0 0h.008v3.62a3.972 3.972 0 0 1 3.678 2.012m0 0v5.971m0 0h4.992" />
                                </svg>
                                Segarkan Data
                            </button>
                            <button type="button" class="px-4 py-2.5 bg-[#1a68f0] dark:bg-blue-600 text-white font-medium text-sm rounded-xl hover:bg-blue-700 dark:hover:bg-blue-500 transition-colors shadow-sm">
                                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                Ajukan Kampanye
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quota Summary Cards -->
                <div>
                    <h3 class="text-lg font-bold text-[#212121] dark:text-white mb-4">Kuota Tersisa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- Hero Slider (16:9) -->
                        <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400 mb-1">Hero Slider (16:9)</p>
                                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white">2 <span class="text-base font-semibold text-slate-400">/ 5</span></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-[#1a68f0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2l1.586-1.586a2 2 0 0 1 2.828 0L20 14m-6-6h.01M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="bg-[#1a68f0] h-2 rounded-full" style="width: 40%"></div>
                            </div>
                        </div>

                        <!-- Horizontal Slider -->
                        <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400 mb-1">Horizontal Slider</p>
                                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white">0 <span class="text-base font-semibold text-slate-400">/ 2</span></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-zinc-800 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-slate-500 dark:text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h16.5M3.75 9.75H21m-13.5 5.25h13.5M3.75 20.25h16.5" />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="bg-slate-300 dark:bg-zinc-600 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>

                        <!-- Card Slider -->
                        <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400 mb-1">Card Slider</p>
                                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white">10 <span class="text-base font-semibold text-slate-400">/ 10</span></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-[#1a68f0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 3h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="bg-[#1a68f0] h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>

                        <!-- Pop Up Iklan -->
                        <div class="p-6 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs sm:text-sm font-medium text-slate-500 dark:text-zinc-400 mb-1">Pop Up Iklan</p>
                                    <p class="text-2xl sm:text-3xl font-extrabold text-[#212121] dark:text-white">1 <span class="text-base font-semibold text-slate-400">/ 1</span></p>
                                </div>
                                <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-[#1a68f0]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.651a3.75 3.75 0 0 1 5.304 0l7.753-7.753a3 3 0 0 0-4.243-4.243L12 7.757 4.591.348a3 3 0 1 0 4.243 4.243L12 16l7.348-7.348z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-zinc-800 rounded-full h-2">
                                <div class="bg-[#1a68f0] h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Overview Tables Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Daftar Kampanye Promosi -->
                    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                        <h3 class="text-lg font-bold text-[#212121] dark:text-white mb-4">Daftar Kampanye Promosi</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-start border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-zinc-800/80 text-[13px] font-semibold text-slate-400 dark:text-zinc-500">
                                        <th class="py-3 px-4 text-start font-medium">Kampanye</th>
                                        <th class="py-3 px-4 text-start font-medium">Penempatan</th>
                                        <th class="py-3 px-4 text-start font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 dark:divide-zinc-800/60 text-sm">
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                        <td class="py-4 px-4 font-medium text-slate-700 dark:text-zinc-200">Promo Akhir Tahun</td>
                                        <td class="py-4 px-4 text-slate-600 dark:text-zinc-400">Hero Slider</td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-50 dark:bg-yellow-950/30 text-yellow-700 dark:text-yellow-300">Menunggu Approval</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                        <td class="py-4 px-4 font-medium text-slate-700 dark:text-zinc-200">Banner Diskon 50%</td>
                                        <td class="py-4 px-4 text-slate-600 dark:text-zinc-400">Card Slider</td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300">Disetujui</span>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40 transition-colors">
                                        <td class="py-4 px-4 font-medium text-slate-700 dark:text-zinc-200">Flash Sale Produk</td>
                                        <td class="py-4 px-4 text-slate-600 dark:text-zinc-400">Pop Up Iklan</td>
                                        <td class="py-4 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300">Disetujui</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Katalog Produk & Suku Cadang -->
                    <div class="p-6 sm:p-8 rounded-2xl bg-white dark:bg-zinc-900 border border-slate-200/60 dark:border-zinc-800 shadow-[0_4px_20px_-4px_rgba(20,40,90,0.04)]">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-[#212121] dark:text-white">Katalog Produk & Suku Cadang Saya</h3>
                            <a href="#" class="text-sm font-semibold text-[#1a68f0] dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">Lihat Semua Produk</a>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl bg-slate-50 dark:bg-zinc-800 border border-slate-100 dark:border-zinc-700/60 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-700 dark:text-zinc-200 truncate">Aki Motor GS Astra Premium</p>
                                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">Rp 350.000</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl bg-slate-50 dark:bg-zinc-800 border border-slate-100 dark:border-zinc-700/60 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-700 dark:text-zinc-200 truncate">Ban FDR Sport XR Evo 120/70</p>
                                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">Rp 275.000</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl bg-slate-50 dark:bg-zinc-800 border border-slate-100 dark:border-zinc-700/60 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-slate-400 dark:text-zinc-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-700 dark:text-zinc-200 truncate">Oli Mesin Shell Helix HX7 1L</p>
                                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">Rp 85.000</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-layouts::admin>
        HTML;
    }
};
