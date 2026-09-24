<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1.1 — Sponsor Benefit Overrides
     * Allows Admin to override the default tier benefit for a specific sponsor
     * without changing the tier definition.
     */
    public function up(): void
    {
        Schema::create('sponsor_benefit_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
            $table->foreignId('benefit_category_id')->constrained('benefit_categories')->cascadeOnDelete();
            $table->json('override_value')->nullable();
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['sponsor_id', 'benefit_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsor_benefit_overrides');
    }
};
