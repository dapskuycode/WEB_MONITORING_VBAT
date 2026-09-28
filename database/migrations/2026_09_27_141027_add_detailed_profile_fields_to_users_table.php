<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('district')->nullable()->after('city_id'); // Kecamatan
            $table->string('village')->nullable()->after('district'); // Kelurahan / Desa
            $table->string('instagram')->nullable()->after('address');
            $table->string('facebook')->nullable()->after('instagram');
            $table->string('tiktok')->nullable()->after('facebook');
            $table->string('youtube')->nullable()->after('tiktok');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'district',
                'village',
                'instagram',
                'facebook',
                'tiktok',
                'youtube',
            ]);
        });
    }
};
