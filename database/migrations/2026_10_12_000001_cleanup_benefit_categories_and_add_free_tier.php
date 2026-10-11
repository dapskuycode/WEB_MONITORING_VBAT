<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BEN-01, BEN-02, TIER-02, WM-01.
 *
 * 1. Menghapus kategori benefit lama yang duplikat dan yang tidak dipakai:
 *    katalog_produk (duplikat kuota_produk), dedicated_storefront (duplikat
 *    storefront_khusus), best_deal_priority (duplikat best_deal_prioritas),
 *    horizontal_infeed (duplikat infeed_banner), co_branding (fitur tidak
 *    dipakai), partner_support (tidak jelas maksudnya).
 *
 *    Sebelum dihapus, nilai pada kategori baru disamakan lebih dulu bila masih
 *    kosong, supaya tidak ada nilai yang hilang.
 *
 * 2. Menambahkan Tier "Free" sebagai tier paling bawah. Tier ini tidak
 *    memiliki benefit apa pun kecuali kuota produk 1 dan outbound marketplace.
 *
 * 3. Menambahkan kolom marketplace_url dan header_image_url pada sponsors,
 *    supaya Website dan Marketplace dapat diisi terpisah.
 */
return new class extends Migration
{
    /** Kategori lama yang dihapus (kiri) dan pasangan barunya (kanan). */
    private array $duplicatePairs = [
        'katalog_produk' => 'kuota_produk',
        'dedicated_storefront' => 'storefront_khusus',
        'best_deal_priority' => 'best_deal_prioritas',
        'horizontal_infeed' => 'infeed_banner',
    ];

    /** Kategori yang dihapus tanpa pengganti. */
    private array $removedOnly = [
        'co_branding',
        'partner_support',
    ];

    public function up(): void
    {
        // ---------- 1. Kolom baru pada sponsors (WM-01, WR-02) ----------
        Schema::table('sponsors', function (Blueprint $table) {
            if (! Schema::hasColumn('sponsors', 'marketplace_url')) {
                $table->string('marketplace_url')->nullable()->after('website_url');
            }
            if (! Schema::hasColumn('sponsors', 'header_image_url')) {
                $table->string('header_image_url')->nullable()->after('logo_path');
            }
        });

        // ---------- 2. Pindahkan nilai duplikat sebelum dihapus ----------
        foreach ($this->duplicatePairs as $oldSlug => $newSlug) {
            $oldId = DB::table('benefit_categories')->where('slug', $oldSlug)->value('id');
            $newId = DB::table('benefit_categories')->where('slug', $newSlug)->value('id');

            if ($oldId && $newId) {
                $rows = DB::table('tier_benefits')->where('benefit_category_id', $oldId)->get();
                foreach ($rows as $row) {
                    $exists = DB::table('tier_benefits')
                        ->where('tier_id', $row->tier_id)
                        ->where('benefit_category_id', $newId)
                        ->first();

                    if (! $exists) {
                        DB::table('tier_benefits')->insert([
                            'tier_id' => $row->tier_id,
                            'benefit_category_id' => $newId,
                            'value' => $row->value,
                            'label' => $row->label,
                            'is_default_awal' => $row->is_default_awal,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        // ---------- 3. Hapus kategori duplikat dan yang tidak dipakai ----------
        $toDelete = array_merge(
            array_keys($this->duplicatePairs),
            $this->removedOnly,
        );

        foreach ($toDelete as $slug) {
            $id = DB::table('benefit_categories')->where('slug', $slug)->value('id');
            if ($id) {
                DB::table('tier_benefits')->where('benefit_category_id', $id)->delete();
                DB::table('sponsor_benefit_overrides')->where('benefit_category_id', $id)->delete();
                DB::table('benefit_categories')->where('id', $id)->delete();
            }
        }

        // ---------- 4. Tier Free sebagai tier paling bawah (TIER-02) ----------
        $freeExists = DB::table('sponsor_tiers')->where('slug', 'free')->exists();

        if (! $freeExists) {
            $freeId = DB::table('sponsor_tiers')->insertGetId([
                'slug' => 'free',
                'name' => 'Free',
                'badge_label' => '',
                'badge_color' => '#64748B',
                'icon_url' => null,
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Free Tier: hanya kuota produk 1 dan outbound marketplace.
            // Tier ini tidak mendapat badge (badge adalah hak tier berbayar).
            $kuotaId = DB::table('benefit_categories')->where('slug', 'kuota_produk')->value('id');
            $outboundId = DB::table('benefit_categories')->where('slug', 'outbound_marketplace')->value('id');
            $disabledSlugs = [
                'storefront_khusus', 'best_deal_slot', 'best_deal_durasi',
                'best_deal_prioritas', 'hero_slider', 'hero_posisi',
                'share_of_voice', 'popup_cta', 'infeed_banner', 'infeed_jarak',
                'push_broadcast', 'badge',
            ];

            if ($kuotaId) {
                DB::table('tier_benefits')->insert([
                    'tier_id' => $freeId,
                    'benefit_category_id' => $kuotaId,
                    'value' => json_encode(1),
                    'label' => null,
                    'is_default_awal' => true,
                    'initial_value' => json_encode(1),
                    'is_unlimited' => false,
                    'is_disabled' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($outboundId) {
                DB::table('tier_benefits')->insert([
                    'tier_id' => $freeId,
                    'benefit_category_id' => $outboundId,
                    'value' => json_encode(true),
                    'label' => null,
                    'is_default_awal' => true,
                    'initial_value' => json_encode(true),
                    'is_unlimited' => false,
                    'is_disabled' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($disabledSlugs as $slug) {
                $catId = DB::table('benefit_categories')->where('slug', $slug)->value('id');
                if (! $catId) {
                    continue;
                }
                DB::table('tier_benefits')->insert([
                    'tier_id' => $freeId,
                    'benefit_category_id' => $catId,
                    'value' => null,
                    'label' => null,
                    'is_default_awal' => true,
                    'initial_value' => null,
                    'is_unlimited' => false,
                    'is_disabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Hapus tier Free beserta benefit-nya.
        $freeId = DB::table('sponsor_tiers')->where('slug', 'free')->value('id');
        if ($freeId) {
            DB::table('tier_benefits')->where('tier_id', $freeId)->delete();
            DB::table('sponsor_tiers')->where('id', $freeId)->delete();
        }

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn(['marketplace_url', 'header_image_url']);
        });
    }
};
