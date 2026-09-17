<?php

use Livewire\Component;
use App\Models\Campaign;
use App\Models\CampaignLog;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component
{
    public $sponsor;

    public function mount()
    {
        $user = Auth::user();
        $this->sponsor = $user?->sponsor ?? Sponsor::first();
    }

    public function with(): array
    {
        if (!$this->sponsor) {
            return [
                'hasSponsor' => false,
                'totalViews' => 0,
                'totalClicks' => 0,
                'ctr' => 0,
                'totalProducts' => 0,
                'totalWishlists' => 0,
                'campaigns' => collect(),
                'placementCounts' => [],
            ];
        }

        $sponsorId = $this->sponsor->id;

        $campaignIds = Campaign::where('sponsor_id', $sponsorId)->pluck('id');
        $productIds = SponsorProduct::where('sponsor_id', $sponsorId)->pluck('id');

        $totalViews = CampaignLog::whereIn('campaign_id', $campaignIds)->where('event_type', 'view')->count();
        $totalClicks = CampaignLog::whereIn('campaign_id', $campaignIds)->where('event_type', 'click')->count();
        $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        $totalProducts = $productIds->count();
        $totalWishlists = CampaignLog::whereIn('sponsor_product_id', $productIds)->where('event_type', 'wishlist')->count();

        $placementCounts = [
            'hero_slider' => Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'hero_slider')->count(),
            'shop_horizontal' => Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'shop_horizontal')->count(),
            'card_slider' => Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'card_slider')->count(),
            'popup_modal' => Campaign::where('sponsor_id', $sponsorId)->where('placement_type', 'popup_modal')->count(),
        ];

        $campaigns = Campaign::where('sponsor_id', $sponsorId)
            ->withCount([
                'logs as views_count' => fn($q) => $q->where('event_type', 'view'),
                'logs as clicks_count' => fn($q) => $q->where('event_type', 'click'),
            ])
            ->latest()
            ->take(10)
            ->get();

        $topProducts = SponsorProduct::where('sponsor_id', $sponsorId)
            ->withCount('wishlists')
            ->orderByDesc('view_count')
            ->take(5)
            ->get();

        return [
            'hasSponsor' => true,
            'totalViews' => $totalViews,
            'totalClicks' => $totalClicks,
            'ctr' => $ctr,
            'totalProducts' => $totalProducts,
            'totalWishlists' => $totalWishlists,
            'campaigns' => $campaigns,
            'topProducts' => $topProducts,
            'placementCounts' => $placementCounts,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header Sponsor -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-800 pb-5">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-blue-500/20 border border-blue-400/20">
                {{ strtoupper(substr($sponsor?->name ?? 'SP', 0, 2)) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-white tracking-tight">{{ $sponsor?->name ?? 'Portal Mitra Sponsor' }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-900/60 text-blue-300 border border-blue-700/50">
                        TIER {{ strtoupper($sponsor?->tier ?? 'PARTNER') }}
                    </span>
                </div>
                <p class="text-xs text-zinc-400 mt-1">
                    Dashboard analitik performa iklan & katalog produk khusus mitra {{ $sponsor?->name }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" wire:click="$refresh" class="px-3.5 py-2 rounded-xl bg-zinc-800/80 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold border border-zinc-700/80 transition flex items-center gap-1.5 shadow-sm">
                <flux:icon name="arrow-path" class="w-3.5 h-3.5" />
                <span>Segarkan Data</span>
            </button>
            <a href="{{ route('sponsor.campaigns.hero') }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition flex items-center gap-1.5">
                <flux:icon name="plus" class="w-3.5 h-3.5" />
                <span>+ Ajukan Kampanye</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Khusus Sponsor (Dark Harmonized) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Impresi Banner -->
        <div class="p-5 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-sm relative overflow-hidden group hover:border-blue-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Tayangan Banner</span>
                <div class="p-2.5 rounded-xl bg-blue-950/60 text-blue-400 border border-blue-900/50">
                    <flux:icon name="eye" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-white tracking-tight">{{ number_format($totalViews) }}</span>
                <p class="text-xs text-zinc-400 mt-1">Total impresi ke pengguna ponsel</p>
            </div>
        </div>

        <!-- 2. Total Klik & Interaksi -->
        <div class="p-5 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-sm relative overflow-hidden group hover:border-emerald-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Klik Iklan</span>
                <div class="p-2.5 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-900/50">
                    <flux:icon name="cursor-arrow-rays" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-emerald-400 tracking-tight">{{ number_format($totalClicks) }}</span>
                <p class="text-xs text-zinc-400 mt-1">Interaksi langsung ke tautan/marketplace</p>
            </div>
        </div>

        <!-- 3. Rata-rata CTR -->
        <div class="p-5 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-sm relative overflow-hidden group hover:border-amber-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Efektivitas (CTR)</span>
                <div class="p-2.5 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-900/50">
                    <flux:icon name="arrow-trending-up" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-amber-400 tracking-tight">{{ $ctr }}%</span>
                <p class="text-xs text-zinc-400 mt-1">Rasio klik dibanding tayangan</p>
            </div>
        </div>

        <!-- 4. Peminat Produk (Wishlist) -->
        <div class="p-5 rounded-2xl bg-zinc-900/90 border border-zinc-800 shadow-sm relative overflow-hidden group hover:border-purple-500/50 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Wishlist Produk</span>
                <div class="p-2.5 rounded-xl bg-purple-950/60 text-purple-400 border border-purple-900/50">
                    <flux:icon name="heart" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-3">
                <span class="text-3xl font-extrabold text-purple-400 tracking-tight">{{ number_format($totalWishlists) }}</span>
                <p class="text-xs text-zinc-400 mt-1">Disimpan oleh teknisi di ponsel</p>
            </div>
        </div>
    </div>

    <!-- Status Penempatan Iklan Aktif Sponsor (Dark Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('sponsor.campaigns.hero') }}" class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 hover:border-blue-500/60 hover:bg-zinc-850 transition block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-400 group-hover:text-blue-300">Hero Slider (16:9)</span>
                <span class="text-lg font-bold text-blue-400">{{ $placementCounts['hero_slider'] ?? 0 }}</span>
            </div>
            <p class="text-[11px] text-zinc-400 mt-1">Header atas Beranda</p>
        </a>
        <a href="{{ route('sponsor.campaigns.horizontal') }}" class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 hover:border-indigo-500/60 hover:bg-zinc-850 transition block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-400 group-hover:text-indigo-300">Horizontal Slider</span>
                <span class="text-lg font-bold text-indigo-400">{{ $placementCounts['shop_horizontal'] ?? 0 }}</span>
            </div>
            <p class="text-[11px] text-zinc-400 mt-1">Shop & Separator Home</p>
        </a>
        <a href="{{ route('sponsor.campaigns.card') }}" class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 hover:border-teal-500/60 hover:bg-zinc-850 transition block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-400 group-hover:text-teal-300">Card Slider</span>
                <span class="text-lg font-bold text-teal-400">{{ $placementCounts['card_slider'] ?? 0 }}</span>
            </div>
            <p class="text-[11px] text-zinc-400 mt-1">Grid Promo Beranda</p>
        </a>
        <a href="{{ route('sponsor.campaigns.popup') }}" class="p-4 rounded-2xl bg-zinc-900/80 border border-zinc-800 hover:border-rose-500/60 hover:bg-zinc-850 transition block group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-400 group-hover:text-rose-300">Pop Up Iklan</span>
                <span class="text-lg font-bold text-rose-400">{{ $placementCounts['popup_modal'] ?? 0 }}</span>
            </div>
            <p class="text-[11px] text-zinc-400 mt-1">Dialog Startup Ponsel</p>
        </a>
    </div>

    <!-- Tabel Kampanye & Produk Terbaru Sponsor -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kampanye Iklan Terbaru Sponsor (2 Kolom) -->
        <div class="lg:col-span-2 bg-zinc-900/90 rounded-2xl border border-zinc-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-white">Daftar Kampanye Promosi</h3>
                    <p class="text-xs text-zinc-400">Status persetujuan dan metrik performa tiap iklan</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-800 text-xs font-semibold text-zinc-400 uppercase">
                            <th class="pb-3">Iklan / Penempatan</th>
                            <th class="pb-3 text-center">Status</th>
                            <th class="pb-3 text-right">Tayang</th>
                            <th class="pb-3 text-right">Klik</th>
                            <th class="pb-3 text-right">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800/80">
                        @forelse($campaigns as $camp)
                        <tr class="hover:bg-zinc-800/50 transition">
                            <td class="py-3.5 pr-4 flex items-center gap-3">
                                <div class="rounded-lg bg-zinc-950 overflow-hidden relative border border-zinc-800 flex items-center justify-center shrink-0" style="width: 48px; height: 32px;">
                                    <img src="{{ $camp->thumbnail_url }}" style="width: 48px; height: 32px; object-fit: cover;" alt="{{ $camp->title }}" onerror="this.src='{{ asset('assets/images/banner_promo_diskon.png') }}'" />
                                    @if($camp->media_type === 'video')
                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center text-white pointer-events-none">
                                            <flux:icon name="play" class="w-2.5 h-2.5 text-white fill-white" />
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-white truncate max-w-xs">{{ $camp->title }}</div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[11px] text-zinc-400 uppercase font-mono">{{ str_replace('_', ' ', $camp->placement_type) }}</span>
                                        <span class="text-[11px] text-zinc-400">• {{ $camp->media_type }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 text-center">
                                @if($camp->status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-950/60 text-emerald-400 border border-emerald-800/50">Aktif</span>
                                @elseif($camp->status === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-950/60 text-amber-400 border border-amber-800/50">Menunggu</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-950/60 text-rose-400 border border-rose-800/50">Ditolak</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-right font-mono text-zinc-300">{{ number_format($camp->views_count) }}</td>
                            <td class="py-3.5 text-right font-mono text-emerald-400">{{ number_format($camp->clicks_count) }}</td>
                            <td class="py-3.5 text-right font-mono font-bold text-white">
                                {{ $camp->views_count > 0 ? round(($camp->clicks_count / $camp->views_count) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-zinc-400 text-sm">
                                Belum ada kampanye iklan yang diajukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Produk Milik Sponsor (1 Kolom - Dark Styled, No Stark White!) -->
        <div class="bg-zinc-900/90 rounded-2xl border border-zinc-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-base text-white">Katalog Terlaris</h3>
                    <p class="text-xs text-zinc-400">Produk paling banyak diminati</p>
                </div>
            </div>

            <div class="space-y-3">
                @forelse($topProducts ?? [] as $prod)
                <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-800/60 border border-zinc-700/60 hover:border-zinc-600 hover:bg-zinc-800/90 transition group">
                    <div class="w-12 h-12 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center justify-center overflow-hidden shrink-0">
                        @if($prod->image_path)
                            <img src="{{ str_starts_with($prod->image_path, 'http') ? $prod->image_path : asset($prod->image_path) }}" class="w-full h-full object-cover" alt="{{ $prod->name }}" onerror="this.src='{{ asset('assets/images/product_1.png') }}'" />
                        @else
                            <flux:icon name="shopping-bag" class="w-5 h-5 text-zinc-400" />
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-zinc-200 group-hover:text-white truncate">{{ $prod->name }}</div>
                        <div class="text-xs text-blue-400 font-mono font-bold mt-0.5">Rp {{ number_format($prod->price, 0, ',', '.') }}</div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs font-bold text-purple-400 flex items-center gap-1">
                            <flux:icon name="heart" class="w-3.5 h-3.5" />
                            <span>{{ $prod->wishlists_count }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="py-8 text-center text-zinc-400 text-xs">
                    Belum ada produk aktif di katalog.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
