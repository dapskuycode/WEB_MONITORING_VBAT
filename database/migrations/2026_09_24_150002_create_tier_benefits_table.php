<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1.1 — Tier Benefits (default benefit values per tier)
     * Stores the default value of each benefit category for each tier.
     * These are the "default awal" values that Admin can override.
     */
    public function up(): void
    {
        Schema::create('tier_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tier_id')->constrained('sponsor_tiers')->cascadeOnDelete();
            $table->foreignId('benefit_category_id')->constrained('benefit_categories')->cascadeOnDelete();
            $table->json('value')->nullable(); // JSON-encoded value (number, bool, string, or object)
            $table->string('label')->nullable(); // Optional display label (e.g. "Top 5")
            $table->boolean('is_default_awal')->default(true); // Marks this as the initial default
            $table->timestamps();

            $table->unique(['tier_id', 'benefit_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tier_benefits');
    }
};
