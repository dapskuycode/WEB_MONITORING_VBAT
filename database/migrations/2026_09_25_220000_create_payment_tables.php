<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Payment Packages (config-driven)
        Schema::create('payment_packages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'android', 'iphone', 'bundling', etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2); // IDR
            $table->enum('type', ['one_time', 'subscription'])->default('one_time');
            $table->integer('subscription_months')->nullable(); // 12 for yearly
            $table->json('entitlements'); // { "android": true, "iphone": false } or course_type
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Payment Transactions
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_package_id')->constrained('payment_packages')->onDelete('restrict');
            $table->string('external_reference')->unique(); // Midtrans order_id
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'settlement', 'failed', 'cancelled', 'expired', 'refund'])->default('pending');
            $table->json('midtrans_response')->nullable(); // Full Midtrans response
            $table->string('payment_method')->nullable(); // 'credit_card', 'bank_transfer', etc.
            $table->text('notes')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('external_reference');
        });

        // 3. Webhook Log (idempotency + audit)
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_type'); // 'midtrans'
            $table->string('external_id')->nullable(); // transaction external_reference
            $table->enum('status', ['pending', 'received', 'processed', 'failed'])->default('received');
            $table->json('payload');
            $table->json('signature_data')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index('external_id');
            $table->index('webhook_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_packages');
    }
};
