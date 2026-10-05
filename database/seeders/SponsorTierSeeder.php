<?php

namespace Database\Seeders;

use App\Models\BenefitCategory;
use App\Models\Sponsor;
use App\Models\SponsorTier;
use App\Models\TierBenefit;
use Illuminate\Database\Seeder;

class SponsorTierSeeder extends Seeder
{
    /**
     * Seed the 6 sponsor tiers and their 14 default benefits per VBAT3 specification.
     */
    public function run(): void
    {
        // 1. Create 6 Tiers
        $tiers = [
            ['slug' => 'kontribusi', 'name' => 'Kontribusi', 'badge_label' => 'Kontributor', 'sort_order' => 10],
            ['slug' => 'bronze', 'name' => 'Bronze', 'badge_label' => 'Bronze Partner', 'sort_order' => 20],
            ['slug' => 'silver', 'name' => 'Silver', 'badge_label' => 'Silver Official', 'sort_order' => 30],
            ['slug' => 'gold', 'name' => 'Gold', 'badge_label' => 'Gold Brand', 'sort_order' => 40],
            ['slug' => 'platinum', 'name' => 'Platinum', 'badge_label' => 'Platinum Premier', 'sort_order' => 50],
            ['slug' => 'diamond', 'name' => 'Diamond', 'badge_label' => 'Diamond Title Partner', 'sort_order' => 60],
        ];

        $tierModels = [];
        foreach ($tiers as $t) {
            $tierModels[$t['slug']] = SponsorTier::updateOrCreate(
                ['slug' => $t['slug']],
                $t
            );
        }

        // 2. Create 14 Benefit Categories
        $categories = [
            [
                'slug' => 'kuota_produk',
                'name' => 'Kuota Produk',
                'description' => 'Jumlah produk yang dapat diunggah per bulan kalender',
                'value_type' => 'integer',
                'sort_order' => 10,
            ],
            [
                'slug' => 'outbound_marketplace',
                'name' => 'Outbound Marketplace',
                'description' => 'Tautan langsung ke toko Shopee/Tokopedia pada produk',
                'value_type' => 'boolean',
                'sort_order' => 20,
            ],
            [
                'slug' => 'storefront_khusus',
                'name' => 'Storefront Khusus',
                'description' => 'Halaman etalase toko khusus brand sponsor',
                'value_type' => 'boolean',
                'sort_order' => 30,
            ],
            [
                'slug' => 'best_deal_slot',
                'name' => 'Best Deal Slot',
                'description' => 'Jumlah kuota slot produk Best Deal',
                'value_type' => 'integer',
                'sort_order' => 40,
            ],
            [
                'slug' => 'best_deal_durasi',
                'name' => 'Durasi Best Deal (hari)',
                'description' => 'Durasi penayangan produk Best Deal dalam hari',
                'value_type' => 'integer',
                'sort_order' => 50,
            ],
            [
                'slug' => 'best_deal_prioritas',
                'name' => 'Prioritas Best Deal',
                'description' => 'Urutan posisi penayangan Best Deal',
                'value_type' => 'string',
                'sort_order' => 60,
            ],
            [
                'slug' => 'hero_slider',
                'name' => 'Hero Slider (slot)',
                'description' => 'Jumlah slot banner hero slider teratas di Beranda',
                'value_type' => 'integer',
                'sort_order' => 70,
            ],
            [
                'slug' => 'hero_posisi',
                'name' => 'Posisi Hero',
                'description' => 'Alokasi posisi slide banner pada hero slider',
                'value_type' => 'string',
                'sort_order' => 80,
            ],
            [
                'slug' => 'share_of_voice',
                'name' => 'Share of Voice (%)',
                'description' => 'Bobot probabilitas frekuensi kemunculan banner sponsor',
                'value_type' => 'integer',
                'sort_order' => 90,
            ],
            [
                'slug' => 'popup_cta',
                'name' => 'Popup + CTA',
                'description' => 'Hak penayangan pop-up dialog selamat datang',
                'value_type' => 'boolean',
                'sort_order' => 100,
            ],
            [
                'slug' => 'infeed_banner',
                'name' => 'In-Feed Banner (slot)',
                'description' => 'Jumlah slot banner sponsor di sela konten feed',
                'value_type' => 'integer',
                'sort_order' => 110,
            ],
            [
                'slug' => 'infeed_jarak',
                'name' => 'Jarak In-Feed (produk)',
                'description' => 'Interval kemunculan banner tiap N item produk',
                'value_type' => 'integer',
                'sort_order' => 120,
            ],
            [
                'slug' => 'push_broadcast',
                'name' => 'Push Broadcast/bulan',
                'description' => 'Jumlah push notification broadcast per bulan',
                'value_type' => 'integer',
                'sort_order' => 130,
            ],
            [
                'slug' => 'badge',
                'name' => 'Badge',
                'description' => 'Label lencana resmi sponsor',
                'value_type' => 'string',
                'sort_order' => 140,
            ],
        ];

        $catModels = [];
        foreach ($categories as $c) {
            $catModels[$c['slug']] = BenefitCategory::updateOrCreate(
                ['slug' => $c['slug']],
                $c
            );
        }

        // 3. Define 6 Tier x 14 Benefit Baseline Matrix
        // Format: [tier_slug, benefit_slug, value, is_unlimited, is_disabled, label]
        $matrix = [
            // Kuota Produk (integer, allow ∞)
            ['kontribusi', 'kuota_produk', 5, false, false, null],
            ['bronze', 'kuota_produk', 15, false, false, null],
            ['silver', 'kuota_produk', 35, false, false, null],
            ['gold', 'kuota_produk', 75, false, false, null],
            ['platinum', 'kuota_produk', 150, false, false, null],
            ['diamond', 'kuota_produk', null, true, false, 'unlimited'],

            // Outbound Marketplace (boolean)
            ['kontribusi', 'outbound_marketplace', true, false, false, null],
            ['bronze', 'outbound_marketplace', true, false, false, null],
            ['silver', 'outbound_marketplace', true, false, false, null],
            ['gold', 'outbound_marketplace', true, false, false, null],
            ['platinum', 'outbound_marketplace', true, false, false, null],
            ['diamond', 'outbound_marketplace', true, false, false, null],

            // Storefront Khusus (boolean)
            ['kontribusi', 'storefront_khusus', false, false, false, null],
            ['bronze', 'storefront_khusus', false, false, false, null],
            ['silver', 'storefront_khusus', true, false, false, null],
            ['gold', 'storefront_khusus', true, false, false, null],
            ['platinum', 'storefront_khusus', true, false, false, null],
            ['diamond', 'storefront_khusus', true, false, false, null],

            // Best Deal Slot (integer, allow ∞)
            ['kontribusi', 'best_deal_slot', 0, false, false, null],
            ['bronze', 'best_deal_slot', 1, false, false, null],
            ['silver', 'best_deal_slot', 3, false, false, null],
            ['gold', 'best_deal_slot', 8, false, false, null],
            ['platinum', 'best_deal_slot', 15, false, false, null],
            ['diamond', 'best_deal_slot', null, true, false, 'unlimited'],

            // Durasi Best Deal (hari)
            ['kontribusi', 'best_deal_durasi', null, false, true, '—'],
            ['bronze', 'best_deal_durasi', 3, false, false, null],
            ['silver', 'best_deal_durasi', 7, false, false, null],
            ['gold', 'best_deal_durasi', 14, false, false, null],
            ['platinum', 'best_deal_durasi', 21, false, false, null],
            ['diamond', 'best_deal_durasi', 30, false, false, null],

            // Prioritas Best Deal
            ['kontribusi', 'best_deal_prioritas', null, false, true, '—'],
            ['bronze', 'best_deal_prioritas', 'Standar', false, false, null],
            ['silver', 'best_deal_prioritas', 'Standar', false, false, null],
            ['gold', 'best_deal_prioritas', 'Top 5', false, false, null],
            ['platinum', 'best_deal_prioritas', 'Top 3', false, false, null],
            ['diamond', 'best_deal_prioritas', 'Slot #1', false, false, null],

            // Hero Slider (slot)
            ['kontribusi', 'hero_slider', null, false, true, '—'],
            ['bronze', 'hero_slider', null, false, true, '—'],
            ['silver', 'hero_slider', 1, false, false, null],
            ['gold', 'hero_slider', 1, false, false, null],
            ['platinum', 'hero_slider', 2, false, false, null],
            ['diamond', 'hero_slider', 1, false, false, null],

            // Posisi Hero
            ['kontribusi', 'hero_posisi', null, false, true, '—'],
            ['bronze', 'hero_posisi', null, false, true, '—'],
            ['silver', 'hero_posisi', 'Slide 5-6', false, false, null],
            ['gold', 'hero_posisi', 'Slide 3-4', false, false, null],
            ['platinum', 'hero_posisi', 'Slide 1-2', false, false, null],
            ['diamond', 'hero_posisi', 'Slide #1', false, false, null],

            // Share of Voice (%)
            ['kontribusi', 'share_of_voice', 0, false, false, null],
            ['bronze', 'share_of_voice', 0, false, false, null],
            ['silver', 'share_of_voice', 20, false, false, null],
            ['gold', 'share_of_voice', 35, false, false, null],
            ['platinum', 'share_of_voice', 50, false, false, null],
            ['diamond', 'share_of_voice', 100, false, false, null],

            // Popup + CTA (boolean)
            ['kontribusi', 'popup_cta', false, false, false, null],
            ['bronze', 'popup_cta', false, false, false, null],
            ['silver', 'popup_cta', true, false, false, null],
            ['gold', 'popup_cta', true, false, false, null],
            ['platinum', 'popup_cta', true, false, false, null],
            ['diamond', 'popup_cta', true, false, false, null],

            // In-Feed Banner (slot)
            ['kontribusi', 'infeed_banner', null, false, true, '—'],
            ['bronze', 'infeed_banner', 1, false, false, null],
            ['silver', 'infeed_banner', 2, false, false, null],
            ['gold', 'infeed_banner', 3, false, false, null],
            ['platinum', 'infeed_banner', 4, false, false, null],
            ['diamond', 'infeed_banner', 5, false, false, null],

            // Jarak In-Feed (produk)
            ['kontribusi', 'infeed_jarak', null, false, true, '—'],
            ['bronze', 'infeed_jarak', 24, false, false, null],
            ['silver', 'infeed_jarak', 12, false, false, null],
            ['gold', 'infeed_jarak', 8, false, false, null],
            ['platinum', 'infeed_jarak', 6, false, false, null],
            ['diamond', 'infeed_jarak', 4, false, false, null],

            // Push Broadcast/bulan (integer, allow ∞)
            ['kontribusi', 'push_broadcast', 0, false, false, null],
            ['bronze', 'push_broadcast', 0, false, false, null],
            ['silver', 'push_broadcast', 0, false, false, null],
            ['gold', 'push_broadcast', 1, false, false, null],
            ['platinum', 'push_broadcast', 3, false, false, null],
            ['diamond', 'push_broadcast', null, true, false, 'unlimited'],

            // Badge
            ['kontribusi', 'badge', 'Kontributor', false, false, null],
            ['bronze', 'badge', 'Bronze Partner', false, false, null],
            ['silver', 'badge', 'Silver Official', false, false, null],
            ['gold', 'badge', 'Gold Brand', false, false, null],
            ['platinum', 'badge', 'Platinum Premier', false, false, null],
            ['diamond', 'badge', 'Diamond Title Partner', false, false, null],
        ];

        foreach ($matrix as [$tierSlug, $catSlug, $val, $unlimited, $disabled, $lbl]) {
            $tierId = $tierModels[$tierSlug]->id;
            $catId = $catModels[$catSlug]->id;

            TierBenefit::updateOrCreate(
                [
                    'tier_id' => $tierId,
                    'benefit_category_id' => $catId,
                ],
                [
                    'value' => $val,
                    'initial_value' => $val,
                    'is_unlimited' => $unlimited,
                    'is_disabled' => $disabled,
                    'label' => $lbl,
                    'is_default_awal' => true,
                ]
            );
        }

        // 4. Link Existing Sponsors to their SponsorTier by tier name
        $slugMap = [
            'platinum' => 'platinum',
            'gold' => 'gold',
            'silver' => 'silver',
            'bronze' => 'bronze',
            'kontribusi' => 'kontribusi',
            'partner' => 'kontribusi',
            'diamond' => 'diamond',
        ];

        foreach (Sponsor::all() as $sponsor) {
            $normalized = strtolower($sponsor->tier ?? 'kontribusi');
            $targetSlug = $slugMap[$normalized] ?? 'kontribusi';
            if (isset($tierModels[$targetSlug])) {
                $sponsor->update([
                    'tier_id' => $tierModels[$targetSlug]->id,
                ]);
            }
        }
    }
}
