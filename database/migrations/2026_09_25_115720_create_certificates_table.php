<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('certificate_number')->unique();
            $table->string('type'); // course, workshop, competition, participation
            $table->timestamp('issued_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('qr_data')->nullable(); // QR payload for verification
            $table->string('qr_image_url')->nullable();
            $table->string('pdf_url')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
