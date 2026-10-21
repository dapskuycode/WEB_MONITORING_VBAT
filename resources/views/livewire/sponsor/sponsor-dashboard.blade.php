<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;

new #[Layout('layouts.app')] #[Title('Dashboard Sponsor')] class extends Component {
    public function render()
    {
        return <<<'HTML'
        <div class="min-h-screen bg-[#F4F6F8]">
            <!-- Header Profile Section -->
            <div class="bg-white border-b border-gray-200 px-6 py-5">
                <div class="flex items-center justify-between max-w-7xl mx-auto">
                    <!-- Left: Partner Profile -->
                    <div class="flex items-center gap-6">
                        <div class="relative">
                            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xl">
                                SP
                            </div>
                            <button class="absolute bottom-0 right-0 w-6 h-6 bg-white rounded-full border-2 border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                                <i class="fas fa-camera text-xs text-gray-600"></i>
                            </button>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-[#212121]">Sponsor Partner</h2>
                            <span class="inline-block px-3 py-1 mt-1 bg-blue-50 text-blue-600 text-xs font-semibold rounded-full">TIER PARTNER</span>
                            <div class="flex items-center gap-4 mt-2 text-sm">
                                <a href="#" class="text-blue-600 hover:text-blue-700 hover:underline">Ubah Profil</a>
                                <span class="text-gray-300">|</span>
                                <a href="#" class="text-blue-600 hover:text-blue-700 hover:underline">Gambar Header</a>
                                <span class="text-gray-300">|</span>
                                <a href="#" class="text-blue-600 hover:text-blue-700 hover:underline">Upload Logo</a>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Action Buttons -->
                    <div class="flex items-center gap-3">
                        <button class="px-4 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Segarkan Data
                        </button>
                        <button class="px-4 py-2.5 bg-[#1a73e8] text-white font-medium rounded-lg hover:bg-[#1557b0] transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Ajukan Kampanye
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-7xl mx-auto px-6 py-8">
                <!-- Quota Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Hero Slider Quota -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-[#828282] mb-1">Hero Slider (16:9)</p>
                                <p class="text-lg font-bold text-[#212121]">Sisa Kuota: 2 dari 5</p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-images text-blue-600"></i>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-[#1a73e8] h-2 rounded-full" style="width: 40%"></div>
                        </div>
                    </div>

                    <!-- Horizontal Slider Quota -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-[#828282] mb-1">Horizontal Slider</p>
                                <p class="text-lg font-bold text-[#212121]">Sisa Kuota: 0 dari 2</p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center">
                                <i class="fas fa-sliders-h text-gray-600"></i>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-400 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Card Slider Quota -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-[#828282] mb-1">Card Slider</p>
                                <p class="text-lg font-bold text-[#212121]">Sisa Kuota: 10 dari 10</p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-th-large text-blue-600"></i>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-[#1a73e8] h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>

                    <!-- Pop Up Iklan Quota -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-[#828282] mb-1">Pop Up Iklan</p>
                                <p class="text-lg font-bold text-[#212121]">Sisa Kuota: 1 dari 1</p>
                            </div>
                            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                                <i class="fas fa-bullhorn text-blue-600"></i>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-[#1a73e8] h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Overview Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Daftar Kampanye Promosi -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <h3 class="text-lg font-bold text-[#212121] mb-4">Daftar Kampanye Promosi</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="text-left py-3 text-sm font-semibold text-[#828282]">Kampanye</th>
                                        <th class="text-left py-3 text-sm font-semibold text-[#828282]">Penempatan</th>
                                        <th class="text-left py-3 text-sm font-semibold text-[#828282]">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-3 text-sm text-[#212121]">Promo Akhir Tahun</td>
                                        <td class="py-3 text-sm text-[#828282]">Hero Slider</td>
                                        <td class="py-3">
                                            <span class="inline-block px-3 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-full">Menunggu Approval</span>
                                        </td>
                                    </tr>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-3 text-sm text-[#212121]">Banner Diskon 50%</td>
                                        <td class="py-3 text-sm text-[#828282]">Card Slider</td>
                                        <td class="py-3">
                                            <span class="inline-block px-3 py-1 bg-green-50 text-green-700 text-xs font-medium rounded-full">Disetujui</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-3 text-sm text-[#212121]">Flash Sale Produk</td>
                                        <td class="py-3 text-sm text-[#828282]">Pop Up Iklan</td>
                                        <td class="py-3">
                                            <span class="inline-block px-3 py-1 bg-green-50 text-green-700 text-xs font-medium rounded-full">Disetujui</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Katalog Produk & Suku Cadang -->
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-[#212121]">Katalog Produk & Suku Cadang Saya</h3>
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-700 hover:underline">Lihat Semua Produk</a>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-[#212121]">Aki Motor GS Astra Premium</p>
                                    <p class="text-sm text-[#828282] mt-1">Rp 350.000</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-[#212121]">Ban FDR Sport XR Evo 120/70</p>
                                    <p class="text-sm text-[#828282] mt-1">Rp 275.000</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-[#212121]">Oli Mesin Shell Helix HX7 1L</p>
                                    <p class="text-sm text-[#828282] mt-1">Rp 85.000</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
};

<div class="flex flex-col gap-6">
    <!-- Header Profile & Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <!-- Left: Partner Profile -->
            <div class="flex items-center gap-4">
                <!-- Avatar with Upload -->
                <div class="relative">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 text-2xl font-semibold">
                        SP
                    </div>
                    <button type="button" class="absolute -bottom-1 -right-1 w-6 h-6 bg-blue-500 rounded-full text-white flex items-center justify-center hover:bg-blue-600 transition-colors">
                        <i class="fas fa-camera-alt text-xs"></i>
                    </button>
                </div>

                <!-- Profile Info -->
                <div class="space-y-1">
                    <h2 class="text-xl font-semibold text-gray-900">Sponsor Partnership</h2>
                    <div class="inline-flex items-center gap-2">
                        <span class="px-3 py-1 bg-blue-50 text-blue-700 text-sm font-medium rounded-full">
                            TIER PARTNER
                        </span>
                        <span class="text-sm text-gray-500">•</span>
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Ubah Profil</button>
                        <span class="text-sm text-gray-500">•</span>
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Gambar Header</button>
                        <span class="text-sm text-gray-500">•</span>
                        <button type="button" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Upload Logo</button>
                    </div>
                </div>
            </div>

            <!-- Right: Action Buttons -->
            <div class="flex items-center gap-3">
                <button type="button" class="px-4 py-2.5 border border-gray-300 text-gray-700 font-medium text-sm rounded-lg hover:bg-gray-50 transition-colors">
                    Segarkan Data
                </button>
                <button type="button" class="px-4 py-2.5 bg-blue-600 text-white font-medium text-sm rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fas fa-plus mr-2"></i> Ajukan Kampanye
                </button>
            </div>
        </div>
    </div>

    <!-- QUOTA SUMMARY CARDS -->
    <div>
        <div class="mb-3">
            <h3 class="text-lg font-semibold text-gray-900">Kuota Tersisa</h3>
            <p class="text-sm text-gray-500">Tinjau kuota fitur yang masih tersedia</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Hero Slider (16:9) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fas fa-images"></i>
                        </div>
                        <span class="font-medium text-gray-900">Hero Slider (16:9)</span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-2">Sisa Kuota: <span class="font-medium text-gray-900">2 dari 5</span></p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 40%"></div>
                </div>
            </div>

            <!-- Horizontal Slider -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <span class="font-medium text-gray-900">Horizontal Slider</span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-2">Sisa Kuota: <span class="font-medium text-gray-900">0 dari 2</span></p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-gray-300 h-2 rounded-full" style="width: 0%"></div>
                </div>
            </div>

            <!-- Card Slider -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fas fa-th-large"></i>
                        </div>
                        <span class="font-medium text-gray-900">Card Slider</span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-2">Sisa Kuota: <span class="font-medium text-gray-900">10 dari 10</span></p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 100%"></div>
                </div>
            </div>

            <!-- Pop Up Iklan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <span class="font-medium text-gray-900">Pop Up Iklan</span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-2">Sisa Kuota: <span class="font-medium text-gray-900">1 dari 1</span></p>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK OVERVIEW TABLES -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left: Daftar Kampanye Promosi -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Daftar Kampanye Promosi</h3>
                    <p class="text-sm text-gray-500">Kampanye yang sedang berjalan</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="text-left text-sm font-medium text-gray-500">
                            <th class="pb-3">Kampanye</th>
                            <th class="pb-3">Penempatan</th>
                            <th class="pb-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="py-3">
                                <div>
                                    <p class="font-medium text-gray-900">Promo Smartphone Maret</p>
                                    <p class="text-sm text-gray-500">1–31 Mar 2025</p>
                                </div>
                            </td>
                            <td class="py-3 text-gray-700">Hero Slider</td>
                            <td class="py-3">
                                <span class="px-3 py-1 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-full">Menunggu Approval</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3">
                                <div>
                                    <p class="font-medium text-gray-900">Flash Sale Aksesoris</p>
                                    <p class="text-sm text-gray-500">10–12 Apr 2025</p>
                                </div>
                            </td>
                            <td class="py-3 text-gray-700">Card Slider</td>
                            <td class="py-3">
                                <span class="px-3 py-1 bg-green-50 text-green-700 text-sm font-medium rounded-full">Disetujui</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3">
                                <div>
                                    <p class="font-medium text-gray-900">Iklan Perawatan HP</p>
                                    <p class="text-sm text-gray-500">5–20 Mei 2025</p>
                                </div>
                            </td>
                            <td class="py-3 text-gray-700">Pop Up</td>
                            <td class="py-3">
                                <span class="px-3 py-1 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-full">Menunggu Approval</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Katalog Produk & Suku Cadang -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Katalog Produk & Suku Cadang Saya</h3>
                    <p class="text-sm text-gray-500">Produk dan suku cadang yang tersedia</p>
                </div>
                <a href="#" class="text-sm font-medium text-blue-600 hover:text-blue-800">Lihat Semua Produk →</a>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Smartphone ABC Pro</h4>
                        <p class="text-sm text-gray-500">Model 2025, 128GB</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">Rp 4.299.000</p>
                        <p class="text-sm text-gray-500">Stok: 12</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-headphones-alt text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Wireless Earbuds</h4>
                        <p class="text-sm text-gray-500">Noise Cancelling</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">Rp 899.000</p>
                        <p class="text-sm text-gray-500">Stok: 45</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-battery-full text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Baterai HP Original</h4>
                        <p class="text-sm text-gray-500">5000mAh, Multi-Model</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">Rp 249.000</p>
                        <p class="text-sm text-gray-500">Stok: 78</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>