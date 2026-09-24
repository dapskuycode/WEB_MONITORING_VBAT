<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * REQ-SF-03 — [M3] Share of Voice (SoV) placement weighting.
     *
     * share_of_voice expresses the target proportion of impressions a
     * placement config (per tier) should receive. Values are 0.000–1.000.
     */
    public function up(): void
    {
        Schema::table('placement_configs', function (Blueprint $table) {
            $table->double('share_of_voice', 4, 3)->default(0.000)->after('target_probability');
        });
    }

    public function down(): void
    {
        Schema::table('placement_configs', function (Blueprint $table) {
            $table->dropColumn('share_of_voice');
        });
    }
};
