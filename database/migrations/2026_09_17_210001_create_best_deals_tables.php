<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('best_deals', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('banner_path')->nullable();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('best_deal_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('best_deal_id')->constrained('best_deals')->cascadeOnDelete();
            $table->foreignId('sponsor_product_id')->constrained('sponsor_products')->cascadeOnDelete();
            $table->string('badge_text')->default('BEST DEAL');
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['best_deal_id', 'sponsor_product_id'], 'best_deal_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('best_deal_products');
        Schema::dropIfExists('best_deals');
    }
};
