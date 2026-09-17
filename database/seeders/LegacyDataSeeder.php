<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class LegacyDataSeeder extends Seeder
{
    public function run(): void
    {
        $sqlPath = base_path('../hasil_backup.sql');
        if (!file_exists($sqlPath)) {
            $this->command->warn("Backup file not found at: {$sqlPath}");
            return;
        }

        $this->command->info("Parsing and importing data from backup SQL...");

        // 1. Seed Provinces & Cities
        $this->seedProvincesAndCities($sqlPath);

        // 2. Create Default Users (Super Admin, Owner, Sponsor)
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@vbatponsel.com'],
            [
                'name' => 'Super Admin VBAT',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'birth_date' => '1995-05-12',
                'gender' => 'male',
                'phone' => '081234567890',
                'profile_completed' => true,
            ]
        );

        $owner = User::updateOrCreate(
            ['email' => 'owner@vbatponsel.com'],
            [
                'name' => 'Owner VBAT',
                'password' => Hash::make('owner123'),
                'role' => 'owner',
                'birth_date' => '1990-08-20',
                'gender' => 'male',
                'phone' => '081234567891',
                'profile_completed' => true,
            ]
        );

        $sponsorAccounts = [
            ['name' => 'Mitra BraderParts', 'email' => 'sponsor@braderparts.com'],
            ['name' => 'Mitra TITAN Tools', 'email' => 'sponsor@titantools.com'],
            ['name' => 'Mitra BT-ACC Battery', 'email' => 'sponsor@btacc.com'],
            ['name' => 'Mitra Sunshine Tools', 'email' => 'sponsor@sunshine.com'],
            ['name' => 'Mitra Borneo Schematics', 'email' => 'sponsor@borneo.com'],
            ['name' => 'Mitra Pragmafix', 'email' => 'sponsor@pragmafix.com'],
        ];

        $sponsorUsers = [];
        foreach ($sponsorAccounts as $sa) {
            $sponsorUsers[$sa['email']] = User::updateOrCreate(
                ['email' => $sa['email']],
                [
                    'name' => $sa['name'],
                    'password' => Hash::make('sponsor123'),
                    'role' => 'sponsor',
                    'birth_date' => '1992-02-14',
                    'gender' => 'male',
                    'phone' => '081234567892',
                    'profile_completed' => true,
                ]
            );
        }
        $sponsorUser = $sponsorUsers['sponsor@braderparts.com'];

        // Seed some sample student users with varied birth dates for demographic analytics
        $sampleDemographics = [
            ['name' => 'Andi Teknisi', 'email' => 'andi@teknisi.id', 'gender' => 'male', 'birth_date' => '2004-03-15', 'city_id' => 3171], // Gen Z (22 yo) - Jakarta Pusat
            ['name' => 'Rian Repair', 'email' => 'rian@repair.id', 'gender' => 'male', 'birth_date' => '2000-07-22', 'city_id' => 3273], // 26 yo - Bandung
            ['name' => 'Siti Solder', 'email' => 'siti@service.id', 'gender' => 'female', 'birth_date' => '1998-11-05', 'city_id' => 3578], // 28 yo - Surabaya
            ['name' => 'Budi Hardware', 'email' => 'budi@vbatponsel.com', 'gender' => 'male', 'birth_date' => '1993-01-30', 'city_id' => 3174], // 33 yo - Jakarta Selatan
            ['name' => 'Dewi Flasher', 'email' => 'dewi@flash.id', 'gender' => 'female', 'birth_date' => '1988-09-18', 'city_id' => 3204], // 38 yo - Kab Bandung
            ['name' => 'Hendra Master', 'email' => 'hendra@master.id', 'gender' => 'male', 'birth_date' => '1982-12-10', 'city_id' => 1101], // 44 yo - Aceh Selatan
        ];

        foreach ($sampleDemographics as $s) {
            User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'gender' => $s['gender'],
                    'birth_date' => $s['birth_date'],
                    'city_id' => $s['city_id'],
                    'profile_completed' => true,
                ]
            );
        }

        // 3. Seed Sponsors from SQL or Default
        $this->seedSponsorsAndProducts($sqlPath, $sponsorUsers);

        // 4. Seed Active Discount Event (Backlog 2.4 & 4.3)
        DB::table('discount_events')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Flash Event Akhir Pekan',
                'discount_type' => 'percentage',
                'discount_value' => 15.00,
                'banner_text' => 'FLASH EVENT - HEMAT 15% SEMUA PART RESMI',
                'start_at' => now()->subDay(),
                'end_at' => now()->addDays(7),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info("Seeding completed successfully!");
    }

    private function seedProvincesAndCities(string $sqlPath): void
    {
        $file = fopen($sqlPath, 'r');
        if (!$file) return;

        $inProvinces = false;
        $inCities = false;

        Schema::disableForeignKeyConstraints();

        while (($line = fgets($file)) !== false) {
            if (str_contains($line, "INSERT INTO `provinces` VALUES")) {
                $inProvinces = true;
                continue;
            }
            if ($inProvinces) {
                $this->parseInsertLine($line, function ($vals) {
                    if (count($vals) >= 3) {
                        DB::table('provinces')->updateOrInsert(
                            ['id' => trim($vals[0], "' ")],
                            ['name' => trim($vals[2], "' "), 'created_at' => now(), 'updated_at' => now()]
                        );
                    } elseif (count($vals) >= 2) {
                        DB::table('provinces')->updateOrInsert(
                            ['id' => trim($vals[0], "' ")],
                            ['name' => trim($vals[1], "' "), 'created_at' => now(), 'updated_at' => now()]
                        );
                    }
                });
                if (str_contains($line, ";")) $inProvinces = false;
            }

            if (str_contains($line, "INSERT INTO `cities` VALUES")) {
                $inCities = true;
                continue;
            }
            if ($inCities) {
                $this->parseInsertLine($line, function ($vals) {
                    if (count($vals) >= 4) {
                        $id = trim($vals[0], "' ");
                        $name = trim($vals[2], "' ");
                        $provinceId = trim($vals[3], "' ");
                        $type = isset($vals[4]) && $vals[4] !== 'NULL' ? trim($vals[4], "' ") : null;
                        $postalCode = isset($vals[5]) && $vals[5] !== 'NULL' ? trim($vals[5], "' ") : null;

                        DB::table('cities')->updateOrInsert(
                            ['id' => $id],
                            [
                                'province_id' => $provinceId,
                                'name' => $name,
                                'type' => $type,
                                'postal_code' => $postalCode,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });
                if (str_contains($line, ";")) $inCities = false;
            }
        }
        fclose($file);
        Schema::enableForeignKeyConstraints();
    }

    private function seedSponsorsAndProducts(string $sqlPath, array $sponsorUsers): void
    {
        $defaultSponsors = [
            [
                'id' => 1,
                'user_id' => $sponsorUsers['sponsor@braderparts.com']->id ?? null,
                'name' => 'BraderParts Indonesia',
                'slug' => 'braderparts',
                'description' => 'Official Distributor suku cadang LCD OLED/Incell, fleksibel, baterai, dan komponen smartphone original bergaransi resmi se-Indonesia.',
                'tier' => 'platinum',
                'weight' => 5,
                'logo_path' => 'assets/images/logo_braderparts.png',
                'website_url' => 'https://shopee.co.id/brader_parts',
                'is_active' => true,
                'contact_email' => 'sales@braderparts.com',
                'phone' => '08123456789',
                'whatsapp' => '628123456789',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'user_id' => $sponsorUsers['sponsor@titantools.com']->id ?? null,
                'name' => 'TITAN Tools Official',
                'slug' => 'titan-tools',
                'description' => 'Spesialis peralatan teknisi presisi tinggi: solder digital cerdas T12, blower hot air gun Quick, mikroskop optik stereo RF4, dan perkakas standar industri servis.',
                'tier' => 'gold',
                'weight' => 4,
                'logo_path' => 'assets/images/logo_titan.png',
                'website_url' => 'https://tokopedia.com',
                'is_active' => true,
                'contact_email' => 'titan@tools.com',
                'phone' => '08123456780',
                'whatsapp' => '628123456780',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'user_id' => $sponsorUsers['sponsor@btacc.com']->id ?? null,
                'name' => 'BT-ACC Battery Super',
                'slug' => 'bt-acc',
                'description' => 'Pusat baterai smartphone original double IC protection dengan kapasitas murni, tidak cepat kembung, awet seharian, dan bergaransi retur langsung tanpa ribet.',
                'tier' => 'gold',
                'weight' => 3,
                'logo_path' => 'assets/images/logo_btacc.png',
                'website_url' => 'https://shopee.co.id',
                'is_active' => true,
                'contact_email' => 'btacc@battery.com',
                'phone' => '08123456781',
                'whatsapp' => '628123456781',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'user_id' => $sponsorUsers['sponsor@sunshine.com']->id ?? null,
                'name' => 'Sunshine Tools',
                'slug' => 'sunshine-tools',
                'description' => 'Brand global terpercaya untuk mesin pemisah LCD rotari 360, power supply cerdas, lampu UV lem jumper cepat, dan perlengkapan servis ponsel modern.',
                'tier' => 'silver',
                'weight' => 2,
                'logo_path' => 'assets/images/logo_sunshine.png',
                'website_url' => 'https://shopee.co.id',
                'is_active' => true,
                'contact_email' => 'sales@sunshine.com',
                'phone' => '08123456782',
                'whatsapp' => '628123456782',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'user_id' => $sponsorUsers['sponsor@borneo.com']->id ?? null,
                'name' => 'Borneo Schematics',
                'slug' => 'borneo-schematics',
                'description' => 'Platform skematik hardware, panduan jalur PCB smartphone, bitmap layout, dan pengukuran tegangan paling lengkap di dunia untuk teknisi pemula hingga mahir.',
                'tier' => 'silver',
                'weight' => 2,
                'logo_path' => 'assets/images/logo_borneo.png',
                'website_url' => 'https://borneoschematics.com',
                'is_active' => true,
                'contact_email' => 'support@borneoschematics.com',
                'phone' => '08123456783',
                'whatsapp' => '628123456783',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'user_id' => $sponsorUsers['sponsor@pragmafix.com']->id ?? null,
                'name' => 'Pragmafix',
                'slug' => 'pragmafix',
                'description' => 'Solusi software diagnosa, skematik multi-fungsi, dan analisa kerusakan hardware smartphone dengan cepat, praktis, dan petunjuk visual langkah demi langkah.',
                'tier' => 'partner',
                'weight' => 1,
                'logo_path' => 'assets/images/logo_pragmafix.png',
                'website_url' => 'https://pragmafix.com',
                'is_active' => true,
                'contact_email' => 'support@pragmafix.com',
                'phone' => '08123456784',
                'whatsapp' => '628123456784',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultSponsors as $sponsor) {
            DB::table('sponsors')->updateOrInsert(['id' => $sponsor['id']], $sponsor);
        }


        $defaultProducts = [
            [
                'sponsor_id' => 1,
                'name' => 'LCD iPhone 11 Pro Max Original Quality',
                'slug' => 'lcd-iphone-11-pro-max',
                'description' => 'Layar OLED kualitas original dengan sensitivitas sentuhan 100% mulus.',
                'price' => 1250000,
                'discount_price' => 1062500,
                'image_path' => 'https://cf.shopee.co.id/file/id-11134207-822wg-mo4wg5tmlhxdf5',
                'shopee_url' => 'https://shopee.co.id/brader_parts?categoryId=100013&entryPoint=ShopByPDP&itemId=22913463095',
                'tokopedia_url' => 'https://tokopedia.com/braderparts',
                'is_featured' => true,
                'is_active' => true,
                'order' => 1,
                'view_count' => 840,
                'click_count' => 192,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 1,
                'name' => 'Baterai Infinix Hot 9/10/11 Play BL-58BX Original',
                'slug' => 'baterai-infinix-bl58bx',
                'description' => 'Baterai replacement berkapasitas 6000mAh pure original IC protection.',
                'price' => 145000,
                'discount_price' => 123250,
                'image_path' => 'https://p16-oec-sg.ibyteimg.com/tos-alisg-i-aphluv4xwc-sg/img/VqbcmM/2025/3/17/a7324144-9f97-4931-a5ef-7bee5b1b4905.jpg~tplv-aphluv4xwc-resize-jpeg:700:0.jpg',
                'shopee_url' => 'https://shopee.co.id/Braderparts-Baterai-Battery-Batre-BL-58BX-for-Infinix-Hot-9-Play-Hot-10-Play-Hot-10S-Hot-11-Play-Hot-12-Play-i.57356590.22913463095',
                'tokopedia_url' => 'https://tokopedia.com/braderparts',
                'is_featured' => true,
                'is_active' => true,
                'order' => 2,
                'view_count' => 1250,
                'click_count' => 310,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Obeng Set Magnetik 24 in 1 Presisi S2 Steel',
                'slug' => 'obeng-set-presisi-24in1',
                'description' => 'Mata obeng bahan baja S2 keras tahan aus untuk teknisi HP presisi.',
                'price' => 45000,
                'discount_price' => 38250,
                'image_path' => 'https://down-id.img.susercontent.com/file/sg-11134201-22100-g6s9simjobjv9f',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 3,
                'view_count' => 620,
                'click_count' => 95,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Flux Amtech NC-559-ASM Original 10cc',
                'slug' => 'flux-amtech-nc559',
                'description' => 'Flux soldering no-clean anti timah bridging sangat licin dan bersih.',
                'price' => 85000,
                'discount_price' => 72250,
                'image_path' => 'https://static.martview.com/product/IMG-969SBYYY/amtech-nc-559-asm-10cc-no-clean-solder-paste-welding-advanced-oil-flux-large.jpg',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 4,
                'view_count' => 410,
                'click_count' => 54,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Solder Listrik T12 Digital Auto Sleep',
                'slug' => 'solder-t12-digital',
                'description' => 'Solder stasiun cerdas panas cepat 5 detik dengan mode auto-sleep hemat energi.',
                'price' => 389000,
                'discount_price' => 330650,
                'image_path' => 'https://s.alicdn.com/@sc04/kf/H46413eac1d614539983f290bac9155daq.jpg',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 5,
                'view_count' => 750,
                'click_count' => 140,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Blower Quick 857D Hot Air Gun Digital',
                'slug' => 'blower-quick-857d',
                'description' => 'Hot air gun presisi digital airflow halus untuk angkat pasang IC tanpa gosong.',
                'price' => 850000,
                'discount_price' => 722500,
                'image_path' => 'https://down-id.img.susercontent.com/file/19715aa697d7746b38dfcdba7c3fbf24',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 6,
                'view_count' => 920,
                'click_count' => 215,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 3,
                'name' => 'Baterai Samsung S20 Ultra Original IC',
                'slug' => 'baterai-samsung-s20-ultra',
                'description' => 'Baterai pure 5000mAh dual IC protection stabil awet seharian.',
                'price' => 249000,
                'discount_price' => 211650,
                'image_path' => 'https://down-id.img.susercontent.com/file/id-11134207-7rbkc-m8soyjc0lybm77',
                'shopee_url' => 'https://shopee.co.id',
                'tokopedia_url' => 'https://tokopedia.com',
                'is_featured' => true,
                'is_active' => true,
                'order' => 7,
                'view_count' => 640,
                'click_count' => 110,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 1,
                'name' => 'LCD Samsung Galaxy A51 Super AMOLED Frame Ori',
                'slug' => 'lcd-samsung-a51-amoled',
                'description' => 'Layar Super AMOLED warna jernih 100% responsif dengan bezel presisi.',
                'price' => 750000,
                'discount_price' => 637500,
                'image_path' => 'https://image.made-in-china.com/2f0j00jrRowCVhkbqK/Super-Amoled-A51-Display-with-Fingerprint-Mobile-Phone-LCD-for-Samsung-Galaxy-A51-A515f-LCD-Display-Touch-Screen-Digitizer-Replacement.webp',
                'shopee_url' => 'https://shopee.co.id/brader_parts',
                'tokopedia_url' => 'https://tokopedia.com/braderparts',
                'is_featured' => true,
                'is_active' => true,
                'order' => 8,
                'view_count' => 520,
                'click_count' => 88,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Mikroskop Stereo Trinokuler RF4 7-50X HD',
                'slug' => 'mikroskop-stereo-rf4-trinokuler',
                'description' => 'Mikroskop teknisi zoom 7X-50X optik tajam dilengkapi lampu ring LED dimmer.',
                'price' => 2850000,
                'discount_price' => 2422500,
                'image_path' => 'https://ae-pic-a1.aliexpress-media.com/kf/S5e10fc910e6047bfb1f3e4e7f446f8cfU.jpg',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 9,
                'view_count' => 310,
                'click_count' => 45,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 3,
                'name' => 'Baterai iPhone 11 High Capacity 3500mAh',
                'slug' => 'baterai-iphone-11-high-capacity',
                'description' => 'Baterai upgrade kapasitas 3500mAh no pop-up message indikator kesehatan 100%.',
                'price' => 210000,
                'discount_price' => 178500,
                'image_path' => 'https://images.tokopedia.net/img/cache/700/o3syd0/1997/1/1/8429f6352eb948388ffdd02e014a391b~.jpeg.webp',
                'shopee_url' => 'https://shopee.co.id',
                'tokopedia_url' => 'https://tokopedia.com',
                'is_featured' => true,
                'is_active' => true,
                'order' => 10,
                'view_count' => 480,
                'click_count' => 92,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Lem LCD Touchscreen T-7000 Hitam 50ml',
                'slug' => 'lem-lcd-t7000-hitam',
                'description' => 'Lem perekat khusus frame LCD warna hitam pekat kedap cahaya daya rekat tinggi.',
                'price' => 25000,
                'discount_price' => 21250,
                'image_path' => 'https://down-id.img.susercontent.com/file/id-11134207-8224s-mjpqi10q4dmv8d',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 11,
                'view_count' => 890,
                'click_count' => 170,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Kawat Jumper Tembaga 0.02mm Presisi',
                'slug' => 'kawat-jumper-002mm',
                'description' => 'Kawat tembaga insulated super tipis 0.02mm untuk jalur mikro IC & fingerprint.',
                'price' => 18000,
                'discount_price' => 15300,
                'image_path' => 'https://ae-pic-a1.aliexpress-media.com/kf/Sd5223e354d53481c9125ac0b4e6646afV.jpg',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 12,
                'view_count' => 610,
                'click_count' => 120,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'name' => 'Pinset Titanium Presisi Anti-Magnetik',
                'slug' => 'pinset-titanium-presisi',
                'description' => 'Pinset ujung ultra runcing paduan titanium tahan karat anti-magnet untuk kapasitor kecil.',
                'price' => 65000,
                'discount_price' => 55250,
                'image_path' => 'https://down-id.img.susercontent.com/file/id-11134207-822wn-mpw26e3jdp8v52',
                'shopee_url' => 'https://shopee.co.id/titan_tools',
                'tokopedia_url' => 'https://tokopedia.com/titantools',
                'is_featured' => true,
                'is_active' => true,
                'order' => 13,
                'view_count' => 430,
                'click_count' => 76,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 1,
                'name' => 'Konektor Charger Type-C Universal 10 Pcs',
                'slug' => 'konektor-charger-type-c-10pcs',
                'description' => 'Paket 10 pcs port charging USB Type-C pin tembaga presisi untuk berbagai HP Android.',
                'price' => 35000,
                'discount_price' => 29750,
                'image_path' => 'https://down-id.img.susercontent.com/file/id-11134207-81ztk-mf86gp18ytxr81',
                'shopee_url' => 'https://shopee.co.id/brader_parts',
                'tokopedia_url' => 'https://tokopedia.com/braderparts',
                'is_featured' => true,
                'is_active' => true,
                'order' => 14,
                'view_count' => 380,
                'click_count' => 62,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultProducts as $prod) {
            DB::table('sponsor_products')->updateOrInsert(['slug' => $prod['slug']], $prod);
        }

        // Seed Campaigns (Backlog 1.1 Hero Slider & Backlog 2.2 Horizontal Shop Banner)
        $campaigns = [
            [
                'sponsor_id' => 1,
                'title' => 'Diskon Akbar Suku Cadang BraderParts',
                'placement_type' => 'hero_slider',
                'media_path' => 'assets/images/banner_braderparts.png',
                'media_type' => 'image',
                'target_url' => 'https://shopee.co.id/brader_parts',
                'description' => 'Dapatkan diskon sparepart LCD dan IC original hingga 30% khusus anggota resmi VbatPonsel.',
                'daily_limit' => 5000,
                'weight' => 5,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonths(1),
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'title' => 'Toolkit Lengkap Teknisi TITAN Tools',
                'placement_type' => 'hero_slider',
                'media_path' => 'assets/images/banner_promo_diskon.png',
                'media_type' => 'image',
                'target_url' => 'https://tokopedia.com/titantools',
                'description' => 'Paket toolkit solder, timah presisi, dan mikroskop siap kerja dengan garansi 1 tahun.',
                'daily_limit' => 3000,
                'weight' => 4,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonths(1),
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 1,
                'title' => 'Katalog Spesial BraderParts - Sparepart Bergaransi',
                'placement_type' => 'shop_horizontal',
                'media_path' => 'assets/images/banner_braderparts.png',
                'media_type' => 'image',
                'target_url' => 'https://shopee.co.id/brader_parts',
                'description' => 'Banner promosi di tengah katalog belanja suku cadang.',
                'daily_limit' => 10000,
                'weight' => 3,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonths(1),
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sponsor_id' => 2,
                'title' => 'Promo Peralatan Solder & Blower Auto-Sleep',
                'placement_type' => 'shop_horizontal',
                'media_path' => 'assets/images/banner_promo_diskon.png',
                'media_type' => 'image',
                'target_url' => 'https://tokopedia.com/titantools',
                'description' => 'Diskon kilat perkakas servis HP.',
                'daily_limit' => 10000,
                'weight' => 2,
                'start_date' => now()->subDay(),
                'end_date' => now()->addMonths(1),
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('campaigns')->insert($campaigns);

        // Seed some sample campaign logs for initial analytics (Backlog 4.6)
        $logs = [];
        for ($i = 0; $i < 150; $i++) {
            $logs[] = [
                'campaign_id' => rand(1, 4),
                'sponsor_product_id' => rand(1, 4),
                'user_id' => rand(1, 8),
                'event_type' => $i % 4 == 0 ? 'click' : ($i % 6 == 0 ? 'wishlist' : 'view'),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'VbatPonsel-Flutter-App/1.0',
                'created_at' => now()->subHours(rand(1, 72)),
            ];
        }
        DB::table('campaign_logs')->insert($logs);
    }

    private function parseInsertLine(string $line, callable $callback): void
    {
        preg_match_all('/\(([^()]+)\)/', $line, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $tuple) {
                $fields = str_getcsv($tuple, ',', "'");
                $callback($fields);
            }
        }
    }
}
