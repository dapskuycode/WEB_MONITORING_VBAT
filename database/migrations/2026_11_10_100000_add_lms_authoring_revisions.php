<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique();
            $table->unsignedInteger('revision')->default(1);
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique();
            $table->text('description')->nullable();
            // Preserve visibility of legacy modules; new authoring explicitly creates Draft.
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('revision')->default(1);
        });
        Schema::table('learning_materials', function (Blueprint $table) {
            $table->boolean('is_preview')->default(false);
            $table->unsignedInteger('revision')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('learning_materials', fn (Blueprint $table) => $table->dropColumn(['is_preview', 'revision']));
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'description', 'status', 'revision']);
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'revision']);
        });
    }
};
