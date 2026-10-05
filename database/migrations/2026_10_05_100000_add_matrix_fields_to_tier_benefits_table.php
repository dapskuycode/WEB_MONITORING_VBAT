<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SPONSOR-01: Support baseline initial values, unlimited toggle (∞), and disabled cells (—).
     */
    public function up(): void
    {
        Schema::table('tier_benefits', function (Blueprint $table) {
            if (! Schema::hasColumn('tier_benefits', 'initial_value')) {
                $table->json('initial_value')->nullable()->after('value');
            }
            if (! Schema::hasColumn('tier_benefits', 'is_unlimited')) {
                $table->boolean('is_unlimited')->default(false)->after('label');
            }
            if (! Schema::hasColumn('tier_benefits', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false)->after('is_unlimited');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tier_benefits', function (Blueprint $table) {
            $table->dropColumn(['initial_value', 'is_unlimited', 'is_disabled']);
        });
    }
};
