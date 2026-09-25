<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Quiz Manager</h1>
        <p class="text-gray-600 mt-1">Manage quizzes, questions, and attempt limits</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8">
            <button wire:click="$set('activeTab', 'quizzes')"
                class="@if($activeTab === 'quizzes') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Quizzes
            </button>
            <button wire:click="$set('activeTab', 'questions')" @if(!$managingQuizId) disabled class="opacity-50 cursor-not-allowed" @endif
                class="@if($activeTab === 'questions') border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-4 px-1 border-b-2 font-medium">
                Questions
            </button>
        </nav>
    </div>

    {{-- Quizzes Tab --}}
    @if($activeTab === 'quizzes')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Quiz Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">@if($selectedQuizId) Edit Quiz @else Create Quiz @endif</h2>
                <form wire:submit.prevent="saveQuiz" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title *</label>
                        <input type="text" wire:model="quizTitle" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        @error('quizTitle') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea wire:model="quizDescription" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        @error('quizDescription') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Learning Material</label>
                        <select wire:model="quizMaterialId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- None --</option>
                            @foreach($materials as $mat)
                                <option value="{{ $mat->id }}">{{ $mat->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Max Attempts *</label>
                            <input type="number" wire:model="quizMaxAttempts" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('quizMaxAttempts') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Passing Score % *</label>
                            <input type="number" wire:model="quizPassingScore" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('quizPassingScore') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Time Limit (min) *</label>
                            <input type="number" wire:model="quizTimeLimit" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('quizTimeLimit') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                            <input type="number" wire:model="quizSortOrder" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="quizIsActive" class="rounded border-gray-300">
                            <span class="ml-2 text-sm">Active</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        @if($selectedQuizId) Update @else Create @endif Quiz
                    </button>
                    @if($selectedQuizId)
                        <button type="button" wire:click="resetQuizForm" class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Quizzes List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">All Quizzes</h2>
                <div class="mb-4 grid grid-cols-1 gap-2">
                    <input type="text" wire:model.live="searchTerm" placeholder="Search quizzes..." class="block w-full rounded-md border-gray-300 shadow-sm">
                    <select wire:model.live="materialFilter" class="block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">-- All Materials --</option>
                        @foreach($materials as $mat)
                            <option value="{{ $mat->id }}">{{ $mat->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($quizzes as $quiz)
                        <div class="p-3 bg-gray-50 rounded border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">{{ $quiz->title }}</h3>
                                    <p class="text-xs text-gray-600">{{ $quiz->learningMaterial?->title ?? 'No material' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Max attempts: {{ $quiz->max_attempts }} | Pass score: {{ $quiz->passing_score }}%
                                    </p>
                                </div>
                                <div class="flex gap-1">
                                    <button wire:click="editQuiz({{ $quiz->id }})" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                    <button wire:click="manageQuestions({{ $quiz->id }})" class="text-green-600 hover:text-green-800 text-sm">Questions</button>
                                    <button wire:click="deleteQuiz({{ $quiz->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No quizzes found.</p>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $quizzes->links() }}
                </div>
            </div>
        </div>
    @endif

    {{-- Questions Tab --}}
    @if($activeTab === 'questions' && $managingQuizId)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Question Form --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">@if($selectedQuestionId) Edit Question @else Add Question @endif</h2>
                <form wire:submit.prevent="saveQuestion" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Question Text *</label>
                        <textarea wire:model="questionText" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Enter question..."></textarea>
                        @error('questionText') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium text-gray-700">Options *</label>
                            <button type="button" wire:click="addOption" class="text-sm text-blue-600 hover:text-blue-800">+ Add Option</button>
                        </div>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($questionOptions as $index => $option)
                                <div class="flex gap-2">
                                    <label class="flex items-center flex-shrink-0">
                                        <input type="radio" wire:model="questionCorrectAnswer" value="{{ $index }}" class="rounded border-gray-300">
                                        <span class="ml-1 text-xs text-gray-600">Correct</span>
                                    </label>
                                    <input type="text" wire:model="questionOptions.{{ $index }}" class="flex-1 rounded-md border-gray-300 shadow-sm" placeholder="Option {{ $index + 1 }}">
                                    @if(count($questionOptions) > 2)
                                        <button type="button" wire:click="removeOption({{ $index }})" class="text-red-600 hover:text-red-800">✕</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('questionOptions') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Points *</label>
                            <input type="number" wire:model="questionPoints" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('questionPoints') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                            <input type="number" wire:model="questionSortOrder" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                        @if($selectedQuestionId) Update @else Add @endif Question
                    </button>
                    @if($selectedQuestionId)
                        <button type="button" wire:click="resetQuestionForm" class="w-full px-4 py-2 bg-gray-300 text-gray-700 rounded-md">Cancel</button>
                    @endif
                </form>
            </div>

            {{-- Questions List --}}
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-lg font-semibold mb-4">Questions ({{ count($questions) }})</h2>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @forelse($questions as $question)
                        <div class="p-3 bg-gray-50 rounded border border-gray-200">
                            <h3 class="font-medium text-gray-900 text-sm">{{ $question->question_text }}</h3>
                            <p class="text-xs text-gray-600 mt-1">{{ count($question->options ?? []) }} options | {{ $question->points }} pts</p>
                            <div class="flex gap-1 mt-2">
                                <button wire:click="editQuestion({{ $question->id }})" class="text-blue-600 hover:text-blue-800 text-xs">Edit</button>
                                <button wire:click="deleteQuestion({{ $question->id }})" onclick="return confirm('Delete?')" class="text-red-600 hover:text-red-800 text-xs">Delete</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">No questions yet.</p>
                    @endforelse
                </div>

                <button wire:click="backToQuizzes" class="w-full mt-4 px-4 py-2 bg-gray-300 text-gray-700 rounded-md">
                    ← Back to Quizzes
                </button>
            </div>
        </div>
    @endif
</div>
