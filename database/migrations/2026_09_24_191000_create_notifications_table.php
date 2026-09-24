<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('recipient'); // recipient_type (user/admin), recipient_id
            $table->string('type', 64)->index(); // payment_status, sponsor_upload, hs_unlock, etc.
            $table->string('title');
            $table->text('body');
            $table->string('deep_link')->nullable();
            $table->json('data')->nullable(); // extra payload
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['recipient_type', 'recipient_id', 'is_read', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
