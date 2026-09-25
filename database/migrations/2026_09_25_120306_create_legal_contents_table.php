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
        Schema::create('legal_contents', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // terms, privacy, about, consent
            $table->string('version');
            $table->text('content');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->unique(['type', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_contents');
    }
};
