<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Entitlement;
use App\Models\LearningMaterial;
use App\Models\LmsUpload;
use App\Models\User;

class EntitlementService
{
    /** @return array{has_access: bool, reason: string, expires_at: ?string, state: string} */
    public function accessForType(User $user, string $type): array
    {
        if ($type === Course::TYPE_FREE_CLASS) {
            return ['has_access' => true, 'reason' => 'free_class', 'state' => 'accessible', 'expires_at' => null];
        }
        // Prerequisite mapping and trusted completion are implemented in later batches.
        if ($type === Course::TYPE_HARDWARE_SOLUTION) {
            return ['has_access' => false, 'reason' => 'prerequisite_unavailable', 'state' => 'locked_prerequisite', 'expires_at' => null];
        }
        if (! in_array($type, [Course::TYPE_ANDROID, Course::TYPE_IPHONE], true)) {
            return ['has_access' => false, 'reason' => 'unsupported_course_type', 'state' => 'locked_entitlement', 'expires_at' => null];
        }
        $grants = $user->entitlements()->whereIn('package_type', [$type, Entitlement::PACKAGE_BUNDLING])->active()->get();
        $expiry = $grants->contains(fn ($grant) => $grant->expires_at === null)
            ? null : $grants->max('expires_at');

        $state = 'accessible';
        $latest = null;
        if ($grants->isEmpty()) {
            $latest = $user->entitlements()->whereIn('package_type', [$type, Entitlement::PACKAGE_BUNDLING])->latest('id')->first();
            $state = match (true) {
                $latest?->status === Entitlement::STATUS_REVOKED => 'revoked',
                $latest?->status === Entitlement::STATUS_EXPIRED,
                $latest?->expires_at !== null && $latest->expires_at->lessThanOrEqualTo(now()) => 'expired',
                $latest?->starts_at !== null && $latest->starts_at->isFuture() => 'not_started',
                default => 'locked_entitlement',
            };
        }

        return [
            'has_access' => $grants->isNotEmpty(), 'state' => $state,
            'reason' => $grants->isNotEmpty() ? 'active_entitlement' : $state,
            'expires_at' => $grants->isNotEmpty() ? ($expiry ? $expiry->toIso8601String() : null) : $latest?->expires_at?->toIso8601String(),
        ];
    }

    /** @return array{has_access: bool, reason: string, expires_at: ?string, state: string} */
    public function courseAccess(User $user, Course $course): array
    {
        if ($course->status !== 'published' || $course->archived_at !== null) {
            return ['has_access' => false, 'reason' => 'course_unpublished', 'state' => 'unavailable', 'expires_at' => null];
        }

        if ($course->type === Course::TYPE_HARDWARE_SOLUTION) {
            return app(LmsHardwareGate::class)->forHardwareCourse($user, $course);
        }

        return $this->accessForType($user, $course->type);
    }

    public function canAccessCourse(User $user, Course $course): bool
    {
        return $this->courseAccess($user, $course)['has_access'];
    }

    public function canAccessMaterial(User $user, LearningMaterial $material): bool
    {
        $lesson = $material->lesson;
        $course = $lesson?->course;

        if ($material->material_type === 'pdf_document' && (! $material->pdf_upload_id || ! LmsUpload::whereKey($material->pdf_upload_id)->where('status', 'ready')->exists())) {
            return false;
        }

        return $material->status === 'published' && $lesson?->status === 'published' && $course !== null
            && $this->canAccessCourse($user, $course) && app(LmsProgressService::class)->isUnlocked($user, $material);
    }

    public function grantCourseAccess(User $user, string $courseType, ?string $expiresAt = null, ?string $source = 'subscription'): Entitlement
    {
        if (! in_array($courseType, [Entitlement::PACKAGE_ANDROID, Entitlement::PACKAGE_IPHONE, Entitlement::PACKAGE_BUNDLING], true)) {
            throw new \InvalidArgumentException('Unsupported package');
        }

        return Entitlement::create([
            'user_id' => $user->id, 'package_type' => $courseType, 'status' => Entitlement::STATUS_ACTIVE,
            'starts_at' => now(), 'expires_at' => $expiresAt, 'source' => $source,
        ]);
    }

    public function revokeCourseAccess(User $user, string $courseType): int
    {
        return $user->entitlements()->where('package_type', $courseType)->update(['status' => Entitlement::STATUS_REVOKED]);
    }

    /** @return array<string, mixed> */
    public function getDownloadManifest(User $user, Course $course): array
    {
        abort_unless($this->canAccessCourse($user, $course), 403);
        $materials = LearningMaterial::published()->whereHas('lesson', fn ($q) => $q->where('course_id', $course->id))
            ->where('material_type', 'pdf_document')->whereNotNull('pdf_path')->get()->filter(fn ($m) => $this->canAccessMaterial($user, $m))->values();

        return [
            'course' => ['id' => $course->id, 'title' => $course->title, 'type' => $course->type],
            'generated_at' => now()->toIso8601String(),
            'total_materials' => $materials->count(),
            'materials' => $materials->map(fn ($m) => [
                'id' => $m->id, 'title' => $m->title, 'lesson_id' => $m->lesson_id,
                'pdf_url' => app(MediaProxyService::class)->generateProxyUrl('pdf', $m->id, $user->id),
            ])->all(),
        ];
    }

    public function hasPassedHardwareSolutionThreshold(User $user, Course $course): bool
    {
        return app(LmsHardwareGate::class)->forBase($user, $course)['has_access'];
    }

    public function issueKtaCertificate(User $user, Course $course): Entitlement
    {
        throw new \LogicException('Certificate issuance awaits the membership and graduation policy');
    }
}
