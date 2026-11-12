<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $attributes = ['question_type' => 'mcq'];

    protected $fillable = [
        'question_type', 'answer_aliases', 'rubric',
        'quiz_id',
        'question_text',
        'options',
        'correct_answer',
        'points',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array', 'answer_aliases' => 'array', 'rubric' => 'array',
        'points' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
