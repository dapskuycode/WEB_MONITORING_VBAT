<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LmsAssignment;
use App\Models\LmsAssignmentSubmission;
use App\Services\LmsAssignmentService;
use App\Services\LmsAuthoringService;
use App\Services\LmsGradebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LmsAssignmentController extends Controller
{
    public function showStudent(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        $request->validate(['user_id' => 'prohibited']);

        return response()->json([
            'success' => true,
            'data' => $service->detailForStudent($request->user(), $id),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function saveDraft(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->saveDraft($request->user(), $id, $request->all()),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function submit(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->submit($request->user(), $id, $request->all()),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function downloadFile(Request $request, int $id, string $uploadId, LmsAssignmentService $service): StreamedResponse
    {
        return $service->downloadFile($request->user(), $id, $uploadId);
    }

    public function studentCourseGrades(Request $request, int $id, LmsGradebookService $gradebook): JsonResponse
    {
        $request->validate(['user_id' => 'prohibited']);
        $course = Course::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $gradebook->studentGrades($request->user(), $course),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function indexAdmin(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless((bool) $user?->isLmsManager(), 403);
        $request->validate([
            'course_id' => 'sometimes|integer',
            'lesson_id' => 'sometimes|integer',
            'status' => 'sometimes|in:draft,published,closed,archived',
        ]);

        $query = LmsAssignment::query()->with(['course:id,title,code', 'lesson:id,title,course_id']);
        if (! $user->isSuperAdmin()) {
            $query->whereIn('course_id', $user->managedCourseIds());
        }
        if ($request->filled('course_id')) {
            $cid = (int) $request->input('course_id');
            abort_unless($user->canManageCourse($cid), 403);
            $query->where('course_id', $cid);
        }
        if ($request->filled('lesson_id')) {
            $query->where('lesson_id', (int) $request->input('lesson_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $items = $query->orderBy('course_id')->orderBy('lesson_id')->orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function storeAdmin(Request $request, LmsAssignmentService $service): JsonResponse
    {
        $assignment = $service->saveAssignment($request->user(), $request->all());

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ], 201)->header('Cache-Control', 'private, no-store');
    }

    public function updateAdmin(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        $assignment = $service->saveAssignment($request->user(), $request->all(), $id);

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function transitionStatus(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:draft,published,closed,archived',
            'expected_revision' => 'required|integer|min:1',
        ]);
        $assignment = $service->transitionStatus($request->user(), $id, (string) $data['status'], (int) $data['expected_revision']);

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function indexSubmissions(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless((bool) $user?->isLmsManager(), 403);
        $request->validate([
            'course_id' => 'sometimes|integer',
            'assignment_id' => 'sometimes|integer',
            'status' => 'sometimes|in:pending_review,published,returned',
            'page' => 'sometimes|integer|min:1',
        ]);

        $query = LmsAssignmentSubmission::with(['assignment:id,course_id,lesson_id,title,code,max_score', 'user:id,name,email']);
        if (! $user->isSuperAdmin()) {
            $courseIds = $user->managedCourseIds();
            $query->whereHas('assignment', fn ($q) => $q->whereIn('course_id', $courseIds));
        }
        if ($request->filled('course_id')) {
            $cid = (int) $request->input('course_id');
            abort_unless($user->canManageCourse($cid), 403);
            $query->whereHas('assignment', fn ($q) => $q->where('course_id', $cid));
        }
        if ($request->filled('assignment_id')) {
            $aid = (int) $request->input('assignment_id');
            $asg = LmsAssignment::findOrFail($aid);
            abort_unless($user->canManageCourse($asg->course_id), 403);
            $query->where('assignment_id', $aid);
        }
        if ($request->filled('status')) {
            $st = $request->string('status')->toString();
            if ($st === 'returned') {
                $query->where('status', 'returned');
            } else {
                $query->where('grade_status', $st);
            }
        }

        $items = $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (LmsAssignmentSubmission $s) => [
                'id' => $s->id,
                'assignment_id' => $s->assignment_id,
                'assignment_title' => $s->assignment?->title ?? ($s->assignment_snapshot['title'] ?? ''),
                'course_id' => $s->assignment?->course_id,
                'user_id' => $s->user_id,
                'user' => $s->user ? ['id' => $s->user->id, 'name' => $s->user->name, 'email' => $s->user->email] : null,
                'version' => (int) $s->version,
                'receipt_code' => $s->receipt_code,
                'status' => $s->status,
                'grade_status' => $s->grade_status,
                'grade_version' => (int) $s->grade_version,
                'score' => $s->score !== null ? (float) $s->score : null,
                'score_percent' => $s->score_percent !== null ? (float) $s->score_percent : null,
                'is_passed' => (bool) $s->is_passed,
                'is_late' => (bool) $s->is_late,
                'submitted_at' => $s->submitted_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function showSubmission(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->submissionDetail($request->user(), $id),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function draftGrade(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->gradeSubmission($request->user(), $id, $request->all(), false),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function publishGrade(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->gradeSubmission($request->user(), $id, $request->all(), true),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function returnSubmission(Request $request, int $id, LmsAssignmentService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->returnSubmission($request->user(), $id, $request->all()),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function courseGradebook(Request $request, int $id, LmsGradebookService $gradebook): JsonResponse
    {
        $request->validate([
            'q' => 'sometimes|nullable|string|max:120',
            'status' => 'sometimes|nullable|in:all,pending_review,published,returned,not_submitted',
        ]);
        $course = Course::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $gradebook->courseGradebook($request->user(), $course, $request->only(['q', 'status'])),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function bulkPublishGradebook(Request $request, LmsGradebookService $gradebook): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $gradebook->bulkPublish($request->user(), $request->all()),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function assignInstructors(Request $request, int $id, LmsAuthoringService $authoring): JsonResponse
    {
        $data = $request->validate([
            'instructor_id' => 'nullable|integer|exists:users,id',
            'co_instructor_ids' => 'sometimes|array|max:20',
            'co_instructor_ids.*' => 'integer|exists:users,id',
        ]);
        $course = Course::findOrFail($id);
        $updated = $authoring->assignInstructors(
            $course,
            isset($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            $data['co_instructor_ids'] ?? []
        );

        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $updated->id,
                'instructor_id' => $updated->instructor_id,
                'co_instructor_ids' => $updated->coInstructors->pluck('id')->map(fn ($uid) => (int) $uid)->values()->all(),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }
}
