<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('sponsors')->cascadeOnDelete();
            $table->string('title');
            $table->string('placement_type')->default('hero_slider'); // hero_slider, shop_horizontal, best_deal
            $table->string('media_path')->nullable();
            $table->string('media_type')->default('image'); // image, video
            $table->text('target_url')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('daily_limit')->default(1000); // limit tayang per hari
            $table->integer('weight')->default(1); // frekuensi tayang (bobot rotasi)
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('sponsor_product_id')->nullable()->constrained('sponsor_products')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type'); // view, click, wishlist
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'event_type', 'created_at']);
            $table->index(['sponsor_product_id', 'event_type', 'created_at']);
        });

        Schema::create('user_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('sponsor_product_id')->constrained('sponsor_products')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'sponsor_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wishlists');
        Schema::dropIfExists('campaign_logs');
        Schema::dropIfExists('campaigns');
    }
};
