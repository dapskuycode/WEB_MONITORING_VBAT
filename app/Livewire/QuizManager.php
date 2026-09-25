<?php

namespace App\Livewire;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\LearningMaterial;
use Livewire\Component;
use Livewire\WithPagination;

class QuizManager extends Component
{
    use WithPagination;

    public $activeTab = 'quizzes'; // 'quizzes', 'questions'
    
    // Quiz properties
    public $selectedQuizId = null;
    public $quizTitle = '';
    public $quizDescription = '';
    public $quizMaterialId = null;
    public $quizMaxAttempts = 3;
    public $quizPassingScore = 70;
    public $quizTimeLimit = 60;
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
        if (!$this->managingQuizId) {
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
            'quizTitle' => 'required|string|max:255',
            'quizDescription' => 'nullable|string',
            'quizMaterialId' => 'nullable|exists:learning_materials,id',
            'quizMaxAttempts' => 'required|integer|min:1',
            'quizPassingScore' => 'required|integer|min:0|max:100',
            'quizTimeLimit' => 'required|integer|min:1',
            'quizIsActive' => 'boolean',
            'quizSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'title' => $this->quizTitle,
            'description' => $this->quizDescription,
            'learning_material_id' => $this->quizMaterialId,
            'max_attempts' => $this->quizMaxAttempts,
            'passing_score' => $this->quizPassingScore,
            'time_limit_minutes' => $this->quizTimeLimit,
            'is_active' => $this->quizIsActive,
            'sort_order' => $this->quizSortOrder,
        ];

        if ($this->selectedQuizId) {
            Quiz::findOrFail($this->selectedQuizId)->update($data);
            session()->flash('message', "Quiz updated.");
        } else {
            Quiz::create($data);
            session()->flash('message', "Quiz created.");
        }

        $this->resetQuizForm();
    }

    public function deleteQuiz($id)
    {
        Quiz::findOrFail($id)->delete();
        session()->flash('message', "Quiz deleted.");
    }

    public function resetQuizForm()
    {
        $this->selectedQuizId = null;
        $this->quizTitle = '';
        $this->quizDescription = '';
        $this->quizMaterialId = null;
        $this->quizMaxAttempts = 3;
        $this->quizPassingScore = 70;
        $this->quizTimeLimit = 60;
        $this->quizIsActive = true;
        $this->quizSortOrder = 0;
    }

    // ===== QUESTION CRUD =====
    public function manageQuestions($quizId)
    {
        $this->managingQuizId = $quizId;
        $this->activeTab = 'questions';
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
        $this->questionOptions = $question->options ?? ['', '', '', ''];
        $this->questionCorrectAnswer = $question->correct_answer;
        $this->questionPoints = $question->points;
        $this->questionSortOrder = $question->sort_order;
    }

    public function saveQuestion()
    {
        $this->validate([
            'questionText' => 'required|string',
            'questionOptions' => 'required|array|min:2',
            'questionOptions.*' => 'required|string|max:500',
            'questionCorrectAnswer' => 'required|integer|min:0|max:' . (count($this->questionOptions) - 1),
            'questionPoints' => 'required|integer|min:1',
            'questionSortOrder' => 'integer|min:0',
        ]);

        $data = [
            'quiz_id' => $this->managingQuizId,
            'question_text' => $this->questionText,
            'options' => array_values($this->questionOptions),
            'correct_answer' => $this->questionCorrectAnswer,
            'points' => $this->questionPoints,
            'sort_order' => $this->questionSortOrder,
        ];

        if ($this->selectedQuestionId) {
            QuizQuestion::findOrFail($this->selectedQuestionId)->update($data);
            session()->flash('message', "Question updated.");
        } else {
            QuizQuestion::create($data);
            session()->flash('message', "Question added.");
        }

        $this->resetQuestionForm();
    }

    public function deleteQuestion($id)
    {
        QuizQuestion::findOrFail($id)->delete();
        session()->flash('message', "Question deleted.");
    }

    public function resetQuestionForm()
    {
        $this->selectedQuestionId = null;
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
