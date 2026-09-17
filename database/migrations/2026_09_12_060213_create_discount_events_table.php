<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('discount_type')->default('percentage'); // percentage / fixed_nominal
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->string('banner_text')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_events');
    }
};
