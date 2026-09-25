<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'code' => 'first_login',
                'name' => 'Pertama Masuk',
                'description' => 'Langkah awal menuju kehebatan',
                'icon_url' => 'user-check',
            ],
            [
                'code' => 'quiz_master',
                'name' => 'Rajin Kuis',
                'description' => 'Menyelesaikan 10 kuis',
                'icon_url' => 'award',
            ],
            [
                'code' => 'lesson_complete',
                'name' => 'Tamat Materi',
                'description' => 'Menyelesaikan pelajaran pertama',
                'icon_url' => 'book-open',
            ],
            [
                'code' => 'streak_7',
                'name' => '7 Hari Berturut',
                'description' => 'Belajar 7 hari berturut-turut',
                'icon_url' => 'flame',
            ],
            [
                'code' => 'certified',
                'name' => 'Sertifikat Pertama',
                'description' => 'Mendapat sertifikat pertama',
                'icon_url' => 'badge-check',
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
