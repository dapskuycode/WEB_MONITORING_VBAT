<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;

class LmsOutcomeService
{
    /**
     * @return array<string, mixed>
     */
    public function course(User $user, Course $course): array
    {
        abort_unless(in_array($course->type, ['android', 'iphone', 'free_class'], true), 404);
        $access = app(EntitlementService::class)->courseAccess($user, $course);
        $curriculum = app(LmsProgressService::class)->curriculum($user, $course);
        $hardware = app(LmsHardwareGate::class)->forBase($user, $course);
        $targets = Course::published()->where('type', 'hardware_solution')->where('prerequisite_course_id', $course->id)->get(['id', 'title']);

        return ['course' => ['id' => $course->id, 'title' => $course->title, 'type' => $course->type], 'access' => $access, 'progress' => $curriculum['progress'],
            'next_unit' => $access['has_access'] ? $curriculum['next_unit'] : null, 'hardware' => [...$hardware, 'courses' => $hardware['has_access'] ? $targets->toArray() : []],
            'can_issue_certificate' => $access['has_access'] && $curriculum['progress']['completed'],
            'certificate_id' => Certificate::where('user_id', $user->id)->where('course_id', $course->id)->where('type', 'course_completion')->value('id')];
    }

    /**
     * @return array<string, mixed>
     */
    public function history(User $user, int $page): array
    {
        $attempts = QuizAttempt::where('user_id', $user->id)->whereIn('status', ['submitted', 'expired', 'invalidated'])->latest('id')->paginate(20, ['*'], 'page', $page);

        return ['items' => $attempts->map(fn ($a) => ['id' => $a->id, 'quiz_id' => $a->quiz_id, 'title' => $a->snapshot['title'], 'status' => $a->status, 'grade_status' => $a->grade_status,
            'score' => $a->grade_status === 'published' ? $a->score : null, 'passed' => $a->grade_status === 'published' && $a->is_passed,
            'submitted_at' => $a->submitted_at?->toIso8601String(), 'feedback' => app(LmsGradingService::class)->feedback($a)])->all(),
            'page' => $attempts->currentPage(), 'last_page' => $attempts->lastPage()];
    }
}
