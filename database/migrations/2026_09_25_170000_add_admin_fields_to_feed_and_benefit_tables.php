<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add admin-manageable columns to feed_configs and benefit_categories.
     *
     * BE-08: Admin Feed Config & Benefit Override UI needs these fields
     * for CRUD management via the admin API.
     */
    public function up(): void
    {
        Schema::table('feed_configs', function (Blueprint $table) {
            $table->foreignId('tier_id')->nullable()->after('id')->constrained('sponsor_tiers')->nullOnDelete();
            $table->unsignedInteger('max_banners')->nullable()->after('insertion_interval');
            $table->integer('sort_order')->default(0)->after('metadata');
        });

        Schema::table('benefit_categories', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('feed_configs', function (Blueprint $table) {
            $table->dropForeign(['tier_id']);
            $table->dropColumn(['tier_id', 'max_banners', 'sort_order']);
        });

        Schema::table('benefit_categories', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
