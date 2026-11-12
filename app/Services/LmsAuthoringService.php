<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Quiz;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LmsAuthoringService
{
    /** @param array<string, mixed> $input */
    public function saveCourse(array $input, ?int $id = null): Course
    {
        return DB::transaction(function () use ($input, $id) {
            $this->authorize();
            $course = $id ? Course::lockForUpdate()->findOrFail($id) : new Course;
            $this->revision($course, $input);
            $data = Validator::make($input, [
                'title' => 'required|string|max:255', 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('courses')->ignore($id)],
                'code' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('courses')->ignore($id)],
                'description' => 'nullable|string|max:10000', 'type' => ['required', Rule::in(Course::TYPES)],
                'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
                'prerequisite_course_id' => 'nullable|integer|exists:courses,id', 'thumbnail_path' => 'nullable|string|max:500', 'is_featured' => 'boolean',
            ])->validate();
            if ($data['type'] === Course::TYPE_HARDWARE_SOLUTION) {
                $sourceId = $data['prerequisite_course_id'] ?? $course->prerequisite_course_id;
                $source = $sourceId ? Course::whereKey($sourceId)->whereIn('type', ['android', 'iphone'])->first() : null;
                if (! $source || $source->id === $course->id) {
                    throw ValidationException::withMessages(['prerequisite_course_id' => 'Pilih kelas Android/iPhone terkait untuk akses HS.']);
                }
                $data['prerequisite_course_id'] = $source->id;
            } else {
                $data['prerequisite_course_id'] = null;
            }
            $old = $course->getAttributes();
            $data['slug'] = $data['slug'] ?? ($course->slug ?: Str::slug($data['title']).'-'.Str::lower(Str::random(6)));
            $data['code'] = $data['code'] ?? ($course->code ?: 'C-'.Str::uuid());
            $data['is_free_class'] = $data['type'] === Course::TYPE_FREE_CLASS;
            $data['archived_at'] = $data['status'] === 'archived' ? ($course->archived_at ?? now()) : null;
            if ($data['status'] === 'published') {
                $this->assertCourseReady($course);
                $data['published_at'] = $course->published_at ?? now();
            }
            $course->fill($data);
            $this->persist($course, $old);

            return $course;
        });
    }

    /** @param array<string, mixed> $input */
    public function saveLesson(array $input, ?int $id = null): Lesson
    {
        return DB::transaction(function () use ($input, $id) {
            $this->authorize();
            Validator::make($input, ['course_id' => 'required|integer|exists:courses,id'])->validate();
            $course = Course::lockForUpdate()->whereKey($input['course_id'])->firstOrFail();
            $lesson = $id ? Lesson::lockForUpdate()->findOrFail($id) : new Lesson;
            $this->revision($lesson, $input);
            if ($id && $lesson->course_id !== $course->id) {
                throw ValidationException::withMessages(['course_id' => 'Modul tidak dapat dipindah ke kelas lain.']);
            }
            $data = Validator::make($input, [
                'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000',
                'code' => ['nullable', 'alpha_dash', 'max:50', Rule::unique('lessons')->ignore($id)],
                'status' => ['required', Rule::in(['draft', 'published', 'archived'])], 'sort_order' => 'sometimes|integer|min:0',
            ])->validate();
            $old = $lesson->getAttributes();
            $data['course_id'] = $course->id;
            $data['code'] = $data['code'] ?? ($lesson->code ?: 'M-'.Str::uuid());
            $data['sort_order'] = $data['sort_order'] ?? ($lesson->exists ? $lesson->sort_order : ($course->lessons()->max('sort_order') ?? -1) + 1);
            if ($data['status'] !== 'archived') {
                $this->assertPosition(Lesson::class, 'course_id', $course->id, $data['sort_order'], $id);
            }
            $lesson->fill($data);
            if ($data['status'] === 'published') {
                $this->assertLessonReady($lesson);
            }
            $this->persist($lesson, $old);
            $this->touchParent($course);

            return $lesson;
        });
    }

    /** @param array<string, mixed> $input */
    public function saveMaterial(array $input, ?int $id = null): LearningMaterial
    {
        return DB::transaction(function () use ($input, $id) {
            $this->authorize();
            Validator::make($input, ['lesson_id' => 'required|integer|exists:lessons,id'])->validate();
            $parent = Lesson::whereKey($input['lesson_id'])->firstOrFail();
            $course = Course::lockForUpdate()->findOrFail($parent->course_id);
            $lesson = Lesson::lockForUpdate()->findOrFail($parent->id);
            $material = $id ? LearningMaterial::lockForUpdate()->findOrFail($id) : new LearningMaterial;
            $this->revision($material, $input);
            if ($id && $material->lesson_id !== $lesson->id) {
                throw ValidationException::withMessages(['lesson_id' => 'Materi tidak dapat dipindah ke modul lain.']);
            }
            foreach (['youtube_url' => 'youtube_video', 'pdf_path' => 'pdf_document', 'content_text' => 'text_content', 'external_url' => 'external_link'] as $field => $type) {
                if (($input['material_type'] ?? null) !== $type) {
                    $input[$field] = null;
                }
            }
            if (($input['material_type'] ?? null) === 'pdf_document') {
                if (! empty($input['pdf_upload_id'])) {
                    $upload = app(LmsUploadService::class)->ready($input['pdf_upload_id'], $material->pdf_upload_id === $input['pdf_upload_id'] ? null : auth()->user());
                    $input['pdf_path'] = $upload->path;
                } elseif (! empty($input['pdf_path']) && (! $material->exists || $material->pdf_path !== $input['pdf_path'])) {
                    throw ValidationException::withMessages(['pdf_upload_id' => 'Unggah dan periksa PDF dahulu; gunakan ID berkas siap.']);
                }
            } else {
                $input['pdf_upload_id'] = null;
            }
            $data = Validator::make($input, [
                'pdf_upload_id' => 'nullable|uuid',
                'unit_code' => ['required', 'alpha_dash', 'max:50', Rule::unique('learning_materials')->ignore($id)],
                'unit_title' => 'required|string|max:255', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:10000',
                'material_type' => ['required', Rule::in(['youtube_video', 'pdf_document', 'text_content', 'external_link'])],
                'youtube_url' => 'nullable|url|max:500', 'pdf_path' => 'nullable|string|max:500',
                'content_text' => 'nullable|string|max:100000', 'external_url' => 'nullable|url:https|max:500',
                'status' => ['required', Rule::in(['draft', 'published', 'archived'])], 'sort_order' => 'sometimes|integer|min:0',
                'is_required' => 'boolean', 'is_preview' => 'boolean', 'quiz_required' => 'boolean',
                'quiz_id' => 'nullable|integer', 'duration_seconds' => 'integer|min:0', 'thumbnail_path' => 'nullable|string|max:500',
            ])->validate();
            $old = $material->getAttributes();
            $data['lesson_id'] = $lesson->id;
            $data['sort_order'] = $data['sort_order'] ?? ($material->exists ? $material->sort_order : ($lesson->learningMaterials()->withTrashed()->max('sort_order') ?? -1) + 1);
            if ($data['status'] !== 'archived') {
                $this->assertPosition(LearningMaterial::class, 'lesson_id', $lesson->id, $data['sort_order'], $id);
            }
            $material->fill($data);
            $material->youtube_video_id = $material->material_type === 'youtube_video'
                ? app(YouTubeValidationService::class)->extractVideoId($material->youtube_url) : null;
            if ($material->youtube_url && $material->material_type === 'youtube_video' && ! $material->youtube_video_id) {
                throw ValidationException::withMessages(['youtube_url' => 'Gunakan tautan YouTube yang valid.']);
            }
            if ($material->status === 'published') {
                $this->assertMaterialReady($material);
            }
            $this->persist($material, $old);
            $this->touchParent($lesson);
            $this->touchParent($course);

            return $material;
        });
    }

    public function archive(Course|Lesson|LearningMaterial $model, int $expectedRevision): Course|Lesson|LearningMaterial
    {
        return DB::transaction(function () use ($model, $expectedRevision) {
            $this->authorize();
            abort_unless(in_array($model::class, [Course::class, Lesson::class, LearningMaterial::class], true), 422);
            $course = null;
            $lesson = null;
            if ($model instanceof Lesson) {
                $course = Course::lockForUpdate()->findOrFail($model->course_id);
            } elseif ($model instanceof LearningMaterial) {
                $parent = Lesson::findOrFail($model->lesson_id);
                $course = Course::lockForUpdate()->findOrFail($parent->course_id);
                $lesson = Lesson::lockForUpdate()->findOrFail($parent->id);
            }
            $model = $model->newQuery()->lockForUpdate()->findOrFail($model->id);
            $this->revision($model, ['expected_revision' => $expectedRevision]);
            $old = $model->getAttributes();
            $model->status = 'archived';
            if ($model instanceof Course) {
                $model->archived_at = now();
            }
            $this->persist($model, $old);
            if ($lesson) {
                $this->touchParent($lesson);
            }
            if ($course) {
                $this->touchParent($course);
            }

            return $model;
        });
    }

    /** @param list<array{id: int, revision: int}> $items */
    public function reorder(Course|Lesson $parent, array $items, int $expectedRevision): void
    {
        DB::transaction(function () use ($parent, $items, $expectedRevision) {
            $this->authorize();
            if ($parent instanceof Lesson) {
                $course = Course::lockForUpdate()->findOrFail($parent->course_id);
            }
            $parent = $parent->newQuery()->lockForUpdate()->findOrFail($parent->id);
            $this->revision($parent, ['expected_revision' => $expectedRevision]);
            $children = ($parent instanceof Course ? $parent->lessons() : $parent->learningMaterials())->where('status', '!=', 'archived')->lockForUpdate()->get()->keyBy('id');
            $ids = array_column($items, 'id');
            $expected = $children->keys()->sort()->values()->all();
            $sorted = $ids;
            sort($sorted);
            if ($sorted !== $expected || count(array_unique($ids)) !== count($ids)) {
                throw ValidationException::withMessages(['items' => 'Kirim seluruh item aktif dari parent yang sama, masing-masing satu kali.']);
            }
            foreach ($items as $position => $item) {
                $child = $children->get($item['id']);
                $this->revision($child, ['expected_revision' => $item['revision'] ?? null]);
                $old = $child->getAttributes();
                $child->sort_order = $position;
                $this->persist($child, $old);
            }
            $this->touchParent($parent);
            if (isset($course)) {
                $this->touchParent($course);
            }
        });
    }

    private function assertCourseReady(Course $course): void
    {
        if ($course->exists && $course->lessons()->where('status', '!=', 'archived')->select('sort_order')->groupBy('sort_order')->havingRaw('count(*) > 1')->exists()) {
            throw ValidationException::withMessages(['sort_order' => 'Urutan modul legacy berkonflik; urutkan ulang sebelum publish.']);
        }
        $lessons = $course->exists ? $course->lessons()->where('status', 'published')->get() : collect();
        if ($lessons->isEmpty()) {
            throw ValidationException::withMessages(['status' => 'Kelas membutuhkan setidaknya satu modul terbit.']);
        }
        $this->assertScopedQuizzes($course->id);
        foreach ($lessons as $lesson) {
            $this->assertLessonReady($lesson);
        }
    }

    private function assertLessonReady(Lesson $lesson): void
    {
        if ($lesson->exists && $lesson->learningMaterials()->where('status', '!=', 'archived')->select('sort_order')->groupBy('sort_order')->havingRaw('count(*) > 1')->exists()) {
            throw ValidationException::withMessages(['sort_order' => 'Urutan materi legacy berkonflik; urutkan ulang sebelum publish.']);
        }
        $materials = $lesson->exists ? $lesson->learningMaterials()->where('status', '!=', 'archived')->get() : collect();
        if (! $materials->contains('status', 'published') || $materials->where('is_required', true)->contains('status', 'draft')) {
            throw ValidationException::withMessages(['status' => 'Modul membutuhkan materi terbit; semua materi wajib harus terbit.']);
        }
        $this->assertScopedQuizzes($lesson->course_id, $lesson->id);
        foreach ($materials->where('status', 'published') as $material) {
            $this->assertMaterialReady($material);
        }
    }

    private function assertScopedQuizzes(int $courseId, ?int $moduleId = null): void
    {
        $query = Quiz::where('target_course_id', $courseId)->where('is_required', true);
        if ($moduleId !== null) {
            $query->where('target_module_id', $moduleId);
        }
        foreach ($query->get() as $quiz) {
            if (! $quiz->is_active) {
                throw ValidationException::withMessages(['quiz_id' => 'Kuis wajib belum aktif.']);
            }
            app(QuizDefinitionService::class)->snapshot($quiz);
        }
    }

    private function assertMaterialReady(LearningMaterial $material): void
    {
        if ($material->material_type === 'youtube_video') {
            if (! $material->youtube_video_id) {
                throw ValidationException::withMessages(['youtube_url' => 'Video belum diisi.']);
            }
            app(YouTubeValidationService::class)->assertEmbeddable($material->youtube_video_id);
        } elseif ($material->material_type === 'pdf_document') {
            if (! $material->pdf_upload_id) {
                throw ValidationException::withMessages(['pdf_upload_id' => 'PDF harus lolos pemeriksaan sebelum terbit.']);
            }
            $upload = app(LmsUploadService::class)->ready($material->pdf_upload_id);
            if ($material->pdf_path !== $upload->path || ! app(LmsFileService::class)->path('lms', $material->pdf_path) || Storage::disk('public')->exists($material->pdf_path ?? '')) {
                throw ValidationException::withMessages(['pdf_path' => 'PDF harus tersedia di penyimpanan privat dan salinan publik harus dimigrasikan.']);
            }
        } elseif ($material->material_type === 'text_content' && trim($material->content_text ?? '') === '') {
            throw ValidationException::withMessages(['content_text' => 'Isi materi teks belum diisi.']);
        } elseif ($material->material_type === 'external_link' && (! filter_var($material->external_url, FILTER_VALIDATE_URL) || parse_url($material->external_url, PHP_URL_SCHEME) !== 'https')) {
            throw ValidationException::withMessages(['external_url' => 'Tautan HTTPS belum diisi.']);
        }
        if ($material->quiz_required) {
            $quiz = $material->exists && $material->quiz_id
                ? Quiz::with('questions')->whereKey($material->quiz_id)->where('learning_material_id', $material->id)->where('is_active', true)->first() : null;
            if (! $quiz) {
                throw ValidationException::withMessages(['quiz_id' => 'Kuis wajib belum lengkap atau tidak terikat ke materi ini.']);
            }
            try {
                app(QuizDefinitionService::class)->snapshot($quiz);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages(['quiz_id' => 'Soal, opsi, kunci atau aturan kuis wajib belum valid.']);
            }
        }
    }

    private function authorize(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    /** @param array<string, mixed> $input */
    private function revision(Course|Lesson|LearningMaterial $model, array $input): void
    {
        if ($model->exists) {
            Validator::make($input, ['expected_revision' => 'required|integer|min:1'])->validate();
            if (! isset($input['expected_revision'])) {
                throw ValidationException::withMessages(['expected_revision' => 'Muat ulang konten sebelum menyimpan.']);
            }
            abort_if((int) $input['expected_revision'] !== (int) $model->revision, 409, 'Konten telah berubah. Muat ulang sebelum menyimpan.');
        }
    }

    private function assertPosition(string $model, string $foreignKey, int $parentId, int $position, ?int $id): void
    {
        if ($model::where($foreignKey, $parentId)->where('status', '!=', 'archived')->when($id, fn ($q) => $q->whereKeyNot($id))->where('sort_order', $position)->exists()) {
            throw ValidationException::withMessages(['sort_order' => 'Urutan sudah dipakai. Gunakan pengurutan ulang atau pilih nomor lain.']);
        }
    }

    /** @param array<string, mixed> $old */
    private function persist(Course|Lesson|LearningMaterial $model, array $old): void
    {
        $model->revision = $model->exists ? $model->revision + 1 : 1;
        $model->save();
        AuditLog::create([
            'actor_type' => 'admin', 'actor_id' => auth()->id(), 'action' => 'lms.'.(! $old ? 'created' : (($old['status'] ?? null) !== $model->status ? $model->status : 'updated')),
            'auditable_type' => $model::class, 'auditable_id' => $model->id,
            'old_values' => $old, 'new_values' => $model->getAttributes(),
            'ip_address' => request()->ip(), 'user_agent' => Str::limit(request()->userAgent() ?? '', 200, ''),
        ]);
    }

    private function touchParent(Course|Lesson|LearningMaterial $model): void
    {
        $old = $model->getAttributes();
        $this->persist($model, $old);
    }
}
