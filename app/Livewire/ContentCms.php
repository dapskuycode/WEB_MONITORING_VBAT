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