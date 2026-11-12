<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Quiz;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SanitizeLmsCatalog
{
    private const PRIVATE_FIELDS = [
        'correct_answer', 'answer_key', 'keywords', 'answer_aliases', 'rubric', 'snapshot', 'question_grades',
        'youtube_url', 'youtube_video_id', 'embed_url', 'pdf_path',
        'pdf_signed_url', 'content_text', 'external_url', 'file_path',
        'source_path', 'url', 'video_id', 'metadata',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();
        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        // Catalog reads never grant media access, including through nested legacy relations.
        $request->merge(['status' => 'published', 'is_active' => true]);
        $action = $request->route()->getActionMethod();
        $controller = $request->route()->getControllerClass();
        $id = $request->route('id') ?? $request->route('material');
        if ($action === 'show') {
            match (class_basename($controller)) {
                'CourseApiController' => Course::published()->findOrFail($id),
                'LessonApiController' => Lesson::where('status', 'published')->whereHas('course', fn ($q) => $q->published())->findOrFail($id),
                'LearningMaterialApiController' => LearningMaterial::published()
                    ->whereHas('lesson', fn ($q) => $q->where('status', 'published')->whereHas('course', fn ($q) => $q->published()))->findOrFail($id),
                'QuizApiController' => Quiz::where('is_active', true)
                    ->whereHas('learningMaterial', fn ($q) => $q->published()
                        ->whereHas('lesson', fn ($q) => $q->where('status', 'published')->whereHas('course', fn ($q) => $q->published())))->findOrFail($id),
                default => null,
            };
        }

        $response = $next($request);
        if ($response instanceof JsonResponse && $response->isSuccessful()) {
            $response->setData($this->sanitize($response->getData(true)));
        }

        return $response;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function sanitize(array $data): array
    {
        if (! array_is_list($data) && (in_array($data['status'] ?? null, ['draft', 'archived'], true)
            || ! empty($data['archived_at']) || ($data['is_active'] ?? true) === false)) {
            return [];
        }
        if (array_is_list($data)) {
            return array_values(array_map(fn ($item) => is_array($item) ? $this->sanitize($item) : $item,
                array_filter($data, fn ($item) => ! is_array($item)
                    || (! in_array($item['status'] ?? null, ['draft', 'archived'], true)
                        && empty($item['archived_at']) && ($item['is_active'] ?? true) !== false))));
        }

        foreach (self::PRIVATE_FIELDS as $key) {
            unset($data[$key]);
        }
        foreach ($data as $key => $value) {
            if (is_string($value) && preg_match('~https?://(?:img\.youtube\.com|i(?:\d)?\.ytimg\.com)/vi/~i', $value)) {
                $data[$key] = null;

                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
