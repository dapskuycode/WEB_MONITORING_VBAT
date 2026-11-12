<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_playback_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->unique()->constrained('learning_materials')->cascadeOnDelete();
            $table->string('video_id', 11);
            $table->unsignedInteger('duration_ms');
            $table->string('content_hash', 64);
            $table->timestamps(6);
        });
        Schema::create('lms_playback_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->string('content_hash', 64);
            $table->json('ranges');
            $table->unsignedInteger('covered_ms')->default(0);
            $table->unsignedInteger('max_position_ms')->default(0);
            $table->unsignedInteger('resume_ms')->default(0);
            $table->timestamp('completed_at', 6)->nullable();
            $table->timestamps(6);
            $table->unique(['user_id', 'material_id', 'content_hash'], 'lms_progress_identity');
        });
        Schema::create('lms_playback_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('progress_id')->constrained('lms_playback_progress')->cascadeOnDelete();
            $table->uuid('request_id');
            $table->string('content_hash', 64);
            $table->string('video_id', 11);
            $table->unsignedInteger('duration_ms');
            $table->unsignedInteger('material_revision');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->unsignedInteger('last_position_ms')->default(0);
            $table->string('last_state')->default('paused');
            $table->timestamp('last_seen_at', 6);
            $table->timestamp('expires_at', 6);
            $table->timestamp('ended_at', 6)->nullable();
            $table->timestamps(6);
            $table->unique(['user_id', 'request_id']);
        });
        Schema::create('lms_playback_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')->constrained('lms_playback_sessions')->cascadeOnDelete();
            $table->uuid('event_id');
            $table->unsignedInteger('sequence');
            $table->string('payload_hash', 64);
            $table->json('response');
            $table->unique(['session_id', 'event_id']);
            $table->unique(['session_id', 'sequence']);
        });
    }

    public function down(): void
    {
        foreach (['lms_playback_events', 'lms_playback_sessions', 'lms_playback_progress', 'lms_playback_sources'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
