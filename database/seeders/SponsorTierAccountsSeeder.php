<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SponsorTierAccountsSeeder extends Seeder
{
    /**
     * Seed 6 dedicated testing sponsor accounts (1 for each tier).
     * Password for all test sponsor accounts: sponsor123
     */
    public function run(): void
    {
        $tiersConfig = [
            [
                'tier_id' => 6,
                'tier_slug' => 'diamond',
                'sponsor_name' => 'Diamond Tech Indonesia',
                'sponsor_slug' => 'diamond-tech',
                'user_name' => 'Mitra Diamond Tech',
                'user_email' => 'sponsor@diamond.com',
                'desc' => 'Official Flagship Diamond Partner penyedia mesin pemisah LCD laser & alat servis canggih.',
            ],
            [
                'tier_id' => 5,
                'tier_slug' => 'platinum',
                'sponsor_name' => 'BraderParts Indonesia',
                'sponsor_slug' => 'braderparts',
                'user_name' => 'Mitra BraderParts',
                'user_email' => 'sponsor@braderparts.com',
                'desc' => 'Official Distributor suku cadang LCD OLED/Incell, fleksibel, baterai, dan komponen smartphone original.',
            ],
            [
                'tier_id' => 4,
                'tier_slug' => 'gold',
                'sponsor_name' => 'TITAN Tools Official',
                'sponsor_slug' => 'titan-tools',
                'user_name' => 'Mitra TITAN Tools',
                'user_email' => 'sponsor@titantools.com',
                'desc' => 'Spesialis peralatan teknisi presisi tinggi: solder cerdas T12, blower hot air gun Quick, dan mikroskop stereo.',
            ],
            [
                'tier_id' => 3,
                'tier_slug' => 'silver',
                'sponsor_name' => 'Sunshine Tools',
                'sponsor_slug' => 'sunshine',
                'user_name' => 'Mitra Sunshine Tools',
                'user_email' => 'sponsor@sunshine.com',
                'desc' => 'Distributor obeng presisi, tang potong, cairan pembersih PCB, dan consumable teknisi terpercaya.',
            ],
            [
                'tier_id' => 2,
                'tier_slug' => 'bronze',
                'sponsor_name' => 'Bronze Solder Works',
                'sponsor_slug' => 'bronze-solder',
                'user_name' => 'Mitra Bronze Works',
                'user_email' => 'sponsor@bronze.com',
                'desc' => 'Mitra spesialis timah solder roll, kawat jumper enamel, dan wick penyerap timah kualitas prima.',
            ],
            [
                'tier_id' => 1,
                'tier_slug' => 'kontribusi',
                'sponsor_name' => 'Pragmafix Schematics',
                'sponsor_slug' => 'pragmafix',
                'user_name' => 'Mitra Pragmafix',
                'user_email' => 'sponsor@pragmafix.com',
                'desc' => 'Mitra skematik & software diagnosis jalur hardware smartphone komprehensif.',
            ],
        ];

        foreach ($tiersConfig as $cfg) {
            // 1. Buat / update User login
            $user = User::updateOrCreate(
                ['email' => $cfg['user_email']],
                [
                    'name' => $cfg['user_name'],
                    'password' => Hash::make('sponsor123'),
                    'role' => 'sponsor',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'phone' => '0812' . rand(10000000, 99999999),
                    'profile_completed' => true,
                ]
            );

            // 2. Buat / update Sponsor profil
            $sponsor = Sponsor::updateOrCreate(
                ['slug' => $cfg['sponsor_slug']],
                [
                    'name' => $cfg['sponsor_name'],
                    'user_id' => $user->id,
                    'tier' => $cfg['tier_slug'],
                    'tier_id' => $cfg['tier_id'],
                    'description' => $cfg['desc'],
                    'contact_email' => $cfg['user_email'],
                    'phone' => $user->phone,
                    'whatsapp' => '628123456789',
                    'is_active' => true,
                    'website_url' => 'https://shopee.co.id/' . $cfg['sponsor_slug'],
                ]
            );

            // Pastikan sponsor memiliki minimal 2 produk untuk bahan testing fitur produk & Best Deal
            if ($sponsor->products()->count() < 2) {
                SponsorProduct::create([
                    'sponsor_id' => $sponsor->id,
                    'name' => 'Produk Unggulan ' . $cfg['sponsor_name'] . ' #1',
                    'slug' => $cfg['sponsor_slug'] . '-item-1-' . time(),
                    'description' => 'Produk sampel kualitas standar industri servis ponsel profesional.',
                    'price' => 150000,
                    'discount_price' => 125000,
                    'image_path' => 'assets/images/sample_product.png',
                    'shopee_url' => 'https://shopee.co.id/' . $cfg['sponsor_slug'],
                    'tokopedia_url' => 'https://tokopedia.com/' . $cfg['sponsor_slug'],
                    'is_active' => true,
                ]);

                SponsorProduct::create([
                    'sponsor_id' => $sponsor->id,
                    'name' => 'Aksesoris Tambahan ' . $cfg['sponsor_name'] . ' #2',
                    'slug' => $cfg['sponsor_slug'] . '-item-2-' . time(),
                    'description' => 'Perlengkapan komplementer untuk melengkapi meja servis teknisi.',
                    'price' => 75000,
                    'discount_price' => 60000,
                    'image_path' => 'assets/images/sample_product.png',
                    'shopee_url' => 'https://shopee.co.id/' . $cfg['sponsor_slug'],
                    'tokopedia_url' => 'https://tokopedia.com/' . $cfg['sponsor_slug'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
