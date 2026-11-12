<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $t) {
            $t->string('code', 50)->nullable()->unique();
        });
        Schema::create('lms_uploads', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('purpose');
            $t->string('original_name');
            $t->string('mime_type');
            $t->unsignedBigInteger('size_bytes');
            $t->string('sha256', 64);
            $t->string('path');
            $t->string('status')->default('quarantine')->index();
            $t->string('reason')->nullable();
            $t->unsignedInteger('page_count')->nullable();
            $t->timestamp('scanned_at')->nullable();
            $t->timestamps();
        });
        Schema::table('learning_materials', function (Blueprint $t) {
            $t->foreignUuid('pdf_upload_id')->nullable()->constrained('lms_uploads')->restrictOnDelete();
        });
        Schema::create('lms_reading_progress', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $t->string('source_hash', 64);
            $t->unsignedInteger('last_page')->default(1);
            $t->unsignedInteger('max_page')->default(0);
            $t->timestamp('reached_end_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->unique(['user_id', 'material_id', 'source_hash']);
            $t->timestamps();
        });
        Schema::create('lms_reading_sessions', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $t->foreignId('progress_id')->constrained('lms_reading_progress')->cascadeOnDelete();
            $t->uuid('upload_id');
            $t->uuid('request_id');
            $t->string('source_hash', 64);
            $t->unsignedInteger('revision');
            $t->unsignedInteger('sequence')->default(0);
            $t->timestamp('expires_at');
            $t->unique(['user_id', 'request_id']);
            $t->timestamps();
        });
        Schema::create('lms_reading_events', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('session_id')->constrained('lms_reading_sessions')->cascadeOnDelete();
            $t->uuid('event_id');
            $t->string('payload_hash', 64);
            $t->unique(['session_id', 'event_id']);
            $t->unsignedInteger('sequence');
            $t->unique(['session_id', 'sequence']);
            $t->timestamps();
        });
        Schema::create('lms_imports', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('file_hash', 64);
            $t->json('rows');
            $t->json('errors');
            $t->json('snapshot');
            $t->string('status')->default('preview');
            $t->timestamp('expires_at');
            $t->json('result')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['lms_imports', 'lms_reading_events', 'lms_reading_sessions', 'lms_reading_progress'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('learning_materials', function (Blueprint $t) {
            $t->dropConstrainedForeignId('pdf_upload_id');
        });
        Schema::dropIfExists('lms_uploads');
        Schema::table('quizzes', function (Blueprint $t) {
            $t->dropUnique(['code']);
            $t->dropColumn('code');
        });
    }
};
