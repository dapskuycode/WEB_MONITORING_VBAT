<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsor_products', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->nullable()->default(null)->after('discount_price');
            $table->integer('sold_count')->nullable()->default(null)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('sponsor_products', function (Blueprint $table) {
            $table->dropColumn(['rating', 'sold_count']);
        });
    }
};
