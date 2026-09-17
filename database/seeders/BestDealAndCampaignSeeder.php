<?php

namespace Database\Seeders;

use App\Models\BestDeal;
use App\Models\Campaign;
use App\Models\DiscountEvent;
use App\Models\SponsorProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BestDealAndCampaignSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan ada discount event aktif dan attach beberapa produk
        $event = DiscountEvent::firstOrCreate(
            ['name' => 'FLASH SALE SPESIAL VBAT'],
            [
                'discount_type' => 'percentage',
                'discount_value' => 15.00,
                'banner_text' => 'DISKON SPESIAL 15% PRODUK PILIHAN!',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDays(7),
                'is_active' => true,
            ]
        );

        $products = SponsorProduct::take(4)->get();
        foreach ($products as $idx => $prod) {
            DB::table('discount_event_products')->updateOrInsert(
                [
                    'discount_event_id' => $event->id,
                    'sponsor_product_id' => $prod->id,
                ],
                [
                    'custom_discount_type' => null,
                    'custom_discount_value' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Buat Best Deal aktif dan attach produk dengan order & badge
        $bestDeal = BestDeal::firstOrCreate(
            ['title' => 'SUPER BEST DEALS 2026'],
            [
                'description' => 'Katalog sparepart dan perkakas terbaik pilihan teknisi handal dengan harga kompetitif.',
                'banner_path' => 'assets/images/banner_promo_diskon.png',
                'start_at' => now()->subDay(),
                'end_at' => now()->addMonths(1),
                'is_active' => true,
            ]
        );

        $bestDealBadges = ['HOT DEAL', 'TOP SELLER', 'HEMAT 25%', 'RECOMMENDED'];
        foreach ($products as $idx => $prod) {
            DB::table('best_deal_products')->updateOrInsert(
                [
                    'best_deal_id' => $bestDeal->id,
                    'sponsor_product_id' => $prod->id,
                ],
                [
                    'badge_text' => $bestDealBadges[$idx % count($bestDealBadges)],
                    'order' => $idx + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Tambahkan sampel Card Slider & Pop-Up Iklan
        $sponsorId = DB::table('sponsors')->value('id') ?? 1;

        // Card Slider di Beranda
        Campaign::firstOrCreate(
            ['title' => 'LCD Premium Braderparts Oled Quality', 'placement_type' => 'card_slider'],
            [
                'sponsor_id' => $sponsorId,
                'media_path' => 'assets/images/PHOTO-2026-07-22-20-20-24.jpg',
                'media_type' => 'image',
                'target_url' => 'https://shopee.co.id',
                'description' => 'Display card promosi di mosaic beranda.',
                'daily_limit' => 5000,
                'weight' => 5,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonths(2),
                'status' => 'approved',
            ]
        );

        // Pop Up Iklan Multi-Sponsor
        Campaign::firstOrCreate(
            ['title' => 'Mega Promo Diskon Sparepart 2026', 'placement_type' => 'popup_modal'],
            [
                'sponsor_id' => $sponsorId,
                'media_path' => 'assets/images/PHOTO-2026-07-22-20-21-55.jpg',
                'media_type' => 'image',
                'target_url' => 'https://shopee.co.id/braderparts',
                'description' => 'Pop up dialog selamat datang saat aplikasi dibuka.',
                'daily_limit' => 10000,
                'weight' => 5,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonths(1),
                'status' => 'approved',
            ]
        );
    }
}
