<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64)->index(); // product_view, impression, course_start, etc.
            $table->unsignedBigInteger('actor_id')->nullable()->index(); // user ID from auth session
            $table->uuid('session_id')->nullable()->index(); // anonymous visitor tracking
            $table->string('target_type', 64)->nullable()->index(); // sponsor_product, campaign, video, course
            $table->unsignedBigInteger('target_id')->nullable()->index();
            $table->json('context')->nullable(); // { placement, position, page_cursor, ... }
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Composite index for common queries
            $table->index(['event_type', 'created_at']);
            $table->index(['target_type', 'target_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
