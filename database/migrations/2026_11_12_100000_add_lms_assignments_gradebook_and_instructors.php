<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('instructor_id')->nullable()->after('prerequisite_course_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(1)->after('passing_score');
        });

        Schema::create('lms_course_instructors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
        });

        Schema::create('lms_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('title', 160);
            $table->text('instructions');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(false);
            $table->decimal('max_score', 7, 2)->default(100);
            $table->decimal('passing_score', 5, 2)->default(70);
            $table->decimal('weight', 5, 2)->default(1);
            $table->json('rubric')->nullable();
            $table->json('allowed_mimes')->nullable();
            $table->unsignedSmallInteger('max_files')->default(5);
            $table->unsignedBigInteger('max_file_bytes')->default(20971520);
            $table->boolean('requires_text')->default(false);
            $table->boolean('requires_attachment')->default(false);
            $table->timestamp('due_at')->nullable();
            $table->boolean('late_submission_allowed')->default(false);
            $table->timestamp('late_until')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lms_assignment_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('text_body')->nullable();
            $table->json('upload_ids');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('allow_resubmit')->default(false);
            $table->timestamp('resubmit_deadline_at')->nullable();
            $table->text('last_return_feedback')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id']);
        });

        Schema::create('lms_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('lms_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('receipt_code', 40)->unique();
            $table->uuid('request_id');
            $table->string('payload_hash', 64);
            $table->text('text_body')->nullable();
            $table->json('files_snapshot');
            $table->json('assignment_snapshot');
            $table->string('status', 20)->default('submitted');
            $table->string('grade_status', 20)->default('pending_review');
            $table->unsignedInteger('grade_version')->default(0);
            $table->unsignedInteger('published_revision')->nullable();
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->boolean('is_passed')->default(false);
            $table->boolean('is_late')->default(false);
            $table->timestamp('submitted_at');
            $table->timestamp('returned_at')->nullable();
            $table->text('return_feedback')->nullable();
            $table->boolean('resubmit_allowed')->default(false);
            $table->timestamp('resubmit_deadline_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'user_id', 'version']);
            $table->unique(['user_id', 'request_id']);
        });

        Schema::create('lms_submission_grade_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('lms_assignment_submissions')->cascadeOnDelete();
            $table->unsignedInteger('revision');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->boolean('passed')->default(false);
            $table->json('criteria')->nullable();
            $table->text('feedback');
            $table->text('reason');
            $table->boolean('allow_resubmit')->default(false);
            $table->timestamp('resubmit_deadline_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'revision']);
        });

        Schema::create('lms_submission_requests', function (Blueprint $table) {
            $table->id();
            $table->string('scope_key', 120);
            $table->uuid('request_id');
            $table->string('payload_hash', 64);
            $table->json('response_json')->nullable();
            $table->timestamps();
            $table->unique(['scope_key', 'request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_submission_requests');
        Schema::dropIfExists('lms_submission_grade_revisions');
        Schema::dropIfExists('lms_assignment_submissions');
        Schema::dropIfExists('lms_assignment_drafts');
        Schema::dropIfExists('lms_assignments');
        Schema::dropIfExists('lms_course_instructors');
        Schema::table('quizzes', fn (Blueprint $table) => $table->dropColumn('weight'));
        Schema::table('courses', fn (Blueprint $table) => $table->dropConstrainedForeignId('instructor_id'));
    }
};
