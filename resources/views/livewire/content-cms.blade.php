<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Content Management System</h1>
        <p class="text-gray-600 mt-1">Manage courses, lessons, units, and learning materials</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'courses')" 
                class="@if($activeTab === 'courses') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Courses
            </button>
            <button wire:click="$set('activeTab', 'categories')" 
                class="@if($activeTab === 'categories') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Categories
            </button>
            <button wire:click="$set('activeTab', 'lessons')" 
                class="@if($activeTab === 'lessons') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Lessons
            </button>
            <button wire:click="$set('activeTab', 'units')" 
                class="@if($activeTab === 'units') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Units
            </button>
            <button wire:click="$set('activeTab', 'materials')" 
                class="@if($activeTab === 'materials') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Materials
            </button>
        </nav>
    </div>

    {{-- Courses Tab --}}
    @if($activeTab === 'courses')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Course Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedCourseId ? 'Edit' : 'Create' }} Course</h2>
                <form wire:submit.prevent="saveCourse">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title *</label>
                            <input type="text" wire:model="courseTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('courseTitle') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug *</label>
                            <input type="text" wire:model="courseSlug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('courseSlug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type *</label>
                            <select wire:model="courseType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="android">Android</option>
                                <option value="iphone">iPhone</option>
                                <option value="bundling">Bundling</option>
                                <option value="hardware_solution">Hardware Solution</option>
                                <option value="free_class">Free Class</option>
                                <option value="subscription">Subscription</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <select wire:model="courseCategoryId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- None --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price (IDR) *</label>
                            <input type="number" wire:model="coursePrice" step="1000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration Value *</label>
                                <input type="number" wire:model="courseDurationValue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration Unit *</label>
                                <select wire:model="courseDurationUnit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="day">Day</option>
                                    <option value="month">Month</option>
                                    <option value="year">Year</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea wire:model="courseDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="courseIsArchived" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Archived</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="courseIsFree" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Free</span>
                            </label>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ $selectedCourseId ? 'Update' : 'Create' }}
                            </button>
                            @if($selectedCourseId)
                                <button type="button" wire:click="resetCourseForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Course List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Courses</h2>
                <div class="space-y-2">
                    @forelse($courses as $course)
                        <div class="border rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">{{ $course->title }}</h3>
                                    <p class="text-sm text-gray-600">{{ $course->slug }} • {{ ucfirst($course->type) }}</p>
                                    <p class="text-sm text-gray-500">Rp {{ number_format($course->price, 0, ',', '.') }} • {{ $course->duration }}</p>
                                    @if($course->is_archived)
                                        <span class="inline-block px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded">Archived</span>
                                    @endif
                                </div>
                                <div class="flex space-x-2">
                                    <button wire:click="editCourse({{ $course->id }})" class="text-blue-600 hover:text-blue-800">Edit</button>
                                    <button wire:click="deleteCourse({{ $course->id }})" onclick="return confirm('Delete this course?')" class="text-red-600 hover:text-red-800">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No courses yet.</p>
                    @endforelse
                </div>
                <div class="mt-4">
                    {{ $courses->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Categories Tab --}}
    @if($activeTab === 'categories')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Category Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">{{ $selectedCategoryId ? 'Edit' : 'Create' }} Category</h2>
                <form wire:submit.prevent="saveCategory">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title *</label>
                            <input type="text" wire:model="categoryTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('categoryTitle') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug *</label>
                            <input type="text" wire:model="categorySlug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('categorySlug') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Color *</label>
                            <input type="color" wire:model="categoryColor" class="mt-1 block w-20 h-10 rounded-md border-gray-300 shadow-sm">
                            @error('categoryColor') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                {{ $selectedCategoryId ? 'Update' : 'Create' }}
                            </button>
                            @if($selectedCategoryId)
                                <button type="button" wire:click="resetCategoryForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Category List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Categories</h2>
                <div class="space-y-2">
                    @forelse($categories as $category)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 flex justify-between items-center">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 rounded" style="background-color: {{ $category->color ?? '#3b82f6' }}"></div>
                                <div>
                                    <h3 class="font-medium">{{ $category->title }}</h3>
                                    <p class="text-sm text-gray-500">{{ $category->slug }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="editCategory({{ $category->id }})" class="text-blue-600 hover:text-blue-800">Edit</button>
                                <button wire:click="deleteCategory({{ $category->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800">Delete</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No categories yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Lessons Tab --}}
    @if($activeTab === 'lessons')
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Lesson Management</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Select Course First</label>
                <select wire:model="selectedCourseId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Select Course --</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}">{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>

            @if($selectedCourseId)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <div>
                        <h3 class="font-medium mb-3">{{ $selectedLessonId ? 'Edit' : 'Create' }} Lesson</h3>
                        <form wire:submit.prevent="saveLesson" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title *</label>
                                <input type="text" wire:model="lessonTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('lessonTitle') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Slug *</label>
                                <input type="text" wire:model="lessonSlug" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea wire:model="lessonDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort Order *</label>
                                <input type="number" wire:model="lessonSortOrder" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="lessonIsArchived" class="rounded border-gray-300">
                                <span class="ml-2 text-sm">Archived</span>
                            </label>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">{{ $selectedLessonId ? 'Update' : 'Create' }}</button>
                            @if($selectedLessonId)
                                <button type="button" wire:click="resetLessonForm" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                            @endif
                        </form>
                    </div>

                    <div>
                        <h3 class="font-medium mb-3">Lessons</h3>
                        <div class="space-y-2">
                            @forelse($lessons as $lesson)
                                <div class="border rounded p-3 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium">{{ $lesson->title }}</h4>
                                            <p class="text-sm text-gray-500">Order: {{ $lesson->sort_order }}</p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button wire:click="editLesson({{ $lesson->id }})" class="text-blue-600 text-sm">Edit</button>
                                            <button wire:click="deleteLesson({{ $lesson->id }})" onclick="return confirm('Delete?')" class="text-red-600 text-sm">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No lessons yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Units Tab --}}
    @if($activeTab === 'units')
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Unit Management</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Select Lesson First</label>
                <select wire:model="selectedLessonId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Select Lesson --</option>
                    @if($selectedCourseId && $lessons)
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson->id }}">{{ $lesson->title }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            @if($selectedLessonId)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <div>
                        <h3 class="font-medium mb-3">{{ $selectedUnitId ? 'Edit' : 'Create' }} Unit</h3>
                        <form wire:submit.prevent="saveUnit" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title *</label>
                                <input type="text" wire:model="unitTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Code *</label>
                                <input type="text" wire:model="unitCode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type *</label>
                                <select wire:model="unitType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="video">Video</option>
                                    <option value="reading">Reading</option>
                                    <option value="quiz">Quiz</option>
                                    <option value="assignment">Assignment</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort Order *</label>
                                <input type="number" wire:model="unitSortOrder" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="unitIsRequired" class="rounded border-gray-300">
                                <span class="ml-2 text-sm">Required</span>
                            </label>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">{{ $selectedUnitId ? 'Update' : 'Create' }}</button>
                        </form>
                    </div>

                    <div>
                        <h3 class="font-medium mb-3">Units</h3>
                        <div class="space-y-2">
                            @forelse($units as $unit)
                                <div class="border rounded p-3 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium">{{ $unit->title }}</h4>
                                            <p class="text-sm text-gray-500">{{ ucfirst($unit->type) }} • Order: {{ $unit->sort_order }}</p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button wire:click="editUnit({{ $unit->id }})" class="text-blue-600 text-sm">Edit</button>
                                            <button wire:click="deleteUnit({{ $unit->id }})" onclick="return confirm('Delete?')" class="text-red-600 text-sm">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No units yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Materials Tab --}}
    @if($activeTab === 'materials')
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold mb-4">Material Management</h2>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Select Unit First</label>
                <select wire:model="selectedUnitId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">-- Select Unit --</option>
                    @if($selectedLessonId && $units)
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->title }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            @if($selectedUnitId)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    <div>
                        <h3 class="font-medium mb-3">{{ $selectedMaterialId ? 'Edit' : 'Create' }} Material</h3>
                        <form wire:submit.prevent="saveMaterial" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title *</label>
                                <input type="text" wire:model="materialTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type *</label>
                                <select wire:model="materialType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="youtube">YouTube Video</option>
                                    <option value="pdf">PDF Document</option>
                                    <option value="external_link">External Link</option>
                                </select>
                            </div>

                            @if($materialType === 'youtube')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">YouTube URL *</label>
                                    <input type="url" wire:model="materialYoutubeUrl" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="https://www.youtube.com/watch?v=...">
                                    @error('materialYoutubeUrl') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    @if($materialVideoId)
                                        <p class="mt-1 text-sm text-green-600">✓ Video ID: {{ $materialVideoId }}</p>
                                    @endif
                                </div>
                            @endif

                            @if($materialType === 'pdf')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">PDF File *</label>
                                    <input type="file" wire:model="materialPdfFile" accept="application/pdf" class="mt-1 block w-full">
                                    @error('materialPdfFile') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sort Order *</label>
                                <input type="number" wire:model="materialSortOrder" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="materialIsRequired" class="rounded border-gray-300">
                                <span class="ml-2 text-sm">Required</span>
                            </label>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">{{ $selectedMaterialId ? 'Update' : 'Create' }}</button>
                        </form>
                    </div>

                    <div>
                        <h3 class="font-medium mb-3">Materials</h3>
                        <div class="space-y-2">
                            @forelse($materials as $material)
                                <div class="border rounded p-3 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium">{{ $material->title }}</h4>
                                            <p class="text-sm text-gray-500">{{ ucfirst($material->file_type) }} • Order: {{ $material->sort_order }}</p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button wire:click="editMaterial({{ $material->id }})" class="text-blue-600 text-sm">Edit</button>
                                            <button wire:click="deleteMaterial({{ $material->id }})" onclick="return confirm('Delete?')" class="text-red-600 text-sm">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-center py-4">No materials yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
