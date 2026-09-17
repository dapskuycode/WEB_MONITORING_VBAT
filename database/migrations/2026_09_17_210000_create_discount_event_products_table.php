<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_event_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_event_id')->constrained('discount_events')->cascadeOnDelete();
            $table->foreignId('sponsor_product_id')->constrained('sponsor_products')->cascadeOnDelete();
            $table->string('custom_discount_type')->nullable(); // percentage / fixed_nominal (null = inherit from event)
            $table->decimal('custom_discount_value', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['discount_event_id', 'sponsor_product_id'], 'event_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_event_products');
    }
};
