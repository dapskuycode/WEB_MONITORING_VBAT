<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('status')->default('legacy')->index();
            $table->string('final_reason')->nullable();
            $table->uuid('request_id')->nullable();
            $table->json('snapshot')->nullable();
            $table->json('flagged_question_ids')->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('deadline_at')->nullable()->index();
            $table->unique(['user_id', 'request_id']);
        });
        Schema::create('lms_quiz_starts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->uuid('request_id');
            $table->unique(['user_id', 'request_id']);
            $table->timestamps();
        });
        Schema::create('lms_quiz_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->uuid('request_id');
            $table->string('payload_hash', 64);
            $table->unique(['attempt_id', 'request_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_quiz_requests');
        Schema::dropIfExists('lms_quiz_starts');
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'request_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['deadline_at']);
            $table->dropColumn(['status', 'final_reason', 'request_id', 'snapshot', 'flagged_question_ids', 'source_hash', 'version', 'deadline_at']);
        });
    }
};
