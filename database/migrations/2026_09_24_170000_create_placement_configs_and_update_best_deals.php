<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. placement_configs table
        Schema::create('placement_configs', function (Blueprint $table) {
            $table->id();
            $table->string('placement_type'); // hero_slider, horizontal_infeed, popup, best_deal, card_infeed
            $table->foreignId('tier_id')->nullable()->constrained('sponsor_tiers')->nullOnDelete();
            $table->unsignedInteger('max_slots')->default(3);
            $table->double('target_probability', 4, 3)->default(0.500); // 0.000–1.000
            $table->unsignedInteger('insertion_interval')->default(10); // items between placements
            $table->string('fallback_behavior', 20)->default('hide'); // hide, show_default
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['placement_type', 'tier_id']);
            $table->index(['placement_type', 'is_active']);
        });

        // 2. Update best_deals: add selection tracking
        Schema::table('best_deals', function (Blueprint $table) {
            $table->foreignId('selected_by')->nullable()->after('is_active')
                  ->constrained('users')->nullOnDelete();
            $table->string('selection_type', 20)->default('manual') // manual, auto, none
                  ->after('selected_by');
            $table->foreignId('sponsor_product_id')->nullable()->after('selection_type')
                  ->constrained('sponsor_products')->nullOnDelete();
        });

        // 3. Update campaign_logs: add placement context
        Schema::table('campaign_logs', function (Blueprint $table) {
            $table->string('placement_context', 50)->nullable()->after('event_type');
            $table->string('session_id', 64)->nullable()->after('user_agent');

            $table->index(['placement_context', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_configs');

        Schema::table('best_deals', function (Blueprint $table) {
            $table->dropForeign(['selected_by']);
            $table->dropForeign(['sponsor_product_id']);
            $table->dropColumn(['selected_by', 'selection_type', 'sponsor_product_id']);
        });

        Schema::table('campaign_logs', function (Blueprint $table) {
            $table->dropIndex(['placement_context', 'created_at']);
            $table->dropColumn(['placement_context', 'session_id']);
        });
    }
};
