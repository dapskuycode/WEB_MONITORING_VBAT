<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Course type for Free Class / Android / iPhone / Hardware Solution
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('type', ['free_class', 'android', 'iphone', 'hardware_solution'])
                  ->default('android')
                  ->after('status');
            $table->boolean('is_free_class')->default(false)->after('type');
            $table->timestamp('published_at')->nullable()->after('is_free_class');
            $table->timestamp('archived_at')->nullable()->after('published_at');
        });

        // 2. Quiz model
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('max_attempts')->default(5);
            $table->integer('passing_score')->default(70);
            $table->integer('time_limit_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->json('options'); // array of {label, value}
            $table->string('correct_answer');
            $table->integer('points')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('score')->nullable();
            $table->boolean('is_passed')->default(false);
            $table->json('answers')->nullable(); // {question_id: answer_value}
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        // 3. Learning Bookmark (isolated from wishlist)
        Schema::create('learning_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('bookmarkable'); // learning_material, course, lesson
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
        });

        // 4. Entitlement (package access)
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('package_type'); // android, iphone, bundling, hardware_solution, premium
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // active, expired, revoked
            $table->string('source')->default('purchase'); // purchase, manual_grant, trial
            $table->string('transaction_reference')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'package_type', 'status']);
        });

        // 5. Hardware Solution threshold config (Admin configurable)
        Schema::create('lms_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_configs');
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('learning_bookmarks');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['type', 'is_free_class', 'published_at', 'archived_at']);
        });
    }
};
