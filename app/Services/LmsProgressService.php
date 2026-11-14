<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentSubmission;
use App\Models\LmsUpload;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LmsProgressService
{
    /** @return array<string, mixed> */
    public function curriculum(User $user, Course $course): array
    {
        $materials = LearningMaterial::published()->whereHas('lesson', fn ($q) => $q->where('course_id', $course->id)->where('status', 'published'))
            ->with(['lesson', 'quizzes'])->get()->sortBy(fn ($m) => sprintf('%010d-%010d-%010d-%010d', $m->lesson->sort_order, $m->lesson_id, $m->sort_order, $m->id));
        $progress = DB::table('lms_playback_progress as p')->join('lms_playback_sources as s', function ($join) {
            $join->on('s.material_id', '=', 'p.material_id')->on('s.content_hash', '=', 'p.content_hash');
        })->where('p.user_id', $user->id)->whereIn('p.material_id', $materials->pluck('id'))
            ->select('p.*', 's.video_id', 's.duration_ms')->get()->keyBy('material_id');
        $uploads = LmsUpload::whereIn('id', $materials->pluck('pdf_upload_id')->filter())->get()->keyBy('id');
        $reading = DB::table('lms_reading_progress')->where('user_id', $user->id)->whereIn('material_id', $materials->pluck('id'))->get()->keyBy(fn ($p) => $p->material_id.':'.$p->source_hash);
        $placement = app(LmsQuizPlacementService::class)->slots($course, $materials);
        $quizIds = [];
        foreach ($placement['slots'] as $slots) {
            foreach ($slots as $slot) {
                $quizIds[] = $slot['quiz']->id;
            }
        }
        $grades = QuizAttempt::where('user_id', $user->id)->whereIn('quiz_id', $quizIds)
            ->whereIn('status', ['submitted', 'expired'])->get();
        $courseAssignments = LmsAssignment::where('course_id', $course->id)
            ->whereIn('status', ['published', 'closed'])
            ->whereHas('lesson', fn ($q) => $q->where('status', 'published'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('lesson_id');
        $assignmentSubs = LmsAssignmentSubmission::where('user_id', $user->id)
            ->whereIn('assignment_id', $courseAssignments->flatten()->pluck('id'))
            ->orderByDesc('version')
            ->get()
            ->groupBy('assignment_id');
        $states = [];
        foreach ($materials as $material) {
            $row = $progress->get($material->id);
            $row = $row && $row->video_id === $material->youtube_video_id && $material->material_type === 'youtube_video' ? $row : null;
            $upload = $uploads->get($material->pdf_upload_id);
            $pdfReady = $material->material_type === 'pdf_document' && $upload?->status === 'ready';
            $pdf = $pdfReady ? $reading->get($material->id.':'.$upload->sha256) : null;
            $states[$material->id] = ['row' => $row, 'upload' => $upload, 'pdfReady' => $pdfReady, 'pdf' => $pdf,
                'source_hash' => $row->content_hash ?? ($pdfReady ? $upload->sha256 : null), 'done' => $row?->completed_at !== null || $pdf?->completed_at !== null];
        }
        $quizSlots = [];
        $quizzes = [];
        $assignments = [];
        $processedAssignmentLessons = [];
        $materialsByLesson = $materials->groupBy('lesson_id');
        $unlocked = true;
        $units = [];
        $required = $placement['missing'];
        $completed = 0;
        $next = null;
        $nextChosen = false;
        $modules = [];
        foreach ($materials as $material) {
            $modules[$material->lesson_id] ??= ['required_units' => 0, 'completed_units' => 0];
            $state = $states[$material->id];
            $row = $state['row'];
            $upload = $state['upload'];
            $pdfReady = $state['pdfReady'];
            $pdf = $state['pdf'];
            $sourceHash = $state['source_hash'];
            $done = $state['done'];
            $snapshot = ['available' => $material->material_type === 'youtube_video' || $pdfReady,
                'percent' => $row ? round($row->covered_ms / $row->duration_ms * 100, 2) : ($pdf ? round($pdf->max_page / $upload->page_count * 100, 2) : 0),
                'completed' => $done, 'resume_position' => $row ? $row->resume_ms / 1000 : 0,
                'resume_page' => $pdf?->last_page, 'reached_end' => $pdf?->reached_end_at !== null];
            $units[$material->id] = ['unlocked' => $unlocked || $row !== null || $pdf !== null,
                'source_available' => $material->material_type === 'youtube_video' || $pdfReady, 'progress' => $snapshot];
            if ($material->is_required) {
                $required++;
                $completed += $done ? 1 : 0;
                $modules[$material->lesson_id]['required_units']++;
                $modules[$material->lesson_id]['completed_units'] += $done ? 1 : 0;
            }
            if (! $nextChosen && ! $done && $material->is_required) {
                $next = ['kind' => 'material', 'id' => $material->id, 'course_id' => $course->id, 'module_id' => $material->lesson_id];
                $nextChosen = true;
            }
            if ($material->is_required && ! $done) {
                $unlocked = false;
            }
            $hasLegacyQuiz = false;
            foreach ($placement['slots'][$material->id] ?? [] as $slot) {
                $quiz = $slot['quiz'];
                if ($quiz->id === $material->quiz_id) {
                    $hasLegacyQuiz = true;
                }
                $ids = $slot['scope_ids'];
                $hashes = array_map(fn ($id) => [$id, $states[$id]['source_hash']], $ids);
                $hasSources = count($ids) > 0 && count(array_filter($hashes, fn ($pair) => $pair[1] === null)) === 0;
                $context = ! $hasSources ? null : ($slot['placement'] === 'after_material' ? $sourceHash : hash('sha256', json_encode([$quiz->id, $slot['placement'], $hashes], JSON_THROW_ON_ERROR)));
                $results = $context ? $grades->filter(fn ($g) => $g->quiz_id === $quiz->id && $g->source_hash === $context) : collect();
                $outcome = app(QuizOutcomeService::class)->summarize($results);
                $passed = $outcome['passed'];
                $scopeDone = count($ids) > 0 && count(array_filter($ids, fn ($id) => ! $states[$id]['done'])) === 0;
                $open = $quiz->is_active && (($unlocked && $scopeDone) || $passed);
                $quizzes[$quiz->id] = ['unlocked' => $open, 'source_hash' => $context, 'module_id' => $material->lesson_id,
                    'progress' => ['available' => true, 'completed' => $passed, 'percent' => $passed ? 100 : 0, 'best_score' => $outcome['score'],
                        'pending_review' => $results->contains('grade_status', 'pending_review')]];
                if ($quiz->is_active) {
                    $quizSlots[$material->id][] = ['id' => $quiz->id, 'title' => $quiz->title, 'is_required' => $slot['required'], 'placement' => $slot['placement']];
                }
                if ($slot['required']) {
                    $required++;
                    $completed += $passed ? 1 : 0;
                    if ($slot['placement'] !== 'end_of_course') {
                        $modules[$material->lesson_id]['required_units']++;
                        $modules[$material->lesson_id]['completed_units'] += $passed ? 1 : 0;
                    }
                    if (! $nextChosen && ! $passed) {
                        $next = $quiz->is_active ? ['kind' => 'quiz', 'id' => $quiz->id, 'course_id' => $course->id, 'module_id' => $material->lesson_id] : null;
                        $nextChosen = true;
                    }
                    if (! $passed) {
                        $unlocked = false;
                    }
                }
            }
            if ($material->quiz_required && ! $hasLegacyQuiz) {
                $required++;
                $modules[$material->lesson_id]['required_units']++;
                $unlocked = false;
                if (! $nextChosen) {
                    $nextChosen = true;
                    $next = null;
                }
            }
            $lastInLesson = ($materialsByLesson->get($material->lesson_id)?->last()?->id === $material->id);
            if ($lastInLesson && ! isset($processedAssignmentLessons[$material->lesson_id])) {
                $processedAssignmentLessons[$material->lesson_id] = true;
                foreach ($courseAssignments->get($material->lesson_id, collect()) as $asg) {
                    $latestSub = ($assignmentSubs->get($asg->id) ?? collect())->first();
                    $asgPassed = $latestSub && $latestSub->grade_status === 'published' && (bool) $latestSub->is_passed;
                    $asgPending = $latestSub && $latestSub->grade_status === 'pending_review';
                    $asgReturned = $latestSub && $latestSub->status === 'returned';
                    $asgOpen = $unlocked || $latestSub !== null;
                    $assignments[$asg->id] = [
                        'unlocked' => $asgOpen,
                        'module_id' => $asg->lesson_id,
                        'progress' => [
                            'available' => true,
                            'completed' => (bool) $asgPassed,
                            'percent' => $asgPassed ? 100 : ($asgPending ? 50 : 0),
                            'best_score' => ($latestSub && $latestSub->grade_status === 'published' && $latestSub->score_percent !== null) ? (float) $latestSub->score_percent : null,
                            'pending_review' => (bool) $asgPending,
                            'returned' => (bool) $asgReturned,
                        ],
                    ];
                    if ($asg->is_required) {
                        $required++;
                        $completed += $asgPassed ? 1 : 0;
                        $modules[$asg->lesson_id]['required_units']++;
                        $modules[$asg->lesson_id]['completed_units'] += $asgPassed ? 1 : 0;
                        if (! $nextChosen && ! $asgPassed) {
                            $next = ['kind' => 'assignment', 'id' => $asg->id, 'course_id' => $course->id, 'module_id' => $asg->lesson_id];
                            $nextChosen = true;
                        }
                        if (! $asgPassed && ! $asgPending) {
                            $unlocked = false;
                        }
                    }
                }
            }
        }

        foreach ($courseAssignments as $lessonId => $lessonAssignments) {
            if (isset($processedAssignmentLessons[$lessonId])) {
                continue;
            }
            $modules[$lessonId] ??= ['required_units' => 0, 'completed_units' => 0];
            foreach ($lessonAssignments as $asg) {
                $latestSub = ($assignmentSubs->get($asg->id) ?? collect())->first();
                $asgPassed = $latestSub && $latestSub->grade_status === 'published' && (bool) $latestSub->is_passed;
                $asgPending = $latestSub && $latestSub->grade_status === 'pending_review';
                $asgReturned = $latestSub && $latestSub->status === 'returned';
                $asgOpen = $unlocked || $latestSub !== null;
                $assignments[$asg->id] = [
                    'unlocked' => $asgOpen,
                    'module_id' => $asg->lesson_id,
                    'progress' => [
                        'available' => true,
                        'completed' => (bool) $asgPassed,
                        'percent' => $asgPassed ? 100 : ($asgPending ? 50 : 0),
                        'best_score' => ($latestSub && $latestSub->grade_status === 'published' && $latestSub->score_percent !== null) ? (float) $latestSub->score_percent : null,
                        'pending_review' => (bool) $asgPending,
                        'returned' => (bool) $asgReturned,
                    ],
                ];
                if ($asg->is_required) {
                    $required++;
                    $completed += $asgPassed ? 1 : 0;
                    $modules[$asg->lesson_id]['required_units']++;
                    $modules[$asg->lesson_id]['completed_units'] += $asgPassed ? 1 : 0;
                    if (! $nextChosen && ! $asgPassed) {
                        $next = ['kind' => 'assignment', 'id' => $asg->id, 'course_id' => $course->id, 'module_id' => $asg->lesson_id];
                        $nextChosen = true;
                    }
                    if (! $asgPassed && ! $asgPending) {
                        $unlocked = false;
                    }
                }
            }
        }

        foreach ($modules as &$module) {
            $module['available'] = true;
            $module['percent'] = $module['required_units'] ? round($module['completed_units'] / $module['required_units'] * 100, 2) : 0;
        }

        return ['quiz_slots' => $quizSlots, 'units' => $units, 'quizzes' => $quizzes, 'assignments' => $assignments, 'modules' => $modules, 'next_unit' => $next, 'next_unit_reason' => $next ? 'next_required_activity' : ($nextChosen ? 'curriculum_unavailable' : 'no_next_activity'),
            'progress' => ['available' => true, 'percent' => $required ? round($completed / $required * 100, 2) : 0,
                'completed_units' => $completed, 'required_units' => $required, 'completed' => $required > 0 && $completed === $required]];
    }

    public function isUnlocked(User $user, LearningMaterial $material): bool
    {
        return $this->curriculum($user, $material->lesson->course)['units'][$material->id]['unlocked'] ?? false;
    }
}
