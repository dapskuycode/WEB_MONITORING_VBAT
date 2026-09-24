<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);                 // product.uploaded, campaign.paused, etc.
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->string('actor_type', 50);           // 'sponsor' | 'admin' | 'system'
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('target_type', 50)->nullable();  // 'product' | 'campaign' | 'sponsor' | 'best_deal'
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();       // flexible payload
            $table->string('deep_link', 500)->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->unsignedBigInteger('recipient_admin_id')->nullable();  // null = broadcast to all admins
            $table->timestamps();

            $table->index(['is_read', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['recipient_admin_id', 'is_read']);
            $table->index(['target_type', 'target_id']);
        });

        // Admin audit log — tracks all admin override actions
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action', 100);              // sponsor.deactivated, campaign.paused, benefit.overridden
            $table->string('actor_type', 50);           // 'admin' (only admins can audit)
            $table->unsignedBigInteger('actor_id');
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id');
            $table->json('before_state')->nullable();   // snapshot before override
            $table->json('after_state')->nullable();    // snapshot after override
            $table->string('reason', 500)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['target_type', 'target_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('admin_notifications');
    }
};
