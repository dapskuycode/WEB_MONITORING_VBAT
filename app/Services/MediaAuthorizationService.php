<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\User;

class MediaAuthorizationService
{
    public function __construct(private EntitlementService $entitlements) {}

    public function canAccessMaterial(User $user, LearningMaterial $material): bool
    {
        return $this->entitlements->canAccessMaterial($user, $material);
    }

    public function checkHardwareSolutionThreshold(User $user, Course $course): bool
    {
        return $this->entitlements->hasPassedHardwareSolutionThreshold($user, $course);
    }

    public function getAccessTier(User $user, LearningMaterial $material): string
    {
        if (! $this->canAccessMaterial($user, $material)) {
            return 'none';
        }

        return $material->lesson->course->type === Course::TYPE_FREE_CLASS ? 'free' : 'premium';
    }
}
