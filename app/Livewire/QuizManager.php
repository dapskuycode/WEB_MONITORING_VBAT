<?php

namespace App\Livewire;

use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\LmsQuizPlacementService;
use App\Services\QuizDefinitionService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class QuizManager extends Component
{
    use WithPagination;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public string $quizGradingMode = 'hybrid';

    public string $quizGradingPolicy = 'highest';

    public string $quizPlacement = 'after_material';

    public bool $quizRequired = false;

    public bool $quizShuffleQuestions = false;

    public bool $quizShuffleOptions = false;

    public string $questionType = 'mcq';

    public array $questionAliasGroups = [['text' => '', 'weight' => 1]];

    public array $questionRubric = [['id' => 'criterion-1', 'label' => '', 'max_points' => 10]];

    public $activeTab = 'quizzes'; // 'quizzes', 'questions'

    // Quiz properties
    public $selectedQuizId = null;

    public $quizTitle = '';

    public $quizDescription = '';

    public $quizMaterialId = null;

    public $quizMaxAttempts = 0;

    public $quizPassingScore = 70;

    public $quizTimeLimit = null;

    public $quizIsActive = true;

    public $quizSortOrder = 0;

    // Question properties
    public $managingQuizId = null;

    public $selectedQuestionId = null;

    public $questionText = '';

    public $questionOptions = ['', '', '', '']; // Default 4 options

    public $questionCorrectAnswer = 0;

    public $questionPoints = 10;

    public $questionSortOrder = 0;

    public $searchTerm = '';

    public $materialFilter = '';

    // ===== COMPUTED =====
    public function getQuizzesProperty()
    {
        $query = Quiz::with('learningMaterial');

        if ($this->searchTerm) {
            $query->where('title', 'like', "%{$this->searchTerm}%");
        }

        if ($this->materialFilter) {
            $query->where('learning_material_id', $this->materialFilter);
        }

        return $query->orderBy('sort_order')->paginate(10);
    }

    public function getMaterialsProperty()
    {
        return LearningMaterial::select('id', 'title')
            ->orderBy('title')
            ->get();
    }

    public function getQuestionsProperty()
    {
        if (! $this->managingQuizId) {
            return collect();
        }

        return QuizQuestion::where('quiz_id', $this->managingQuizId)
            ->orderBy('sort_order')
            ->get();
    }

    // ===== QUIZ CRUD =====
    public function editQuiz($id)
    {
        $quiz = Quiz::findOrFail($id);
        $this->selectedQuizId = $quiz->id;
        $this->quizGradingMode = $quiz->grading_mode;
        $this->quizGradingPolicy = $quiz->grading_policy;
        $this->quizPlacement = $quiz->placement ?? 'after_material';
        $this->quizRequired = (bool) $quiz->is_required;
        $this->quizShuffleQuestions = $quiz->shuffle_questions;
        $this->quizShuffleOptions = $quiz->shuffle_options;
        $this->quizTitle = $quiz->title;
        $this->quizDescription = $quiz->description ?? '';
        $this->quizMaterialId = $quiz->learning_material_id;
        $this->quizMaxAttempts = $quiz->max_attempts;
        $this->quizPassingScore = $quiz->passing_score;
        $this->quizTimeLimit = $quiz->time_limit_minutes;
        $this->quizIsActive = $quiz->is_active;
        $this->quizSortOrder = $quiz->sort_order;
    }

    public function saveQuiz()
    {
        $this->validate([
            'quizGradingMode' => 'required|in:auto,manual,hybrid', 'quizGradingPolicy' => 'required|in:highest,latest,average', 'quizPlacement' => 'required|in:after_material,end_of_module,end_of_course',
            'quizTitle' => 'required|string|max:255',
            'quizDescription' => 'nullable|string',
            'quizMaterialId' => 'required|exists:learning_materials,id',
            'quizMaxAttempts' => 'required|integer|min:0|max:10',
            'quizPassingScore' => 'required|integer|min:0|max:100',
            'quizTimeLimit' => 'nullable|integer|min:1|max:240',
            'quizIsActive' => 'boolean',
            'quizSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'grading_mode' => $this->quizGradingMode, 'grading_policy' => $this->quizGradingPolicy, 'placement' => $this->quizPlacement, 'is_required' => $this->quizRequired,
            'shuffle_questions' => $this->quizShuffleQuestions, 'shuffle_options' => $this->quizShuffleOptions,
            'title' => $this->quizTitle,
            'description' => $this->quizDescription,
            'learning_material_id' => $this->quizMaterialId,
            'max_attempts' => $this->quizMaxAttempts,
            'passing_score' => $this->quizPassingScore,
            'time_limit_minutes' => $this->quizTimeLimit === '' ? null : $this->quizTimeLimit,
            'is_active' => $this->quizIsActive,
            'sort_order' => $this->quizSortOrder,
        ];

        if ($this->selectedQuizId) {
            $quiz = Quiz::findOrFail($this->selectedQuizId);
            abort_if($quiz->learning_material_id != $this->quizMaterialId && $quiz->attempts()->exists(), 409, 'Kuis dengan attempt tidak dapat dipindah materi.');
            $data = app(LmsQuizPlacementService::class)->configure($quiz, $data);
            $quiz->update($data);
            session()->flash('message', 'Quiz updated.');
        } else {
            Quiz::create(app(LmsQuizPlacementService::class)->configure(new Quiz, $data));
            session()->flash('message', 'Quiz created.');
        }

        $this->resetQuizForm();
    }

    public function deleteQuiz($id)
    {
        $quiz = Quiz::findOrFail($id);
        if ($quiz->attempts()->exists()) {
            $quiz->update(['is_active' => false]);
            session()->flash('message', 'Kuis dinonaktifkan; riwayat attempt tetap tersedia.');
        } else {
            $quiz->delete();
            session()->flash('message', 'Kuis dihapus.');
        }
    }

    public function resetQuizForm()
    {
        $this->reset('quizGradingMode', 'quizGradingPolicy', 'quizPlacement', 'quizRequired', 'quizShuffleQuestions', 'quizShuffleOptions');
        $this->selectedQuizId = null;
        $this->quizTitle = '';
        $this->quizDescription = '';
        $this->quizMaterialId = null;
        $this->quizMaxAttempts = 0;
        $this->quizPassingScore = 70;
        $this->quizTimeLimit = null;
        $this->quizIsActive = true;
        $this->quizSortOrder = 0;
    }

    // ===== QUESTION CRUD =====
    public function manageQuestions($quizId)
    {
        $this->managingQuizId = $quizId;
        $this->activeTab = 'questions';
    }

    public function addAliasGroup(): void
    {
        $this->questionAliasGroups[] = ['text' => '', 'weight' => 1];
    }

    public function removeAliasGroup(int $index): void
    {
        unset($this->questionAliasGroups[$index]);
        $this->questionAliasGroups = array_values($this->questionAliasGroups);
    }

    public function addCriterion(): void
    {
        $this->questionRubric[] = ['id' => (string) Str::uuid(), 'label' => '', 'max_points' => 1];
    }

    public function removeCriterion(int $index): void
    {
        unset($this->questionRubric[$index]);
        $this->questionRubric = array_values($this->questionRubric);
    }

    public function addOption()
    {
        $this->questionOptions[] = '';
    }

    public function removeOption($index)
    {
        if (count($this->questionOptions) > 2) {
            unset($this->questionOptions[$index]);
            $this->questionOptions = array_values($this->questionOptions);
        }
    }

    public function editQuestion($id)
    {
        $question = QuizQuestion::findOrFail($id);
        $this->selectedQuestionId = $question->id;
        $this->questionText = $question->question_text;
        $normalized = app(QuizDefinitionService::class)->question($question->only(['question_type', 'question_text', 'options', 'correct_answer', 'points', 'sort_order', 'answer_aliases', 'rubric']));
        $this->questionType = $normalized['question_type'];
        $this->questionAliasGroups = array_map(fn ($g) => ['text' => implode(', ', $g['aliases']), 'weight' => $g['weight']], $normalized['answer_aliases']);
        $this->questionRubric = $normalized['rubric'];
        $this->questionOptions = array_column($normalized['options'], 'label');
        $this->questionCorrectAnswer = array_search($normalized['correct_answer'], array_column($normalized['options'], 'value'), true);
        $this->questionPoints = $question->points;
        $this->questionSortOrder = $question->sort_order;
    }

    public function saveQuestion()
    {
        $this->validate([
            'questionText' => 'required|string',
            'questionOptions' => $this->questionType === 'mcq' ? 'required|array|min:2|max:6' : 'array',
            'questionOptions.*' => 'string|max:500',
            'questionCorrectAnswer' => $this->questionType === 'mcq' ? 'required|integer|min:0|max:'.(count($this->questionOptions) - 1) : 'nullable',
            'questionPoints' => 'required|integer|min:1|max:1000',
            'questionSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'quiz_id' => $this->managingQuizId,
            'question_type' => $this->questionType, 'answer_aliases' => array_map(fn ($g) => ['aliases' => array_values(array_filter(array_map('trim', explode(',', $g['text'])), fn ($alias) => $alias !== '')), 'weight' => $g['weight']], $this->questionAliasGroups),
            'rubric' => $this->questionRubric,
            'question_text' => $this->questionText,
            'options' => array_values($this->questionOptions),
            'correct_answer' => $this->questionCorrectAnswer,
            'points' => $this->questionPoints,
            'sort_order' => $this->questionSortOrder,
        ];

        $data = ['quiz_id' => $this->managingQuizId, ...app(QuizDefinitionService::class)->question([...$data, 'correct_answer' => (string) $this->questionCorrectAnswer])];
        if ($this->selectedQuestionId) {
            QuizQuestion::where('quiz_id', $this->managingQuizId)->findOrFail($this->selectedQuestionId)->update($data);
            session()->flash('message', 'Question updated.');
        } else {
            QuizQuestion::create($data);
            session()->flash('message', 'Question added.');
        }

        $this->resetQuestionForm();
    }

    public function deleteQuestion($id)
    {
        QuizQuestion::findOrFail($id)->delete();
        session()->flash('message', 'Question deleted.');
    }

    public function resetQuestionForm()
    {
        $this->selectedQuestionId = null;
        $this->reset('questionType', 'questionAliasGroups', 'questionRubric');
        $this->questionText = '';
        $this->questionOptions = ['', '', '', ''];
        $this->questionCorrectAnswer = 0;
        $this->questionPoints = 10;
        $this->questionSortOrder = 0;
    }

    public function backToQuizzes()
    {
        $this->managingQuizId = null;
        $this->activeTab = 'quizzes';
        $this->resetQuestionForm();
    }

    // ===== RENDER =====
    public function render()
    {
        return view('livewire.quiz-manager', [
            'quizzes' => $this->quizzes,
            'materials' => $this->materials,
            'questions' => $this->questions,
        ]);
    }
}
