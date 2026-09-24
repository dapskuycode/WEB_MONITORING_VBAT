<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1.1 — Sponsor Tier System
     * Creates sponsor_tiers table for the 6-tier system:
     * kontribusi, bronze, silver, gold, platinum, diamond
     */
    public function up(): void
    {
        Schema::create('sponsor_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // kontribusi, bronze, silver, gold, platinum, diamond
            $table->string('name'); // Display name
            $table->string('badge_label'); // Label for UI badge
            $table->integer('sort_order')->default(0); // Sorting order (ascending)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsor_tiers');
    }
};
