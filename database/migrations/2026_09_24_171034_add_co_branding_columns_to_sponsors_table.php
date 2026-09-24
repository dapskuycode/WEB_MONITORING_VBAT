<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-SF-02 — [C1] Co-Branding Asset System (Diamond tier).
     *
     * Adds nullable columns for co-branding assets that only Diamond-tier
     * sponsors may upload (header banner + splash screen logo).
     */
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('co_branding_header_url')->nullable()->after('logo_path');
            $table->string('co_branding_splash_url')->nullable()->after('co_branding_header_url');
        });
    }

    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn(['co_branding_header_url', 'co_branding_splash_url']);
        });
    }
};
