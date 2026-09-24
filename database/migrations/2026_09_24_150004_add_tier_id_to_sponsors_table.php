<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1.1 — Update sponsors.tier to FK to sponsor_tiers
     *
     * Migrates the old string-based tier column ('partner', 'platinum',
     * 'gold', 'silver') to a foreign key referencing sponsor_tiers.id.
     */
    public function up(): void
    {
        // Step 1: Add the new tier_id column
        Schema::table('sponsors', function (Blueprint $table) {
            $table->foreignId('tier_id')
                ->nullable()
                ->after('tier')
                ->constrained('sponsor_tiers')
                ->nullOnDelete();
        });

        // Step 2: Migrate existing data — map old tier strings to new tier slugs
        // Old values: platinum, gold, silver, partner → mapped by slug
        $tierMap = [
            'platinum' => 'platinum',
            'gold'     => 'gold',
            'silver'   => 'silver',
            'partner'  => 'kontribusi', // Old "partner" maps to new "kontribusi" tier
        ];

        foreach ($tierMap as $oldTier => $newSlug) {
            $tier = DB::table('sponsor_tiers')->where('slug', $newSlug)->first();
            if ($tier) {
                DB::table('sponsors')
                    ->where('tier', $oldTier)
                    ->update(['tier_id' => $tier->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropForeign(['tier_id']);
            $table->dropColumn('tier_id');
        });
    }
};
