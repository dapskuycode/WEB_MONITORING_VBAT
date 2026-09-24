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
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('unit_code', 50)->unique();
            $table->string('unit_title');
            $table->enum('material_type', ['youtube_video', 'pdf_document', 'text_content', 'external_link'])->default('youtube_video');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('youtube_video_id', 20)->nullable()->index();
            $table->string('pdf_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->text('content_text')->nullable();
            $table->string('external_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('quiz_required')->default(false);
            $table->foreignId('quiz_id')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lesson_id', 'status', 'sort_order']);
            $table->index(['material_type', 'status']);
            $table->index('is_required');
        });

        Schema::create('learning_material_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'learning_material_id']);
            $table->index('viewed_at');
            $table->index('progress_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learning_material_views');
        Schema::dropIfExists('learning_materials');
    }
};
