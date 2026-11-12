<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $t) {
            $t->string('question_type')->default('mcq');
            $t->json('answer_aliases')->nullable();
            $t->json('rubric')->nullable();
        });
        Schema::table('quizzes', function (Blueprint $t) {
            $t->string('grading_mode')->default('hybrid');
            $t->string('grading_policy')->default('highest');
            $t->boolean('shuffle_questions')->default(false);
            $t->boolean('shuffle_options')->default(false);
            $t->string('placement')->nullable();
            $t->foreignId('target_course_id')->nullable()->constrained('courses')->restrictOnDelete();
            $t->foreignId('target_module_id')->nullable()->constrained('lessons')->restrictOnDelete();
            $t->boolean('is_required')->default(false);
        });
        Schema::table('quiz_attempts', function (Blueprint $t) {
            $t->decimal('score', 5, 2)->nullable()->change();
            $t->string('grade_status')->default('none');
            $t->unsignedInteger('grade_version')->default(0);
            $t->unsignedInteger('published_revision')->nullable();
        });
        DB::table('quiz_attempts')->whereIn('status', ['submitted', 'expired'])->whereNotNull('score')->update(['grade_status' => 'published']);
        Schema::create('lms_grade_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $t->unsignedInteger('revision');
            $t->unique(['attempt_id', 'revision']);
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status');
            $t->json('question_grades');
            $t->decimal('score', 5, 2)->nullable();
            $t->boolean('passed')->default(false);
            $t->text('reason');
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });
        DB::table('quiz_attempts')->where('grade_status', 'published')->whereNotNull('snapshot')->orderBy('id')->chunkById(100, function ($attempts) {
            foreach ($attempts as $attempt) {
                $snapshot = json_decode($attempt->snapshot, true);
                $answers = json_decode($attempt->answers ?? '{}', true);
                $grades = [];
                foreach ($snapshot['questions'] ?? [] as $q) {
                    $grades[] = ['question_id' => $q['id'], 'points' => ($answers[$q['id']] ?? null) === ($q['correct_answer'] ?? '') ? $q['points'] : 0, 'feedback' => '', 'criteria' => []];
                }
                DB::table('lms_grade_revisions')->insert(['attempt_id' => $attempt->id, 'revision' => 1, 'actor_id' => null, 'status' => 'published', 'question_grades' => json_encode($grades),
                    'score' => $attempt->score, 'passed' => $attempt->is_passed, 'reason' => 'Migrasi hasil final tepercaya B07/B08.', 'published_at' => $attempt->submitted_at, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('quiz_attempts')->where('id', $attempt->id)->update(['grade_version' => 1, 'published_revision' => 1]);
            }
        });
        Schema::create('lms_grade_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $t->uuid('request_id');
            $t->string('payload_hash', 64);
            $t->unique(['attempt_id', 'request_id']);
            $t->timestamps();
        });
        Schema::table('courses', function (Blueprint $t) {
            $t->foreignId('prerequisite_course_id')->nullable()->constrained('courses')->restrictOnDelete();
        });
        Schema::table('certificates', function (Blueprint $t) {
            $t->foreignId('course_id')->nullable()->constrained('courses')->restrictOnDelete();
            $t->json('identity_snapshot')->nullable();
            $t->json('completion_snapshot')->nullable();
            $t->string('status')->default('valid');
            $t->text('revoke_reason')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->uuid('verification_token')->nullable()->unique();
            $t->string('document_path')->nullable();
            $t->unique(['user_id', 'course_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $t) {
            $t->dropUnique(['user_id', 'course_id', 'type']);
            $t->dropUnique(['verification_token']);
            $t->dropConstrainedForeignId('course_id');
            $t->dropColumn(['identity_snapshot', 'completion_snapshot', 'status', 'revoke_reason', 'revoked_at', 'verification_token', 'document_path']);
        });
        Schema::table('courses', fn (Blueprint $t) => $t->dropConstrainedForeignId('prerequisite_course_id'));
        Schema::dropIfExists('lms_grade_requests');
        Schema::dropIfExists('lms_grade_revisions');
        Schema::table('quiz_attempts', function (Blueprint $t) {
            $t->dropColumn(['grade_status', 'grade_version', 'published_revision']);
            $t->integer('score')->nullable()->change();
        });
        Schema::table('quizzes', function (Blueprint $t) {
            $t->dropConstrainedForeignId('target_course_id');
            $t->dropConstrainedForeignId('target_module_id');
            $t->dropColumn(['grading_mode', 'grading_policy', 'shuffle_questions', 'shuffle_options', 'placement', 'is_required']);
        });
        Schema::table('quiz_questions', fn (Blueprint $t) => $t->dropColumn(['question_type', 'answer_aliases', 'rubric']));
    }
};
