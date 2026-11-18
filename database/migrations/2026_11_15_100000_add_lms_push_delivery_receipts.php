<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lms_notification_dispatches', function (Blueprint $table) {
            $table->uuid('processing_token')->nullable();
            $table->timestamp('processing_until')->nullable();
        });
        Schema::create('lms_push_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('lms_notification_dispatches')->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->string('status', 24);
            $table->string('provider_receipt')->nullable();
            $table->string('error_code', 80)->nullable();
            $table->timestamps();
            $table->unique(['dispatch_id', 'token_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_push_deliveries');
        Schema::table('lms_notification_dispatches', fn (Blueprint $table) => $table->dropColumn(['processing_token', 'processing_until']));
    }
};
