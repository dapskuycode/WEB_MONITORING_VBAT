<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MediaAuthorizationService
{
    /**
     * Check if user can access specific material
     * - Free Class → anyone (public)
     * - Android/iPhone → paid entitlement
     * - Hardware Solution → paid + 90% quiz threshold
     */
    public function canAccessMaterial(User $user, LearningMaterial $material): bool
    {
        $course = $material->lesson?->course;

        if (!$course) {
            return false;
        }

        // Free Class always accessible
        if ($course->type === 'free_class') {
            return true;
        }

        // Check entitlement for paid courses
        $hasEntitlement = Entitlement::where('user_id', $user->id)
            ->where('source_type', 'payment_package')
            ->whereJsonContains('metadata->course_type', $course->type)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->where('is_active', true)
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (!$hasEntitlement) {
            return false;
        }

        // Hardware Solution requires 90% quiz completion
        if ($course->type === 'hardware_solution') {
            return $this->checkHardwareSolutionThreshold($user, $course);
        }

        return true;
    }

    /**
     * Check if user passed Hardware Solution quiz threshold (90%)
     */
    public function checkHardwareSolutionThreshold(User $user, Course $course): bool
    {
        $totalQuizzes = $course->quizzes()->count();
        if ($totalQuizzes === 0) {
            return true; // No quizzes = auto pass
        }

        // Get passing score threshold from lms_configs (default 90%)
        $threshold = ltrim(config('lms.hardware_solution_threshold', '90'), '%');
        $threshold = (float) $threshold;

        // Count completed quizzes (attempts with score >= threshold)
        $completedQuizzes = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->whereIn('quiz_id', $course->quizzes()->pluck('id'))
            ->where('score', '>=', $threshold)
            ->distinct('quiz_id')
            ->count();

        return $completedQuizzes >= $totalQuizzes;
    }

    /**
     * Get allowed material types per entitlement source
     */
    public function getAllowedMaterialTypes(?string $sourceType = null): array
    {
        return match ($sourceType) {
            'payment_package' => ['video', 'pdf', 'document'],
            'manual', 'sponsor_product' => ['video', 'pdf', 'document'],
            null => ['free_class', 'video', 'pdf', 'document'],
            default => ['video', 'pdf', 'document'],
        };
    }

    /**
     * Get entitlement-based access tier
     */
    public function getAccessTier(User $user, LearningMaterial $material): string
    {
        $course = $material->lesson?->course;

        if (!$course) {
            return 'none';
        }

        // Free Class
        if ($course->type === 'free_class') {
            return 'free';
        }

        // Check entitlement
        $entitlement = Entitlement::where('user_id', $user->id)
            ->where('source_type', 'payment_package')
            ->whereJsonContains('metadata->course_type', $course->type)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->where('is_active', true)
                  ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$entitlement) {
            return 'none';
        }

        // Hardware Solution requires quiz pass
        if ($course->type === 'hardware_solution') {
            if (!$this->checkHardwareSolutionThreshold($user, $course)) {
                return 'qualified_pending';
            }
            return 'qualified';
        }

        return 'premium';
    }
}
