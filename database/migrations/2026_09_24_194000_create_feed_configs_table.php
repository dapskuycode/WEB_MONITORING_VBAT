<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_configs', function (Blueprint $table) {
            $table->id();
            $table->string('feed_type')->index(); // 'shop' or 'home'
            $table->string('banner_type')->default('shop_horizontal'); // hero_slider, shop_horizontal, card_slider, popup_modal
            $table->unsignedInteger('insertion_interval')->default(12); // insert banner every N items
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // additional config (max_banners, priority, etc.)
            $table->timestamps();

            $table->unique(['feed_type', 'banner_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_configs');
    }
};
