<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1.1 — Benefit Categories
     * Defines what benefits are available in the system (e.g. product quota,
     * marketplace link, hero slider slot, best deal slot, etc.)
     */
    public function up(): void
    {
        Schema::create('benefit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // e.g. katalog_produk, outbound_marketplace, hero_slider
            $table->string('name'); // Human-readable name
            $table->text('description')->nullable();
            $table->string('value_type')->default('integer'); // integer | boolean | string | json
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_categories');
    }
};
