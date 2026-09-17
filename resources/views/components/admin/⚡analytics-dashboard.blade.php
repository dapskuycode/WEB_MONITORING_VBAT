<?php

use Livewire\Component;
use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\SponsorProduct;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component
{
    public function with(): array
    {
        // 1. Demografi Usia
        $usersWithBirth = User::whereNotNull('birth_date')->get();
        $ageGroups = [
            '< 23 Thn (Gen-Z)' => 0,
            '23 - 30 Thn' => 0,
            '31 - 40 Thn' => 0,
            '> 40 Thn' => 0,
        ];

        foreach ($usersWithBirth as $u) {
            $age = Carbon::parse($u->birth_date)->age;
            if ($age < 23) $ageGroups['< 23 Thn (Gen-Z)']++;
            elseif ($age <= 30) $ageGroups['23 - 30 Thn']++;
            elseif ($age <= 40) $ageGroups['31 - 40 Thn']++;
            else $ageGroups['> 40 Thn']++;
        }

        // 2. Demografi Gender
        $genderCounts = [
            'Laki-laki' => User::where('gender', 'male')->count(),
            'Perempuan' => User::where('gender', 'female')->count(),
            'Lainnya' => User::where('gender', 'other')->orWhereNull('gender')->count(),
        ];

        // 3. Demografi Kota Teratas
        $topCities = User::join('cities', 'users.city_id', '=', 'cities.id')
            ->select('cities.name as city_name', DB::raw('count(users.id) as total'))
            ->groupBy('cities.name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // 4. Performa Promosi
        $totalViews = CampaignLog::where('event_type', 'view')->count();
        $totalClicks = CampaignLog::where('event_type', 'click')->count();
        $totalWishlists = CampaignLog::where('event_type', 'wishlist')->count();
        $overallCtr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        $campaigns = Campaign::with('sponsor')
            ->withCount([
                'logs as views_count' => fn($q) => $q->where('event_type', 'view'),
                'logs as clicks_count' => fn($q) => $q->where('event_type', 'click'),
            ])
            ->orderByDesc('views_count')
            ->take(6)
            ->get();

        // 5. Heat Index Produk: Score = (Clicks * 0.5) + (Views * 0.2) + (Wishlists * 0.3)
        $products = SponsorProduct::with('sponsor')
            ->withCount('wishlists')
            ->get()
            ->map(function ($p) {
                $score = ($p->click_count * 0.5) + ($p->view_count * 0.2) + ($p->wishlists_count * 0.3);
                $p->heat_index = round($score, 1);
                return $p;
            })
            ->sortByDesc('heat_index')
            ->take(8);

        return [
            'ageGroups' => $ageGroups,
            'genderCounts' => $genderCounts,
            'topCities' => $topCities,
            'totalViews' => $totalViews,
            'totalClicks' => $totalClicks,
            'totalWishlists' => $totalWishlists,
            'overallCtr' => $overallCtr,
            'campaigns' => $campaigns,
            'topProducts' => $products,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Dashboard Analitik Super Admin & Owner
            </h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                Visualisasi metrik demografi pengguna, performa kampanye sponsor, dan Heat Index ketertarikan produk.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                <span class="w-2 h-2 mr-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Real-time Analytics
            </span>
        </div>
    </div>

    <!-- Metrik Ringkas Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Tayangan Iklan</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
            </div>
            <div class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($totalViews) }}</div>
            <p class="text-xs text-zinc-500 mt-1">Impression pada Hero & Banner</p>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Klik Sponsor</span>
                <div class="w-8 h-8 rounded-lg bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center text-orange-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                </div>
            </div>
            <div class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($totalClicks) }}</div>
            <p class="text-xs text-zinc-500 mt-1">Pengunjung menuju marketplace</p>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rata-rata CTR</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ $overallCtr }}%</div>
            <p class="text-xs text-zinc-500 mt-1">Click-Through-Rate kampanye</p>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Interaksi Wishlist</span>
                <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                </div>
            </div>
            <div class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($totalWishlists) }}</div>
            <p class="text-xs text-zinc-500 mt-1">Produk disimpan pengguna</p>
        </div>
    </div>

    <!-- SEKSI 1: Demografi Pengguna (Usia, Gender, Domisili) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Chart Usia -->
        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <h3 class="font-bold text-zinc-900 dark:text-white flex items-center gap-2 mb-4">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                Sebaran Usia Teknisi
            </h3>
            <div class="space-y-3">
                @php $totalAge = array_sum($ageGroups); @endphp
                @foreach ($ageGroups as $range => $count)
                    @php $pct = $totalAge > 0 ? round(($count / $totalAge) * 100, 1) : 0; @endphp
                    <div>
                        <div class="flex justify-between text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1">
                            <span>{{ $range }}</span>
                            <span>{{ $count }} orang ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Rasio Gender -->
        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <h3 class="font-bold text-zinc-900 dark:text-white flex items-center gap-2 mb-4">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                Rasio Jenis Kelamin
            </h3>
            <div class="space-y-4">
                @php $totalGender = array_sum($genderCounts); @endphp
                @foreach ($genderCounts as $gLabel => $gCount)
                    @php $gPct = $totalGender > 0 ? round(($gCount / $totalGender) * 100, 1) : 0; @endphp
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-100 dark:border-zinc-700">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center {{ $gLabel === 'Laki-laki' ? 'bg-blue-100 text-blue-600' : ($gLabel === 'Perempuan' ? 'bg-pink-100 text-pink-600' : 'bg-zinc-200 text-zinc-600') }}">
                                <span class="text-xs font-bold">{{ substr($gLabel, 0, 1) }}</span>
                            </div>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $gLabel }}</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-zinc-900 dark:text-white">{{ $gCount }}</div>
                            <div class="text-xs text-zinc-500">{{ $gPct }}%</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top Domisili Kota -->
        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <h3 class="font-bold text-zinc-900 dark:text-white flex items-center gap-2 mb-4">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                Top Kota Domisili Pengguna
            </h3>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($topCities as $idx => $city)
                    <div class="py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 text-xs font-bold flex items-center justify-center">
                                {{ $idx + 1 }}
                            </span>
                            <span class="text-xs font-medium text-zinc-800 dark:text-zinc-200">{{ $city->city_name }}</span>
                        </div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800">
                            {{ $city->total }} user
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-zinc-500 py-4 text-center">Belum ada data demografi kota.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- SEKSI 2: Ketertarikan Produk (Heat Index) & Performa Promosi -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Heat Index Produk Terpopuler -->
        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                        Ketertarikan Produk (Heat Index)
                    </h3>
                    <p class="text-xs text-zinc-500">Kalkulasi gabungan: (Clicks × 0.5) + (Views × 0.2) + (Wishlist × 0.3)</p>
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-zinc-400 font-semibold border-b border-zinc-100 dark:border-zinc-800">
                            <th class="pb-2">Peringkat</th>
                            <th class="pb-2">Produk & Sponsor</th>
                            <th class="pb-2 text-center">Views</th>
                            <th class="pb-2 text-center">Clicks</th>
                            <th class="pb-2 text-center">Wishlist</th>
                            <th class="pb-2 text-right">Heat Score</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($topProducts as $prod)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3 font-bold">
                                    @if ($loop->iteration === 1)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-black">🥇 1</span>
                                    @elseif ($loop->iteration === 2)
                                        <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 font-black">🥈 2</span>
                                    @elseif ($loop->iteration === 3)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-700/20 text-amber-900 font-black">🥉 3</span>
                                    @else
                                        <span class="text-zinc-500 ml-2">#{{ $loop->iteration }}</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="font-semibold text-zinc-900 dark:text-white truncate max-w-[180px]">{{ $prod->name }}</div>
                                    <div class="text-[10px] text-zinc-400">{{ $prod->sponsor?->name }}</div>
                                </td>
                                <td class="py-3 text-center text-zinc-600 dark:text-zinc-400">{{ $prod->view_count }}</td>
                                <td class="py-3 text-center text-zinc-600 dark:text-zinc-400 font-medium">{{ $prod->click_count }}</td>
                                <td class="py-3 text-center text-zinc-600 dark:text-zinc-400">{{ $prod->wishlists_count }}</td>
                                <td class="py-3 text-right font-black text-orange-600 dark:text-orange-400 text-sm">
                                    🔥 {{ $prod->heat_index }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Performa Kampanye Sponsor -->
        <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        Performa Kampanye Sponsor
                    </h3>
                    <p class="text-xs text-zinc-500">Statistik tayangan, klik dan rasio konversi tayang</p>
                </div>
            </div>

            <div class="divide-y divide-zinc-100 dark:divide-zinc-800 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-zinc-400 font-semibold border-b border-zinc-100 dark:border-zinc-800">
                            <th class="pb-2">Kampanye</th>
                            <th class="pb-2">Posisi</th>
                            <th class="pb-2 text-center">Tayangan</th>
                            <th class="pb-2 text-center">Klik</th>
                            <th class="pb-2 text-right">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($campaigns as $camp)
                            @php
                                $campViews = $camp->views_count;
                                $campClicks = $camp->clicks_count;
                                $campCtr = $campViews > 0 ? round(($campClicks / $campViews) * 100, 1) : 0;
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="py-3">
                                    <div class="font-semibold text-zinc-900 dark:text-white truncate max-w-[170px]">{{ $camp->title }}</div>
                                    <div class="text-[10px] text-zinc-400">{{ $camp->sponsor?->name }} ({{ strtoupper($camp->sponsor?->tier ?? 'PARTNER') }})</div>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 text-[10px] rounded font-semibold {{ $camp->placement_type === 'hero_slider' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $camp->placement_type === 'hero_slider' ? 'Hero Slider' : 'Shop Banner' }}
                                    </span>
                                </td>
                                <td class="py-3 text-center text-zinc-600 dark:text-zinc-400">{{ $campViews }}</td>
                                <td class="py-3 text-center text-zinc-600 dark:text-zinc-400 font-medium">{{ $campClicks }}</td>
                                <td class="py-3 text-right font-bold {{ $campCtr >= 5 ? 'text-emerald-600' : 'text-zinc-700 dark:text-zinc-300' }}">
                                    {{ $campCtr }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>