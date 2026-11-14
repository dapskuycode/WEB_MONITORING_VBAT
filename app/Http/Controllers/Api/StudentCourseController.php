<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Entitlement;
use App\Models\User;
use App\Services\EntitlementService;
use App\Services\LmsProgressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentCourseController extends Controller
{
    public function __construct(private EntitlementService $access, private LmsProgressService $progress) {}

    private function validateIdentity(Request $request): void
    {
        $request->validate(['user_id' => 'prohibited', 'email' => 'prohibited']);
    }

    public function index(Request $request): JsonResponse
    {
        $this->validateIdentity($request);
        $data = $request->validate(['scope' => 'sometimes|in:mine,catalog', 'per_page' => 'sometimes|integer|min:1|max:50', 'page' => 'sometimes|integer|min:1']);
        $scope = $data['scope'] ?? 'mine';
        $query = $this->visibleCourses();
        if ($scope === 'mine') {
            $packages = $request->user()->entitlements()->pluck('package_type')->all();
            $types = array_values(array_intersect(Course::TYPES, $packages));
            if (in_array(Entitlement::PACKAGE_BUNDLING, $packages, true)) {
                $types = array_unique([...$types, 'android', 'iphone']);
            }
            $query->where(function (Builder $q) use ($types, $request) {
                $q->whereIn('type', $types)->orWhere(fn ($q) => $q->where('type', Course::TYPE_HARDWARE_SOLUTION)->whereIn('prerequisite_course_id', Course::whereIn('type', $types)->select('id')))->orWhere(function (Builder $q) use ($request) {
                    $q->where('type', Course::TYPE_FREE_CLASS)->where(function ($q) use ($request) {
                        $q->whereHas('lessons.learningMaterials.views', fn ($q) => $q->where('user_id', $request->user()->id))
                            ->orWhereHas('lessons.learningMaterials', fn ($q) => $q->whereIn('id', DB::table('lms_playback_progress')->where('user_id', $request->user()->id)->select('material_id')));
                    });
                });
            });
        }
        $courses = $query->orderBy('id')->paginate($data['per_page'] ?? 20);
        $access = [];
        foreach (Course::TYPES as $type) {
            $access[$type] = $this->access->accessForType($request->user(), $type);
        }

        return response()->json(['success' => true, 'data' => $courses->map(fn ($course) => $this->courseData($course, $course->type === Course::TYPE_HARDWARE_SOLUTION ? $this->access->courseAccess($request->user(), $course) : $access[$course->type], $request->user()))->all(),
            'meta' => ['scope' => $scope, 'current_page' => $courses->currentPage(), 'last_page' => $courses->lastPage(), 'total' => $courses->total()],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->validateIdentity($request);
        $course = $this->visibleCourses()->findOrFail($id);
        $access = $this->access->courseAccess($request->user(), $course);
        $curriculum = $this->progress->curriculum($request->user(), $course);
        $modules = [];
        $first = null;
        foreach ($course->lessons as $lesson) {
            $activities = [];
            foreach ($lesson->learningMaterials as $material) {
                $unit = $curriculum['units'][$material->id];
                $open = $access['has_access'] && $unit['unlocked'] && $unit['source_available'];
                $activity = ['kind' => 'material', 'id' => $material->id, 'course_id' => $course->id, 'module_id' => $lesson->id,
                    'title' => $material->title, 'material_type' => $material->material_type, 'sort_order' => $material->sort_order,
                    'is_required' => $material->is_required, 'is_preview' => $material->is_preview,
                    'duration_seconds' => $material->duration_seconds > 0 ? $material->duration_seconds : null,
                    'can_open' => $open, 'reason' => ! $access['has_access'] ? $access['reason'] : (! $unit['unlocked'] ? 'prerequisite_incomplete' : ($open ? 'accessible' : ($material->material_type === 'pdf_document' ? 'document_not_ready' : 'reader_unavailable'))),
                    'progress' => $unit['progress']];
                if ($first === null) {
                    $first = $activity;
                }
                $activities[] = $activity;
                foreach ($curriculum['quiz_slots'][$material->id] ?? [] as $quiz) {
                    $quizUnit = $curriculum['quizzes'][$quiz['id']];
                    $quizOpen = $access['has_access'] && $quizUnit['unlocked'];
                    $activities[] = ['kind' => 'quiz', 'id' => $quiz['id'], 'course_id' => $course->id, 'module_id' => $lesson->id,
                        'material_id' => $material->id, 'title' => $quiz['title'], 'placement' => $quiz['placement'], 'can_open' => $quizOpen, 'is_required' => $quiz['is_required'],
                        'reason' => ! $access['has_access'] ? $access['reason'] : ($quizOpen ? 'accessible' : 'prerequisite_incomplete'), 'progress' => $quizUnit['progress']];
                }
            }
            foreach ($lesson->assignments as $assignment) {
                $asgUnit = $curriculum['assignments'][$assignment->id] ?? ['unlocked' => false, 'progress' => ['available' => true, 'completed' => false, 'percent' => 0]];
                $asgOpen = $access['has_access'] && $asgUnit['unlocked'];
                $activity = [
                    'kind' => 'assignment',
                    'id' => $assignment->id,
                    'course_id' => $course->id,
                    'module_id' => $lesson->id,
                    'title' => $assignment->title,
                    'sort_order' => $assignment->sort_order,
                    'is_required' => (bool) $assignment->is_required,
                    'due_at' => $assignment->due_at?->toIso8601String(),
                    'can_open' => $asgOpen,
                    'reason' => ! $access['has_access'] ? $access['reason'] : ($asgOpen ? 'accessible' : 'prerequisite_incomplete'),
                    'progress' => $asgUnit['progress'],
                ];
                if ($first === null) {
                    $first = $activity;
                }
                $activities[] = $activity;
            }
            $modules[] = ['id' => $lesson->id, 'title' => $lesson->title, 'description' => $lesson->description, 'sort_order' => $lesson->sort_order,
                'revision' => $lesson->revision, 'activities' => $activities,
                'progress' => $curriculum['modules'][$lesson->id] ?? ['available' => true, 'percent' => 0, 'required_units' => 0, 'completed_units' => 0]];
        }

        return response()->json(['success' => true, 'data' => [
            'course' => $this->courseData($course, $access, $request->user(), $curriculum), 'modules' => $modules,
            'first_activity' => ($first['can_open'] ?? false) ? array_intersect_key($first, array_flip(['kind', 'id', 'course_id', 'module_id'])) : null,
            'next_unit' => $access['has_access'] ? $curriculum['next_unit'] : null,
            'next_unit_reason' => ! $access['has_access'] ? $access['reason'] : $curriculum['next_unit_reason'],
        ]])->header('Cache-Control', 'private, no-store');
    }

    /** @return Builder<Course> */
    private function visibleCourses(): Builder
    {
        return Course::published()->whereIn('type', Course::TYPES)->with([
            'lessons' => fn ($q) => $q->where('status', 'published')->orderBy('sort_order')->orderBy('id'),
            'lessons.learningMaterials' => fn ($q) => $q->published()->select('id', 'lesson_id', 'title', 'material_type', 'sort_order', 'status', 'is_required', 'is_preview', 'duration_seconds', 'quiz_id', 'quiz_required', 'youtube_video_id', 'revision')->orderBy('sort_order')->orderBy('id'),
            'lessons.learningMaterials.quizzes' => fn ($q) => $q->where('is_active', true)->select('id', 'learning_material_id', 'title'),
            'lessons.assignments' => fn ($q) => $q->whereIn('status', ['published', 'closed'])->orderBy('sort_order')->orderBy('id'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $access
     * @param  array<string, mixed>|null  $curriculum
     * @return array<string, mixed>
     */
    private function courseData(Course $course, array $access, User $user, ?array $curriculum = null): array
    {
        $path = $course->thumbnail_path;
        $cover = is_string($path) && str_starts_with($path, 'learning/covers/') && ! str_contains($path, '..') && preg_match('/\.(?:jpe?g|png|webp)$/i', $path)
            ? asset('storage/'.$path) : null;

        return ['id' => $course->id, 'title' => $course->title, 'description' => $course->description, 'type' => $course->type,
            'revision' => $course->revision, 'cover_url' => $cover, 'module_count' => $course->lessons->count(),
            'material_count' => $course->lessons->sum(fn ($lesson) => $lesson->learningMaterials->count()), 'access' => $access,
            'progress' => ($curriculum ?? $this->progress->curriculum($user, $course))['progress']];
    }
}
