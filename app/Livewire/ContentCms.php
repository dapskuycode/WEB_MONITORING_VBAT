<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\LearningMaterial;
use App\Models\Lesson;
use App\Models\Unit;
use App\Services\YouTubeValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ContentCms extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $activeTab = 'courses';

    // Course form
    public ?int $selectedCourseId = null;
    public string $courseTitle = '';
    public string $courseSlug = '';
    public string $courseType = 'android';
    public string $courseDescription = '';
    public ?int $courseCategoryId = null;
    public float $coursePrice = 0;
    public string $courseDuration = '30 hari';
    public string $courseDurationUnit = 'day';
    public int $courseDurationValue = 30;
    public bool $courseIsArchived = false;
    public bool $courseIsFree = false;
    public string $courseEntitlements = '';
    public array $courseErrors = [];

    // Category form
    public ?int $selectedCategoryId = null;
    public string $categoryTitle = '';
    public string $categorySlug = '';
    public string $categoryColor = '#3b82f6';

    // Lesson form
    public ?int $selectedLessonId = null;
    public string $lessonTitle = '';
    public string $lessonSlug = '';
    public string $lessonDescription = '';
    public int $lessonSortOrder = 0;
    public bool $lessonIsArchived = false;

    // Unit form
    public ?int $selectedUnitId = null;
    public string $unitTitle = '';
    public string $unitCode = '';
    public string $unitDescription = '';
    public string $unitType = 'video';
    public bool $unitIsRequired = false;
    public int $unitSortOrder = 0;
    public bool $unitIsArchived = false;
    public ?string $unitQuizCode = null;

    // Material form
    public ?int $selectedMaterialId = null;
    public string $materialTitle = '';
    public string $materialType = 'youtube';
    public string $materialDescription = '';
    public ?string $materialYoutubeUrl = null;
    public ?string $materialVideoId = null;
    public $materialPdfFile;
    public ?string $materialPdfPath = null;
    public $materialThumbnailFile;
    public ?string $materialThumbnailPath = null;
    public int $materialSortOrder = 0;
    public bool $materialIsArchived = false;
    public bool $materialIsRequired = false;

    // Bulk upload
    public $bulkXlsxFile;
    public string $bulkProcess = 'dry_run';
    public string $bulkReport = '';

    // Services
    private YouTubeValidationService $youtubeService;

    public function mount()
    {
        $this->youtubeService = app(YouTubeValidationService::class);
    }

    public function updatedCourseType($value)
    {
        $this->coursePrice = match ($value) {
            'android' => 800000,
            'iphone' => 2000000,
            'bundling' => 2500000,
            'hardware_solution' => 0,
            'free_class' => 0,
            default => 0,
        };
        $this->courseIsFree = in_array($value, ['hardware_solution', 'free_class']);
        $this->courseDurationValue = $value === 'subscription' ? 365 : 30;
        $this->courseDurationUnit = 'day';
    }

    public function updatedMaterialYoutubeUrl($url)
    {
        if (empty($url)) {
            $this->materialVideoId = null;
            return;
        }

        $this->materialVideoId = $this->youtubeService->extractVideoId($url);
        if (!$this->materialVideoId) {
            $this->addError('materialYoutubeUrl', 'Invalid YouTube URL. Must be a valid YouTube video link.');
        }
    }

    public function saveCourse()
    {
        $this->validate([
            'courseTitle' => 'required|min:3|max:255',
            'courseSlug' => 'required|min:3|max:100|alpha_dash|unique:courses,slug,' . ($this->selectedCourseId ?: 'NULL'),
            'courseType' => ['required', Rule::in(['android', 'iphone', 'bundling', 'hardware_solution', 'free_class', 'subscription'])],
            'courseDescription' => 'nullable|max:1000',
            'courseCategoryId' => 'nullable|exists:course_categories,id',
            'coursePrice' => 'required|numeric|min:0',
            'courseDurationValue' => 'required|integer|min:1',
            'courseDurationUnit' => 'required|in:day,month,year',
        ]);

        try {
            DB::transaction(function () {
                $data = [
                    'title' => $this->courseTitle,
                    'slug' => $this->courseSlug,
                    'type' => $this->courseType,
                    'description' => $this->courseDescription,
                    'category_id' => $this->courseCategoryId,
                    'price' => $this->coursePrice,
                    'duration_value' => $this->courseDurationValue,
                    'duration_unit' => $this->courseDurationUnit,
                    'duration' => "{$this->courseDurationValue} {$this->courseDurationUnit}s",
                    'is_archived' => $this->courseIsArchived,
                    'is_free' => $this->courseIsFree,
                    'entitlements' => $this->courseEntitlements ? json_decode($this->courseEntitlements, true) : [],
                ];

                if ($this->selectedCourseId) {
                    Course::findOrFail($this->selectedCourseId)->update($data);
                    session()->flash('message', 'Course updated successfully.');
                } else {
                    Course::create($data);
                    session()->flash('message', 'Course created successfully.');
                }
                $this->resetCourseForm();
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to save course: ' . $e->getMessage());
        }
    }

    public function editCourse(int $id)
    {
        $course = Course::findOrFail($id);
        $this->selectedCourseId = $course->id;
        $this->courseTitle = $course->title;
        $this->courseSlug = $course->slug;
        $this->courseType = $course->type;
        $this->courseDescription = $course->description;
        $this->courseCategoryId = $course->category_id;
        $this->coursePrice = $course->price;
        [$value, $unit] = explode(' ', rtrim($course->duration, 's'));
        $this->courseDurationValue = (int)$value;
        $this->courseDurationUnit = $unit;
        $this->courseIsArchived = $course->is_archived;
        $this->courseIsFree = $course->is_free;
        $this->courseEntitlements = json_encode($course->entitlements, JSON_PRETTY_PRINT);
        $this->activeTab = 'courses';
    }

    public function deleteCourse(int $id)
    {
        $course = Course::findOrFail($id);
        if ($course->lessons()->count() > 0) {
            session()->flash('error', 'Cannot delete course with existing lessons. Archive instead.');
            return;
        }
        $course->delete();
        session()->flash('message', 'Course deleted successfully.');
    }

    public function resetCourseForm()
    {
        $this->selectedCourseId = null;
        $this->courseTitle = '';
        $this->courseSlug = '';
        $this->courseType = 'android';
        $this->courseDescription = '';
        $this->courseCategoryId = null;
        $this->coursePrice = 800000;
        $this->courseDurationValue = 30;
        $this->courseDurationUnit = 'day';
        $this->courseIsArchived = false;
        $this->courseIsFree = false;
        $this->courseEntitlements = '';
    }

    // Similar methods for category, lesson, unit, material...
    // For brevity, I'll implement the main flow with tab switching and listing

    // ===== CATEGORY CRUD =====
    public function saveCategory()
    {
        $this->validate([
            'categoryTitle' => 'required|min:3|max:255',
            'categorySlug' => 'required|min:3|max:100|alpha_dash|unique:course_categories,slug,' . ($this->selectedCategoryId ?: 'NULL'),
            'categoryColor' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $data = [
            'title' => $this->categoryTitle,
            'slug' => $this->categorySlug,
            'color' => $this->categoryColor,
        ];

        if ($this->selectedCategoryId) {
            CourseCategory::findOrFail($this->selectedCategoryId)->update($data);
            session()->flash('message', 'Category updated successfully.');
        } else {
            CourseCategory::create($data);
            session()->flash('message', 'Category created successfully.');
        }
        $this->resetCategoryForm();
    }

    public function editCategory(int $id)
    {
        $category = CourseCategory::findOrFail($id);
        $this->selectedCategoryId = $category->id;
        $this->categoryTitle = $category->title;
        $this->categorySlug = $category->slug;
        $this->categoryColor = $category->color ?? '#3b82f6';
        $this->activeTab = 'categories';
    }

    public function deleteCategory(int $id)
    {
        $category = CourseCategory::findOrFail($id);
        if ($category->courses()->count() > 0) {
            session()->flash('error', 'Cannot delete category with existing courses.');
            return;
        }
        $category->delete();
        session()->flash('message', 'Category deleted successfully.');
    }

    public function resetCategoryForm()
    {
        $this->selectedCategoryId = null;
        $this->categoryTitle = '';
        $this->categorySlug = '';
        $this->categoryColor = '#3b82f6';
    }

    // ===== LESSON CRUD =====
    public function saveLesson()
    {
        $this->validate([
            'lessonTitle' => 'required|min:3|max:255',
            'lessonSlug' => 'required|min:3|max:100|alpha_dash',
            'lessonDescription' => 'nullable|max:1000',
            'lessonSortOrder' => 'required|integer|min:0',
        ]);

        if (!$this->selectedCourseId) {
            session()->flash('error', 'Please select a course first.');
            return;
        }

        $data = [
            'course_id' => $this->selectedCourseId,
            'title' => $this->lessonTitle,
            'slug' => $this->lessonSlug,
            'description' => $this->lessonDescription,
            'sort_order' => $this->lessonSortOrder,
            'is_archived' => $this->lessonIsArchived,
        ];

        if ($this->selectedLessonId) {
            Lesson::findOrFail($this->selectedLessonId)->update($data);
            session()->flash('message', 'Lesson updated successfully.');
        } else {
            Lesson::create($data);
            session()->flash('message', 'Lesson created successfully.');
        }
        $this->resetLessonForm();
    }

    public function editLesson(int $id)
    {
        $lesson = Lesson::findOrFail($id);
        $this->selectedLessonId = $lesson->id;
        $this->selectedCourseId = $lesson->course_id;
        $this->lessonTitle = $lesson->title;
        $this->lessonSlug = $lesson->slug;
        $this->lessonDescription = $lesson->description;
        $this->lessonSortOrder = $lesson->sort_order;
        $this->lessonIsArchived = $lesson->is_archived;
        $this->activeTab = 'lessons';
    }

    public function deleteLesson(int $id)
    {
        $lesson = Lesson::findOrFail($id);
        if ($lesson->units()->count() > 0) {
            session()->flash('error', 'Cannot delete lesson with existing units. Archive instead.');
            return;
        }
        $lesson->delete();
        session()->flash('message', 'Lesson deleted successfully.');
    }

    public function resetLessonForm()
    {
        $this->selectedLessonId = null;
        $this->lessonTitle = '';
        $this->lessonSlug = '';
        $this->lessonDescription = '';
        $this->lessonSortOrder = 0;
        $this->lessonIsArchived = false;
    }

    // ===== UNIT CRUD =====
    public function saveUnit()
    {
        $this->validate([
            'unitTitle' => 'required|min:3|max:255',
            'unitCode' => 'required|min:1|max:50|alpha_dash',
            'unitDescription' => 'nullable|max:1000',
            'unitType' => ['required', Rule::in(['video', 'reading', 'quiz', 'assignment'])],
            'unitSortOrder' => 'required|integer|min:0',
        ]);

        if (!$this->selectedLessonId) {
            session()->flash('error', 'Please select a lesson first.');
            return;
        }

        $data = [
            'lesson_id' => $this->selectedLessonId,
            'title' => $this->unitTitle,
            'code' => $this->unitCode,
            'description' => $this->unitDescription,
            'type' => $this->unitType,
            'is_required' => $this->unitIsRequired,
            'sort_order' => $this->unitSortOrder,
            'is_archived' => $this->unitIsArchived,
            'quiz_code' => $this->unitQuizCode,
        ];

        if ($this->selectedUnitId) {
            Unit::findOrFail($this->selectedUnitId)->update($data);
            session()->flash('message', 'Unit updated successfully.');
        } else {
            Unit::create($data);
            session()->flash('message', 'Unit created successfully.');
        }
        $this->resetUnitForm();
    }

    public function editUnit(int $id)
    {
        $unit = Unit::findOrFail($id);
        $this->selectedUnitId = $unit->id;
        $this->selectedLessonId = $unit->lesson_id;
        $this->unitTitle = $unit->title;
        $this->unitCode = $unit->code;
        $this->unitDescription = $unit->description;
        $this->unitType = $unit->type;
        $this->unitIsRequired = $unit->is_required;
        $this->unitSortOrder = $unit->sort_order;
        $this->unitIsArchived = $unit->is_archived;
        $this->unitQuizCode = $unit->quiz_code;
        $this->activeTab = 'units';
    }

    public function deleteUnit(int $id)
    {
        $unit = Unit::findOrFail($id);
        if ($unit->learningMaterials()->count() > 0) {
            session()->flash('error', 'Cannot delete unit with existing materials. Archive instead.');
            return;
        }
        $unit->delete();
        session()->flash('message', 'Unit deleted successfully.');
    }

    public function resetUnitForm()
    {
        $this->selectedUnitId = null;
        $this->unitTitle = '';
        $this->unitCode = '';
        $this->unitDescription = '';
        $this->unitType = 'video';
        $this->unitIsRequired = false;
        $this->unitSortOrder = 0;
        $this->unitIsArchived = false;
        $this->unitQuizCode = null;
    }

    // ===== MATERIAL CRUD =====
    public function saveMaterial()
    {
        $this->validate([
            'materialTitle' => 'required|min:3|max:255',
            'materialType' => ['required', Rule::in(['youtube', 'pdf', 'external_link'])],
            'materialDescription' => 'nullable|max:1000',
            'materialSortOrder' => 'required|integer|min:0',
            'materialYoutubeUrl' => 'required_if:materialType,youtube',
            'materialPdfFile' => 'nullable|file|mimes:pdf|max:10240',
            'materialThumbnailFile' => 'nullable|image|max:2048',
        ]);

        if (!$this->selectedUnitId) {
            session()->flash('error', 'Please select a unit first.');
            return;
        }

        $data = [
            'unit_id' => $this->selectedUnitId,
            'title' => $this->materialTitle,
            'file_type' => $this->materialType,
            'description' => $this->materialDescription,
            'sort_order' => $this->materialSortOrder,
            'is_archived' => $this->materialIsArchived,
            'is_required' => $this->materialIsRequired,
        ];

        // YouTube handling
        if ($this->materialType === 'youtube' && $this->materialVideoId) {
            $data['url'] = $this->materialYoutubeUrl;
            $data['video_id'] = $this->materialVideoId;
            $data['file_path'] = null;
        }

        // PDF upload handling
        if ($this->materialType === 'pdf' && $this->materialPdfFile) {
            $data['file_path'] = $this->materialPdfFile->store('learning/pdfs', 'public');
        }

        // Thumbnail upload
        if ($this->materialThumbnailFile) {
            $data['thumbnail_path'] = $this->materialThumbnailFile->store('learning/thumbnails', 'public');
        }

        if ($this->selectedMaterialId) {
            LearningMaterial::findOrFail($this->selectedMaterialId)->update($data);
            session()->flash('message', 'Material updated successfully.');
        } else {
            LearningMaterial::create($data);
            session()->flash('message', 'Material created successfully.');
        }
        $this->resetMaterialForm();
    }

    public function editMaterial(int $id)
    {
        $material = LearningMaterial::findOrFail($id);
        $this->selectedMaterialId = $material->id;
        $this->selectedUnitId = $material->unit_id;
        $this->materialTitle = $material->title;
        $this->materialType = $material->file_type;
        $this->materialDescription = $material->description;
        $this->materialYoutubeUrl = $material->url;
        $this->materialVideoId = $material->video_id;
        $this->materialPdfPath = $material->file_path;
        $this->materialThumbnailPath = $material->thumbnail_path;
        $this->materialSortOrder = $material->sort_order;
        $this->materialIsArchived = $material->is_archived;
        $this->materialIsRequired = $material->is_required;
        $this->activeTab = 'materials';
    }

    public function deleteMaterial(int $id)
    {
        $material = LearningMaterial::findOrFail($id);
        $material->delete();
        session()->flash('message', 'Material deleted successfully.');
    }

    public function resetMaterialForm()
    {
        $this->selectedMaterialId = null;
        $this->materialTitle = '';
        $this->materialType = 'youtube';
        $this->materialDescription = '';
        $this->materialYoutubeUrl = null;
        $this->materialVideoId = null;
        $this->materialPdfFile = null;
        $this->materialPdfPath = null;
        $this->materialThumbnailFile = null;
        $this->materialThumbnailPath = null;
        $this->materialSortOrder = 0;
        $this->materialIsArchived = false;
        $this->materialIsRequired = false;
    }

    public function render()
    {
        return view('livewire.content-cms', [
            'courses' => Course::with(['category', 'lessons'])
                ->orderBy('type')
                ->orderBy('title')
                ->paginate(10, pageName: 'coursesPage'),
            'categories' => CourseCategory::orderBy('title')->get(),
            'lessons' => $this->selectedCourseId
                ? Lesson::where('course_id', $this->selectedCourseId)
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->paginate(10, pageName: 'lessonsPage')
                : [],
            'units' => $this->selectedLessonId
                ? Unit::where('lesson_id', $this->selectedLessonId)
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->paginate(10, pageName: 'unitsPage')
                : [],
            'materials' => $this->selectedUnitId
                ? LearningMaterial::where('unit_id', $this->selectedUnitId)
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->paginate(10, pageName: 'materialsPage')
                : [],
        ]);
    }
}