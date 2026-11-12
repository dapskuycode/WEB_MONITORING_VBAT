<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\User;

class LmsHardwareGate
{
    /**
     * @return array<string, mixed>
     */
    public function forBase(User $user, Course $course): array
    {
        $closed = fn ($reason, $state = 'locked_prerequisite') => ['has_access' => false, 'reason' => $reason, 'state' => $state, 'expires_at' => null];
        if (! in_array($course->type, [Course::TYPE_ANDROID, Course::TYPE_IPHONE], true)) {
            return $closed('prerequisite_unavailable');
        }
        $access = app(EntitlementService::class)->courseAccess($user, $course);
        if (! $access['has_access']) {
            return $closed($access['reason'], $access['state']);
        }
        if (! app(LmsProgressService::class)->curriculum($user, $course)['progress']['completed']) {
            return $closed('course_incomplete');
        }
        $premium = $user->entitlements()->where('package_type', Entitlement::PACKAGE_PREMIUM)->active()->get();
        if ($premium->isEmpty()) {
            return $closed('premium_required', 'locked_entitlement');
        }
        $premiumExpiry = $premium->contains(fn ($g) => $g->expires_at === null) ? null : $premium->max('expires_at')?->toIso8601String();
        $ends = array_filter([$access['expires_at'], $premiumExpiry]);
        sort($ends);

        return ['has_access' => true, 'reason' => 'course_complete_and_premium_active', 'state' => 'accessible', 'expires_at' => $ends[0] ?? null];
    }

    /**
     * @return array<string, mixed>
     */
    public function forHardwareCourse(User $user, Course $hardware): array
    {
        $source = $hardware->prerequisite_course_id ? Course::find($hardware->prerequisite_course_id) : null;

        return $source ? $this->forBase($user, $source) : ['has_access' => false, 'reason' => 'prerequisite_unavailable', 'state' => 'locked_prerequisite', 'expires_at' => null];
    }
}
