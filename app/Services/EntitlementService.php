<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsConfig;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EntitlementService
{
    /**
     * Check if user has access to a course.
     */
    public function canAccessCourse(User $user, Course $course): bool
    {
        // Free class = always accessible
        if ($course->is_free_class) {
            return true;
        }

        // Check entitlement for course type
        return Entitlement::where('user_id', $user->id)
            ->where('entitlement_type', 'course_access')
            ->where('entitlement_key', $course->type)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Check if user has access to a learning material.
     */
    public function canAccessMaterial(User $user, LearningMaterial $material): bool
    {
        $lesson = $material->lesson;
        if (!$lesson) {
            return false;
        }

        $course = $lesson->course;
        if (!$course) {
            return false;
        }

        return $this->canAccessCourse($user, $course);
    }

    /**
     * Grant course access entitlement (via subscription or purchase).
     */
    public function grantCourseAccess(
        User $user,
        string $courseType,
        ?string $expiresAt = null,
        ?string $source = 'subscription'
    ): Entitlement {
        return Entitlement::create([
            'user_id' => $user->id,
            'entitlement_type' => 'course_access',
            'entitlement_key' => $courseType,
            'expires_at' => $expiresAt,
            'source' => $source,
            'is_active' => true,
        ]);
    }

    /**
     * Revoke course access (e.g., subscription expired).
     * D-013: Certificates and progress remain; only future access blocked.
     */
    public function revokeCourseAccess(User $user, string $courseType): int
    {
        return Entitlement::where('user_id', $user->id)
            ->where('entitlement_type', 'course_access')
            ->where('entitlement_key', $courseType)
            ->update(['is_active' => false]);
    }

    /**
     * Get download manifest for a course (all downloadable PDFs + metadata).
     * Only accessible if user has entitlement.
     */
    public function getDownloadManifest(User $user, Course $course): array
    {
        if (!$this->canAccessCourse($user, $course)) {
            throw new \Exception('User does not have access to this course');
        }

        $materials = LearningMaterial::whereHas('lesson', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })
        ->where('type', 'pdf')
        ->whereNotNull('pdf_path')
        ->with('lesson')
        ->orderBy('created_at')
        ->get();

        $manifest = [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'type' => $course->type,
                'thumbnail' => $course->thumbnail_path,
            ],
            'generated_at' => now()->toIso8601String(),
            'total_materials' => $materials->count(),
            'materials' => $materials->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'lesson_id' => $m->lesson_id,
                'lesson_title' => $m->lesson->title ?? null,
                'pdf_url' => $m->pdf_path ? url('/api/v1/storage/' . $m->pdf_path) : null,
                'thumbnail' => $m->thumbnail_path,
                'file_size' => $m->metadata['file_size'] ?? null,
                'duration_minutes' => $m->duration_minutes,
            ])->toArray(),
        ];

        return $manifest;
    }

    /**
     * Check if user has passed Hardware Solution threshold (90%).
     * D-014: Only for Hardware Solution type courses.
     */
    public function hasPassedHardwareSolutionThreshold(User $user, Course $course): bool
    {
        if ($course->type !== Course::TYPE_HARDWARE_SOLUTION) {
            return false;
        }

        $threshold = LmsConfig::getValue(LmsConfig::KEY_HARDWARE_SOLUTION_THRESHOLD, 90);

        // Get all quizzes for this course
        $quizIds = Quiz::whereHas('learningMaterial.lesson', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->pluck('id');

        if ($quizIds->isEmpty()) {
            return false;
        }

        // Check passed quizzes count
        $passedCount = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->where('is_passed', true)
            ->distinct('quiz_id')
            ->count('quiz_id');

        $passRate = ($passedCount / $quizIds->count()) * 100;

        return $passRate >= $threshold;
    }

    /**
     * Issue KTA certificate after Hardware Solution threshold passed.
     * D-013: Certificate is permanent (never revoked even if subscription expires).
     */
    public function issueKtaCertificate(User $user, Course $course): Entitlement
    {
        if (!$this->hasPassedHardwareSolutionThreshold($user, $course)) {
            throw new \Exception('User has not passed Hardware Solution threshold');
        }

        // Check if certificate already issued
        $existing = Entitlement::where('user_id', $user->id)
            ->where('entitlement_type', 'certificate')
            ->where('entitlement_key', 'kta_' . $course->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Entitlement::create([
            'user_id' => $user->id,
            'entitlement_type' => 'certificate',
            'entitlement_key' => 'kta_' . $course->id,
            'expires_at' => null, // Permanent
            'source' => 'achievement',
            'is_active' => true,
            'metadata' => [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'issued_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
