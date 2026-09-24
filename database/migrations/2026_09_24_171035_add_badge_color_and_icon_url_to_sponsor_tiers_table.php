<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-SF-02 — [m2] Explicit tier badge metadata.
     *
     * Adds badge_color (hex) and icon_url so the mobile client can render the
     * tier badge without hardcoding colors or asset paths.
     */
    public function up(): void
    {
        Schema::table('sponsor_tiers', function (Blueprint $table) {
            $table->string('badge_color', 20)->nullable()->after('badge_label');
            $table->string('icon_url')->nullable()->after('badge_color');
        });
    }

    public function down(): void
    {
        Schema::table('sponsor_tiers', function (Blueprint $table) {
            $table->dropColumn(['badge_color', 'icon_url']);
        });
    }
};
