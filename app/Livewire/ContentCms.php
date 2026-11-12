<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\LmsUpload;
use App\Services\LmsAuthoringService;
use App\Services\LmsUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ContentCms extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $activeTab = 'courses';

    #[Locked]
    public ?int $selectedCourseId = null;

    #[Locked]
    public int $courseRevision = 1;

    public string $courseTitle = '';

    public string $courseSlug = '';

    public string $courseCode = '';

    public string $courseType = 'android';

    public ?int $coursePrerequisiteId = null;

    public string $courseDescription = '';

    public string $courseStatus = 'draft';

    public ?TemporaryUploadedFile $courseCover = null;

    #[Locked]
    public ?string $courseCoverPath = null;

    #[Locked]
    public ?int $selectedLessonId = null;

    #[Locked]
    public int $lessonRevision = 1;

    public string $lessonTitle = '';

    public string $lessonCode = '';

    public string $lessonDescription = '';

    public string $lessonStatus = 'draft';

    public int $lessonSortOrder = 0;

    #[Locked]
    public ?int $selectedMaterialId = null;

    #[Locked]
    public int $materialRevision = 1;

    public string $materialTitle = '';

    public string $materialCode = '';

    public string $materialUnitTitle = '';

    public string $materialType = 'youtube_video';

    public string $materialDescription = '';

    public string $materialYoutubeUrl = '';

    public string $materialContent = '';

    public string $materialExternalUrl = '';

    public string $materialStatus = 'draft';

    public int $materialSortOrder = 0;

    public bool $materialIsRequired = true;

    public bool $materialIsPreview = false;

    public ?TemporaryUploadedFile $materialPdfFile = null;

    public ?string $materialPdfUploadId = null;

    public string $materialPdfStatus = '';

    #[Locked]
    public ?string $materialPdfPath = null;

    /** @var list<array{0: string, 1: string}> */
    private array $pendingUploads = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function newCourse(): void
    {
        $this->reset('selectedCourseId', 'courseRevision', 'courseTitle', 'courseSlug', 'courseCode', 'courseType', 'coursePrerequisiteId', 'courseDescription', 'courseStatus', 'courseCover', 'courseCoverPath', 'selectedLessonId', 'selectedMaterialId');
        $this->activeTab = 'courses';
        $this->resetValidation();
    }

    public function selectCourse(int $id): void
    {
        $this->newCourse();
        $this->selectedCourseId = $id;
        $this->loadCourse();
        $this->newLesson();
        $this->activeTab = 'courses';
    }

    private function loadCourse(): void
    {
        $course = Course::findOrFail($this->selectedCourseId);
        $this->courseRevision = $course->revision;
        $this->courseTitle = $course->title;
        $this->courseSlug = $course->slug;
        $this->courseCode = $course->code ?? '';
        $this->courseType = $course->type;
        $this->coursePrerequisiteId = $course->prerequisite_course_id;
        $this->courseDescription = $course->description ?? '';
        $this->courseStatus = $course->status;
        $this->courseCoverPath = $course->thumbnail_path;
    }

    public function saveCourse(): void
    {
        $this->perform(function ($service) {
            $this->validate(['courseCover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
            $path = $this->courseCoverPath;
            if ($this->courseCover) {
                $path = $this->courseCover->store('learning/covers', 'public');
                if ($path === false) {
                    throw new \RuntimeException('Cover storage failed');
                }
                $this->pendingUploads[] = ['public', $path];
            }
            $course = $service->saveCourse([
                'title' => $this->courseTitle, 'slug' => $this->courseSlug ?: null, 'code' => $this->courseCode ?: null,
                'prerequisite_course_id' => $this->coursePrerequisiteId, 'type' => $this->courseType, 'description' => $this->courseDescription,
                'status' => $this->courseStatus, 'thumbnail_path' => $path, 'expected_revision' => $this->courseRevision,
            ], $this->selectedCourseId);
            $this->selectCourse($course->id);
        });
    }

    public function newLesson(): void
    {
        $this->reset('selectedLessonId', 'lessonRevision', 'lessonTitle', 'lessonCode', 'lessonDescription', 'lessonStatus', 'selectedMaterialId');
        $this->lessonSortOrder = $this->selectedCourseId ? (Lesson::where('course_id', $this->selectedCourseId)->max('sort_order') ?? -1) + 1 : 0;
        $this->resetValidation();
    }

    public function selectLesson(int $id): void
    {
        $lesson = Lesson::where('course_id', $this->selectedCourseId)->findOrFail($id);
        $this->selectedLessonId = $id;
        $this->lessonRevision = $lesson->revision;
        $this->lessonTitle = $lesson->title;
        $this->lessonCode = $lesson->code ?? '';
        $this->lessonDescription = $lesson->description ?? '';
        $this->lessonStatus = $lesson->status;
        $this->lessonSortOrder = $lesson->sort_order;
        $this->newMaterial();
        $this->activeTab = 'lessons';
    }

    public function saveLesson(): void
    {
        $this->perform(function ($service) {
            $lesson = $service->saveLesson([
                'course_id' => $this->selectedCourseId, 'title' => $this->lessonTitle, 'code' => $this->lessonCode ?: null,
                'description' => $this->lessonDescription, 'status' => $this->lessonStatus,
                'sort_order' => $this->lessonSortOrder, 'expected_revision' => $this->lessonRevision,
            ], $this->selectedLessonId);
            $this->loadCourse();
            $this->selectLesson($lesson->id);
        });
    }

    public function newMaterial(): void
    {
        $this->reset('selectedMaterialId', 'materialRevision', 'materialTitle', 'materialCode', 'materialUnitTitle', 'materialType', 'materialDescription', 'materialYoutubeUrl', 'materialContent', 'materialExternalUrl', 'materialStatus', 'materialIsRequired', 'materialIsPreview', 'materialPdfFile', 'materialPdfPath', 'materialPdfUploadId', 'materialPdfStatus');
        $this->materialSortOrder = $this->selectedLessonId ? (LearningMaterial::where('lesson_id', $this->selectedLessonId)->max('sort_order') ?? -1) + 1 : 0;
        $this->resetValidation();
    }

    public function selectMaterial(int $id): void
    {
        $material = LearningMaterial::where('lesson_id', $this->selectedLessonId)->findOrFail($id);
        $this->selectedMaterialId = $id;
        $this->materialRevision = $material->revision;
        $this->materialTitle = $material->title;
        $this->materialCode = $material->unit_code;
        $this->materialUnitTitle = $material->unit_title;
        $this->materialType = $material->material_type;
        $this->materialDescription = $material->description ?? '';
        $this->materialYoutubeUrl = $material->youtube_url ?? '';
        $this->materialContent = $material->content_text ?? '';
        $this->materialExternalUrl = $material->external_url ?? '';
        $this->materialStatus = $material->status;
        $this->materialSortOrder = $material->sort_order;
        $this->materialIsRequired = $material->is_required;
        $this->materialIsPreview = (bool) $material->is_preview;
        $this->materialPdfPath = $material->pdf_path;
        $this->materialPdfUploadId = $material->pdf_upload_id;
        $this->materialPdfStatus = $material->pdf_upload_id ? 'PDF terhubung' : 'Berkas lama perlu diperiksa ulang sebelum terbit.';
        $this->materialPdfFile = null;
        $this->activeTab = 'materials';
        $this->resetValidation();
    }

    public function uploadMaterialPdf(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $this->validate(['materialPdfFile' => 'required|file|mimes:pdf|max:10240']);
        $upload = app(LmsUploadService::class)->receive(auth()->user(), $this->materialPdfFile, 'course_material');
        $this->materialPdfUploadId = $upload->id;
        $this->materialPdfFile = null;
        $this->materialPdfStatus = 'Karantina: menunggu pemeriksaan. ID '.$upload->id;
    }

    public function checkMaterialPdf(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $upload = LmsUpload::whereKey($this->materialPdfUploadId)->where('user_id', auth()->id())->firstOrFail();
        $this->materialPdfStatus = $upload->status.' — '.($upload->reason ?? $upload->original_name);
    }

    public function saveMaterial(): void
    {
        $this->perform(function ($service) {
            if ($this->materialPdfFile) {
                throw ValidationException::withMessages(['materialPdfFile' => 'Unggah untuk pemeriksaan dahulu, lalu tunggu status ready.']);
            }
            $path = $this->materialPdfPath;
            $existing = $this->selectedMaterialId ? LearningMaterial::where('lesson_id', $this->selectedLessonId)->findOrFail($this->selectedMaterialId)->getAttributes() : [];
            $material = $service->saveMaterial([
                ...$existing, 'lesson_id' => $this->selectedLessonId, 'title' => $this->materialTitle,
                'unit_code' => $this->materialCode, 'unit_title' => $this->materialUnitTitle,
                'material_type' => $this->materialType, 'description' => $this->materialDescription,
                'youtube_url' => $this->materialYoutubeUrl ?: null, 'pdf_path' => $path, 'pdf_upload_id' => $this->materialPdfUploadId ?: null,
                'content_text' => $this->materialContent, 'external_url' => $this->materialExternalUrl ?: null,
                'status' => $this->materialStatus, 'sort_order' => $this->materialSortOrder,
                'is_required' => $this->materialIsRequired, 'is_preview' => $this->materialIsPreview,
                'expected_revision' => $this->materialRevision,
            ], $this->selectedMaterialId);
            $lessonId = $this->selectedLessonId;
            $this->loadCourse();
            $this->selectLesson($lessonId);
            $this->selectMaterial($material->id);
        });
    }

    public function archiveCourse(int $id, int $revision): void
    {
        $this->perform(fn ($service) => $service->archive(Course::findOrFail($id), $revision));
        if ($this->selectedCourseId === $id) {
            $this->loadCourse();
        }
    }

    public function archiveLesson(int $id, int $revision): void
    {
        if ($this->perform(fn ($service) => $service->archive(Lesson::where('course_id', $this->selectedCourseId)->findOrFail($id), $revision))) {
            $this->loadCourse();
            $this->newLesson();
        }
    }

    public function archiveMaterial(int $id, int $revision): void
    {
        if ($this->perform(fn ($service) => $service->archive(LearningMaterial::where('lesson_id', $this->selectedLessonId)->findOrFail($id), $revision))) {
            $this->loadCourse();
            $this->selectLesson($this->selectedLessonId);
        }
    }

    public function move(string $kind, int $id, int $direction): void
    {
        $this->perform(function ($service) use ($kind, $id, $direction) {
            abort_unless(in_array($kind, ['lessons', 'materials'], true) && in_array($direction, [-1, 1], true), 422);
            $parent = $kind === 'lessons' ? Course::findOrFail($this->selectedCourseId) : Lesson::where('course_id', $this->selectedCourseId)->findOrFail($this->selectedLessonId);
            $children = ($parent instanceof Course ? $parent->lessons() : $parent->learningMaterials())->where('status', '!=', 'archived')->orderBy('id')->get();
            $items = $children->map(fn ($child) => ['id' => $child->id, 'revision' => $child->revision])->all();
            $index = array_search($id, array_column($items, 'id'), true);
            if ($index === false || ! isset($items[$index + $direction])) {
                return;
            }
            [$items[$index], $items[$index + $direction]] = [$items[$index + $direction], $items[$index]];
            $service->reorder($parent, $items, $kind === 'lessons' ? $this->courseRevision : $this->lessonRevision);
            $this->loadCourse();
            if ($kind === 'materials') {
                $this->selectLesson($this->selectedLessonId);
            }
        });
    }

    private function perform(callable $action): bool
    {
        $this->resetValidation();
        session()->forget('message');
        $this->pendingUploads = [];
        try {
            DB::transaction(fn () => $action(app(LmsAuthoringService::class)));
            session()->flash('message', 'Konten berhasil disimpan.');

            return true;
        } catch (ValidationException $e) {
            $this->cleanupUploads();
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, implode(' ', $messages));
            }
        } catch (HttpExceptionInterface $e) {
            $this->cleanupUploads();
            $this->addError('form', $e->getStatusCode() === 409 ? 'Konten telah berubah. Pilih ulang konten untuk memuat revisi terbaru; perubahan belum disimpan.' : 'Konten tidak tersedia untuk operasi ini.');
        } catch (\Throwable $e) {
            $this->cleanupUploads();
            report($e);
            $this->addError('form', 'Konten gagal disimpan. Isian tetap tersedia; coba kembali.');
        }

        return false;
    }

    private function cleanupUploads(): void
    {
        foreach ($this->pendingUploads as [$disk, $path]) {
            Storage::disk($disk)->delete($path);
        }
        $this->pendingUploads = [];
    }

    public function render(): View
    {
        return view('livewire.content-cms', [
            'courses' => Course::query()->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->when(in_array($this->statusFilter, ['draft', 'published', 'archived'], true), fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('updated_at')->orderBy('id')->paginate(10),
            'prerequisiteCourses' => Course::whereIn('type', ['android', 'iphone'])->where('status', '!=', 'archived')->orderBy('title')->get(['id', 'title']),
            'lessons' => $this->selectedCourseId ? Lesson::where('course_id', $this->selectedCourseId)->orderBy('sort_order')->orderBy('id')->get() : collect(),
            'materials' => $this->selectedLessonId ? LearningMaterial::where('lesson_id', $this->selectedLessonId)->orderBy('sort_order')->orderBy('id')->get() : collect(),
        ]);
    }
}
