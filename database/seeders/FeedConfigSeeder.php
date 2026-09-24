<?php

namespace Database\Seeders;

use App\Models\FeedConfig;
use Illuminate\Database\Seeder;

class FeedConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'feed_type' => 'shop',
                'banner_type' => 'shop_horizontal',
                'insertion_interval' => 12,
                'is_active' => true,
                'metadata' => ['max_banners' => 5, 'priority' => 'weight'],
            ],
            [
                'feed_type' => 'home',
                'banner_type' => 'hero_slider',
                'insertion_interval' => 8,
                'is_active' => true,
                'metadata' => ['max_banners' => 3, 'priority' => 'weight'],
            ],
        ];

        foreach ($configs as $config) {
            FeedConfig::firstOrCreate(
                ['feed_type' => $config['feed_type'], 'banner_type' => $config['banner_type']],
                $config
            );
        }
    }
}
