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

    {{-- Other tabs: categories, lessons, units, materials --}}
    {{-- Simplified for now; full implementation would follow similar pattern --}}
    @if($activeTab === 'categories')
        <div class="bg-white p-6 rounded-lg shadow">
            <p class="text-gray-600">Category management UI here (similar pattern to courses).</p>
        </div>
    @endif

    @if($activeTab === 'lessons')
        <div class="bg-white p-6 rounded-lg shadow">
            <p class="text-gray-600">Lesson management UI here (require course selection first).</p>
        </div>
    @endif

    @if($activeTab === 'units')
        <div class="bg-white p-6 rounded-lg shadow">
            <p class="text-gray-600">Unit management UI here (require lesson selection first).</p>
        </div>
    @endif

    @if($activeTab === 'materials')
        <div class="bg-white p-6 rounded-lg shadow">
            <p class="text-gray-600">Material management UI here with YouTube validation & PDF upload.</p>
        </div>
    @endif
</div>
