<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_offline_devices')) {
            Schema::create('lms_offline_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id', 128);
                $table->string('device_name', 160)->nullable();
                $table->string('platform', 40)->default('unknown');
                $table->string('status', 24)->default('active');
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'device_id']);
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('lms_offline_licenses')) {
            Schema::create('lms_offline_licenses', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id', 128);
                $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
                $table->uuid('upload_id');
                $table->string('source_hash', 64);
                $table->unsignedInteger('material_revision')->default(1);
                $table->unsignedBigInteger('size_bytes');
                $table->unsignedInteger('page_count')->default(1);
                $table->string('status', 24)->default('active');
                $table->unsignedInteger('last_sequence')->default(0);
                $table->uuid('request_id');
                $table->timestamp('issued_at');
                $table->timestamp('expires_at');
                $table->timestamp('revoked_at')->nullable();
                $table->string('revoke_reason', 120)->nullable();
                $table->timestamps();
                $table->index(['user_id', 'device_id', 'material_id']);
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('lms_offline_sync_events')) {
            Schema::create('lms_offline_sync_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id', 128);
                $table->uuid('license_id')->nullable();
                $table->unsignedBigInteger('material_id')->nullable();
                $table->uuid('event_id');
                $table->unsignedInteger('sequence')->default(0);
                $table->string('event_type', 48)->default('reading_progress');
                $table->json('payload');
                $table->string('payload_hash', 64);
                $table->timestamp('occurred_at')->nullable();
                $table->string('status', 24);
                $table->string('reason', 120)->nullable();
                $table->timestamp('processed_at');
                $table->timestamps();
                $table->unique(['user_id', 'event_id']);
                $table->index(['license_id', 'sequence']);
            });
        }

        if (! Schema::hasTable('lms_device_push_tokens')) {
            Schema::create('lms_device_push_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('device_id', 128);
                $table->string('push_token', 255);
                $table->string('platform', 32)->default('android');
                $table->string('status', 24)->default('active');
                $table->timestamp('last_registered_at')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'device_id']);
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('lms_notification_dispatches')) {
            Schema::create('lms_notification_dispatches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('notification_id')->nullable();
                $table->string('event_key', 190)->unique();
                $table->string('event_type', 64);
                $table->string('channel', 32)->default('in_app_push');
                $table->json('device_tokens')->nullable();
                $table->string('title', 200);
                $table->text('body');
                $table->string('deep_link', 255);
                $table->json('meta')->nullable();
                $table->string('status', 24)->default('queued');
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->string('provider_receipt', 120)->nullable();
                $table->text('last_error')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_notification_dispatches');
        Schema::dropIfExists('lms_device_push_tokens');
        Schema::dropIfExists('lms_offline_sync_events');
        Schema::dropIfExists('lms_offline_licenses');
        Schema::dropIfExists('lms_offline_devices');
    }
};
