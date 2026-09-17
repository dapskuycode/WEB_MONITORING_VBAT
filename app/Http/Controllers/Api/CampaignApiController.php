<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BestDeal;
use App\Models\Campaign;
use App\Models\DiscountEvent;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Http\JsonResponse;

class CampaignApiController extends Controller
{
    /**
     * Poin 1.1 Hero Slider Banner (Area Paling Atas Beranda):
     * Menampilkan kampanye promosi khusus hero slider beranda (maksimal 6 slide).
     */
    public function getHeroSliders(): JsonResponse
    {
        $campaigns = Campaign::with('sponsor')
            ->active()
            ->where('placement_type', 'hero_slider')
            ->orderByDesc('weight')
            ->orderByDesc('id')
            ->take(6)
            ->get()
            ->map(function ($c) {
                return $this->formatCampaignPayload($c);
            });

        return response()->json([
            'status' => 'success',
            'total_slides' => $campaigns->count(),
            'data' => $campaigns,
        ]);
    }

    /**
     * Brand & Partner Resmi:
     * Mengambil daftar mitra sponsor diurutkan dari tingkat tertinggi:
     * PLATINUM > GOLD > SILVER > PARTNER,
     * lengkap dengan deskripsi singkat dan seluruh produk milik sponsor tersebut.
     */
    public function getBrandPartners(): JsonResponse
    {
        $sponsors = Sponsor::with(['products' => function ($q) {
            $q->where('is_active', true);
        }])
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($s) {
                $tierRanks = [
                    'platinum' => 1,
                    'gold' => 2,
                    'silver' => 3,
                    'partner' => 4,
                ];

                return $tierRanks[strtolower($s->tier ?? 'partner')] ?? 5;
            })
            ->values()
            ->map(function ($s) {
                $tierUpper = strtoupper($s->tier ?? 'PARTNER');
                $logo = $s->logo_path;
                if ($logo && ! str_starts_with($logo, 'http') && ! str_starts_with($logo, 'assets/')) {
                    $logo = url('api/v1/storage/'.$logo);
                }

                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'tier' => $tierUpper,
                    'tier_label' => match ($tierUpper) {
                        'PLATINUM' => 'PLATINUM SPONSOR',
                        'GOLD' => 'GOLD SPONSOR',
                        'SILVER' => 'SILVER SPONSOR',
                        default => 'OFFICIAL PARTNER',
                    },
                    'description' => $s->description ?: 'Mitra resmi penyedia perlengkapan dan suku cadang smartphone terverifikasi.',
                    'logo' => $logo ?: 'assets/images/logo_braderparts.png',
                    'website_url' => $s->website_url ?: 'https://shopee.co.id',
                    'whatsapp' => $s->whatsapp,
                    'verified' => true,
                    'products' => $s->products->map(function ($p) use ($s) {
                        $image = $p->image_path;
                        if ($image && ! str_starts_with($image, 'http') && ! str_starts_with($image, 'assets/')) {
                            $image = url('api/v1/storage/'.$image);
                        }

                        return [
                            'id' => $p->id,
                            'name' => $p->name,
                            'description' => $p->description,
                            'price' => (float) $p->price,
                            'category' => $p->category ?? 'Sparepart',
                            'image' => $image ?: 'assets/images/product_1.png',
                            'link' => $p->shopee_url ?: ($p->tokopedia_url ?: ($s->website_url ?: 'https://shopee.co.id')),
                            'rating' => '4.9',
                            'sold' => '150+',
                            'sponsor_name' => $s->name,
                        ];
                    }),
                ];
            });

        return response()->json([
            'status' => 'success',
            'total_partners' => $sponsors->count(),
            'data' => $sponsors,
        ]);
    }

    /**
     * Poin 2.2 Banner Horizontal Dinamis (Shop & Separator Beranda):
     * Menampilkan kampanye horizontal sponsor baik di katalog Shop maupun di Beranda.
     */
    public function getShopHorizontalBanners(): JsonResponse
    {
        $campaigns = Campaign::with('sponsor')
            ->active()
            ->where('placement_type', 'shop_horizontal')
            ->orderByDesc('weight')
            ->orderByDesc('id')
            ->take(6)
            ->get()
            ->map(function ($c) {
                return $this->formatCampaignPayload($c);
            });

        return response()->json([
            'status' => 'success',
            'total_slides' => $campaigns->count(),
            'data' => $campaigns,
        ]);
    }

    /**
     * Card Slider Promosi Beranda (Grid Mosaic):
     * Menampilkan promo card slider vertikal/square di halaman beranda.
     */
    public function getCardSliders(): JsonResponse
    {
        $campaigns = Campaign::with('sponsor')
            ->active()
            ->where('placement_type', 'card_slider')
            ->orderByDesc('weight')
            ->orderByDesc('id')
            ->take(10)
            ->get()
            ->map(function ($c) {
                return $this->formatCampaignPayload($c);
            });

        return response()->json([
            'status' => 'success',
            'total_cards' => $campaigns->count(),
            'data' => $campaigns,
        ]);
    }

    /**
     * Pop Up Iklan Startup Dialog:
     * Menampilkan dialog modal multi-sponsor slider saat aplikasi dibuka,
     * difilter berdasarkan rentang waktu aktif (start_date & end_date).
     */
    public function getPopupBanners(): JsonResponse
    {
        $campaigns = Campaign::with('sponsor')
            ->active()
            ->where('placement_type', 'popup_modal')
            ->orderByDesc('weight')
            ->orderByDesc('id')
            ->take(8)
            ->get()
            ->map(function ($c) {
                return $this->formatCampaignPayload($c);
            });

        return response()->json([
            'status' => 'success',
            'has_popups' => $campaigns->isNotEmpty(),
            'total_popups' => $campaigns->count(),
            'data' => $campaigns,
        ]);
    }

    /**
     * Best Deal Catalog & Refaktorisasi Event Diskon Terpilih:
     * Mengambil produk dari program Best Deal aktif.
     * Hanya produk yang terpilih di Event Diskon aktif yang akan mendapatkan harga diskon!
     */
    public function getBestDeals(): JsonResponse
    {
        // 1. Ambil Event Diskon Aktif
        $activeEvent = DiscountEvent::active()->with('products')->latest()->first();
        $hasActiveEvent = $activeEvent !== null;
        $eventProductIds = $hasActiveEvent ? $activeEvent->products->pluck('id')->toArray() : [];

        // 2. Ambil Program Best Deal Aktif
        $activeBestDeal = BestDeal::active()->with('products.sponsor')->latest()->first();

        if ($activeBestDeal && $activeBestDeal->products->isNotEmpty()) {
            $rawProducts = $activeBestDeal->products;
            $programTitle = $activeBestDeal->title;
        } else {
            // Fallback jika belum ada program Best Deal spesifik
            $rawProducts = SponsorProduct::with('sponsor')
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('order')
                ->take(50)
                ->get();
            $programTitle = 'BEST DEALS VBAT';
        }

        $products = $rawProducts->map(function ($p) use ($hasActiveEvent, $activeEvent, $eventProductIds) {
            $basePrice = (float) $p->price;
            $isDiscountedByEvent = $hasActiveEvent && in_array($p->id, $eventProductIds);

            if ($isDiscountedByEvent) {
                if ($activeEvent->discount_type === 'percentage') {
                    $discountRate = (float) $activeEvent->discount_value;
                    $discountPrice = round($basePrice * (1 - ($discountRate / 100)));
                    $discountPercent = (int) round($discountRate);
                } else {
                    $discountPrice = max(0, $basePrice - (float) $activeEvent->discount_value);
                    $discountPercent = $basePrice > 0 ? (int) round((($basePrice - $discountPrice) / $basePrice) * 100) : 0;
                }
            } else {
                $discountPrice = $basePrice;
                $discountPercent = 0;
            }

            $category = 'Tools';
            if (str_contains($p->name, 'LCD')) {
                $category = 'LCD';
            } elseif (str_contains($p->name, 'Baterai')) {
                $category = 'Baterai';
            } elseif (str_contains($p->name, 'Konektor')) {
                $category = 'Konektor Charging';
            } elseif (str_contains($p->name, 'Lem') || str_contains($p->name, 'Kawat')) {
                $category = 'Aksesoris';
            }

            $image = $p->image_path;
            if ($image && ! str_starts_with($image, 'http') && ! str_starts_with($image, 'assets/')) {
                $image = url('api/v1/storage/'.$image);
            }

            $badgeText = isset($p->pivot) && isset($p->pivot->badge_text) ? $p->pivot->badge_text : 'BEST DEAL';
            if ($isDiscountedByEvent && $discountPercent > 0) {
                $badgeText = "DISKON {$discountPercent}%";
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $category,
                'description' => $p->description,
                'price' => $basePrice,
                'discount_price' => $discountPrice,
                'discount_percentage' => $discountPercent,
                'is_event_discount' => $isDiscountedByEvent,
                'badge' => $badgeText,
                'image' => $image,
                'shopee_url' => $p->shopee_url,
                'tokopedia_url' => $p->tokopedia_url,
                'rating' => '4.9',
                'sold' => '250+',
                'sponsor' => [
                    'id' => $p->sponsor?->id,
                    'name' => $p->sponsor?->name ?? 'Sponsor',
                    'tier' => $p->sponsor?->tier ?? 'PARTNER',
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'program_title' => $programTitle,
            'event_active' => $hasActiveEvent,
            'event_name' => $activeEvent?->name,
            'total_items' => $products->count(),
            'data' => $products,
        ]);
    }

    private function formatCampaignPayload(Campaign $c): array
    {
        $mediaPath = $c->media_path;
        if ($mediaPath && ! str_starts_with($mediaPath, 'http') && ! str_starts_with($mediaPath, 'assets/')) {
            $mediaPath = url('api/v1/storage/'.$mediaPath);
        }

        $sponsorName = $c->sponsor ? $c->sponsor->name : 'Sponsor';
        $sponsorTier = $c->sponsor ? strtoupper($c->sponsor->tier ?? 'PARTNER') : 'PARTNER';

        return [
            'id' => $c->id,
            'title' => $c->title,
            'placement_type' => $c->placement_type,
            'description' => $c->description,
            'media_path' => $mediaPath,
            'media_type' => $c->media_type,
            'thumbnail_url' => $c->thumbnail_url,
            'thumbnail_path' => $c->thumbnail_path,
            'target_url' => $c->target_url,
            'start_date' => $c->start_date?->toDateString(),
            'end_date' => $c->end_date?->toDateString(),
            'sponsor_name' => $sponsorName,
            'tier' => $sponsorTier,
            'sponsor' => [
                'id' => $c->sponsor?->id,
                'name' => $sponsorName,
                'tier' => $sponsorTier,
                'logo' => $c->sponsor?->logo_path,
                'whatsapp' => $c->sponsor?->whatsapp,
            ],
        ];
    }
}
