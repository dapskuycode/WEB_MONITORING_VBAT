<?php

namespace Database\Seeders;

use App\Models\BenefitCategory;
use App\Models\SponsorTier;
use App\Models\TierBenefit;
use Illuminate\Database\Seeder;

class SponsorTierSeeder extends Seeder
{
    /**
     * Seed the 6 sponsor tiers and their default benefits.
     *
     * Phase 1.2 — Tier & Benefit Seeding
     */
    public function run(): void
    {
        // Step 1: Create 6 tiers
        $tiers = [
            ['slug' => 'kontribusi', 'name' => 'Kontribusi', 'badge_label' => 'Kontributor', 'sort_order' => 10],
            ['slug' => 'bronze', 'name' => 'Bronze', 'badge_label' => 'Bronze Partner', 'sort_order' => 20],
            ['slug' => 'silver', 'name' => 'Silver', 'badge_label' => 'Silver Official', 'sort_order' => 30],
            ['slug' => 'gold', 'name' => 'Gold', 'badge_label' => 'Gold Brand', 'sort_order' => 40],
            ['slug' => 'platinum', 'name' => 'Platinum', 'badge_label' => 'Platinum Premier', 'sort_order' => 50],
            ['slug' => 'diamond', 'name' => 'Diamond', 'badge_label' => 'Diamond Title', 'sort_order' => 60],
        ];

        $tierModels = [];
        foreach ($tiers as $tierData) {
            $tierModels[$tierData['slug']] = SponsorTier::firstOrCreate(
                ['slug' => $tierData['slug']],
                $tierData
            );
        }

        // Step 2: Create benefit categories
        $categories = [
            ['slug' => 'katalog_produk', 'name' => 'Katalog Produk', 'description' => 'Jumlah produk yang dapat diunggah per bulan', 'value_type' => 'integer'],
            ['slug' => 'outbound_marketplace', 'name' => 'Outbound Marketplace', 'description' => 'Link marketplace (Shopee/Tokopedia) pada produk', 'value_type' => 'boolean'],
            ['slug' => 'dedicated_storefront', 'name' => 'Dedicated Storefront', 'description' => 'Halaman toko khusus untuk sponsor', 'value_type' => 'boolean'],
            ['slug' => 'best_deal_slot', 'name' => 'Best Deal Slot', 'description' => 'Jumlah slot Best Deal per bulan (jumlah, durasi hari)', 'value_type' => 'json'],
            ['slug' => 'best_deal_priority', 'name' => 'Best Deal Priority', 'description' => 'Prioritas tampilan Best Deal', 'value_type' => 'string'],
            ['slug' => 'hero_slider', 'name' => 'Hero Slider', 'description' => 'Slot banner hero slider', 'value_type' => 'json'],
            ['slug' => 'popup_cta', 'name' => 'Popup + CTA', 'description' => 'Popup sponsor dengan call-to-action', 'value_type' => 'boolean'],
            ['slug' => 'horizontal_infeed', 'name' => 'Horizontal In-Feed', 'description' => 'Banner horizontal dalam feed', 'value_type' => 'json'],
            ['slug' => 'push_broadcast', 'name' => 'Push Broadcast', 'description' => 'Jumlah push notification broadcast per bulan', 'value_type' => 'integer'],
            ['slug' => 'co_branding', 'name' => 'Co-branding', 'description' => 'Opsi co-branding dengan VBAT', 'value_type' => 'boolean'],
            ['slug' => 'partner_support', 'name' => 'Partner Support', 'description' => 'Level dukungan partner', 'value_type' => 'string'],
        ];

        $categoryModels = [];
        foreach ($categories as $catData) {
            $categoryModels[$catData['slug']] = BenefitCategory::firstOrCreate(
                ['slug' => $catData['slug']],
                $catData
            );
        }

        // Step 3: Create tier_benefits (default values per matrix in DEVELOPMENT_PLANNING.md)
        $benefitMatrix = [
            // [tier_slug, benefit_slug, value, label]
            ['kontribusi', 'katalog_produk', 5, null],
            ['bronze', 'katalog_produk', 15, null],
            ['silver', 'katalog_produk', 35, null],
            ['gold', 'katalog_produk', 75, null],
            ['platinum', 'katalog_produk', 150, null],
            ['diamond', 'katalog_produk', null, 'unlimited'],

            ['kontribusi', 'outbound_marketplace', true, null],
            ['bronze', 'outbound_marketplace', true, null],
            ['silver', 'outbound_marketplace', true, null],
            ['gold', 'outbound_marketplace', true, null],
            ['platinum', 'outbound_marketplace', true, null],
            ['diamond', 'outbound_marketplace', true, null],

            ['kontribusi', 'dedicated_storefront', false, null],
            ['bronze', 'dedicated_storefront', false, null],
            ['silver', 'dedicated_storefront', true, null],
            ['gold', 'dedicated_storefront', true, null],
            ['platinum', 'dedicated_storefront', true, null],
            ['diamond', 'dedicated_storefront', true, null],

            // Best Deal Slot: [count, duration_days]
            ['kontribusi', 'best_deal_slot', ['count' => 0, 'duration_days' => 0], null],
            ['bronze', 'best_deal_slot', ['count' => 1, 'duration_days' => 3], null],
            ['silver', 'best_deal_slot', ['count' => 3, 'duration_days' => 7], null],
            ['gold', 'best_deal_slot', ['count' => 8, 'duration_days' => 14], null],
            ['platinum', 'best_deal_slot', ['count' => 15, 'duration_days' => 21], null],
            ['diamond', 'best_deal_slot', ['count' => null, 'duration_days' => null], 'unlimited'],

            ['kontribusi', 'best_deal_priority', null, '—'],
            ['bronze', 'best_deal_priority', null, '—'],
            ['silver', 'best_deal_priority', null, '—'],
            ['gold', 'best_deal_priority', null, 'Top 5'],
            ['platinum', 'best_deal_priority', null, 'Top 3'],
            ['diamond', 'best_deal_priority', null, '#1 & #2'],

            // Hero Slider: [slot_count, position_range, estimated_share_pct]
            ['kontribusi', 'hero_slider', ['slot_count' => 0], null],
            ['bronze', 'hero_slider', ['slot_count' => 0], null],
            ['silver', 'hero_slider', ['slot_count' => 1, 'position_range' => '5-6', 'estimated_share_pct' => 20], null],
            ['gold', 'hero_slider', ['slot_count' => 1, 'position_range' => '3-4', 'estimated_share_pct' => 35], null],
            ['platinum', 'hero_slider', ['slot_count' => 2, 'position_range' => '1-2', 'estimated_share_pct' => 50], null],
            ['diamond', 'hero_slider', ['slot_count' => 1, 'position_range' => '#1', 'estimated_share_pct' => 100, 'is_title_slide' => true], 'Slide #1 Title'],

            ['kontribusi', 'popup_cta', false, null],
            ['bronze', 'popup_cta', false, null],
            ['silver', 'popup_cta', true, null],
            ['gold', 'popup_cta', true, null],
            ['platinum', 'popup_cta', true, null],
            ['diamond', 'popup_cta', true, null],

            // Horizontal In-Feed: [interval_products, section_count]
            ['kontribusi', 'horizontal_infeed', ['interval_products' => 0, 'section_count' => 0], null],
            ['bronze', 'horizontal_infeed', ['interval_products' => 24, 'section_count' => 1], null],
            ['silver', 'horizontal_infeed', ['interval_products' => 12, 'section_count' => 2], null],
            ['gold', 'horizontal_infeed', ['interval_products' => 8, 'section_count' => 3], 'Multi-section'],
            ['platinum', 'horizontal_infeed', ['interval_products' => 6, 'section_count' => 4], 'Multi-touchpoint'],
            ['diamond', 'horizontal_infeed', ['interval_products' => 4, 'section_count' => 5], 'Eksklusif'],

            ['kontribusi', 'push_broadcast', 0, null],
            ['bronze', 'push_broadcast', 0, null],
            ['silver', 'push_broadcast', 0, null],
            ['gold', 'push_broadcast', 1, null],
            ['platinum', 'push_broadcast', 3, null],
            ['diamond', 'push_broadcast', null, 'Fleksibel'],

            ['kontribusi', 'co_branding', false, null],
            ['bronze', 'co_branding', false, null],
            ['silver', 'co_branding', false, null],
            ['gold', 'co_branding', false, null],
            ['platinum', 'co_branding', false, null],
            ['diamond', 'co_branding', true, 'Opsi (Admin)'],

            ['kontribusi', 'partner_support', false, null],
            ['bronze', 'partner_support', false, null],
            ['silver', 'partner_support', false, null],
            ['gold', 'partner_support', false, null],
            ['platinum', 'partner_support', true, 'Opsi (Admin)'],
            ['diamond', 'partner_support', true, 'VIP Portal'],
        ];

        foreach ($benefitMatrix as [$tierSlug, $benefitSlug, $value, $label]) {
            $tierId = $tierModels[$tierSlug]->id;
            $categoryId = $categoryModels[$benefitSlug]->id;

            TierBenefit::firstOrCreate(
                ['tier_id' => $tierId, 'benefit_category_id' => $categoryId],
                [
                    'value' => $value,
                    'label' => $label,
                    'is_default_awal' => true,
                ]
            );
        }
    }
}
