<?php

namespace Database\Seeders;

use App\Models\PaymentPackage;
use Illuminate\Database\Seeder;

class PaymentPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'code' => 'android',
                'name' => 'Android Only',
                'description' => 'Akses full kelas Android lengkap',
                'price' => 800000,
                'type' => 'one_time',
                'entitlements' => ['course_type' => 'android'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'iphone',
                'name' => 'iPhone Only',
                'description' => 'Akses full kelas iPhone lengkap',
                'price' => 2000000,
                'type' => 'one_time',
                'entitlements' => ['course_type' => 'iphone'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'code' => 'bundling',
                'name' => 'Android + iPhone Bundle',
                'description' => 'Akses kelas Android dan iPhone sekaligus',
                'price' => 2500000,
                'type' => 'one_time',
                'entitlements' => ['course_type' => ['android', 'iphone']],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $data) {
            PaymentPackage::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
