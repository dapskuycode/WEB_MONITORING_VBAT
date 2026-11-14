<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $attributes = ['grading_mode' => 'hybrid', 'grading_policy' => 'highest', 'shuffle_questions' => false, 'shuffle_options' => false, 'max_attempts' => 0, 'passing_score' => 70, 'weight' => 1, 'time_limit_minutes' => null];

    protected $fillable = [
        'grading_mode', 'grading_policy', 'shuffle_questions', 'shuffle_options', 'placement', 'target_course_id', 'target_module_id', 'is_required', 'weight',
        'code', 'learning_material_id',
        'title',
        'description',
        'max_attempts',
        'passing_score',
        'time_limit_minutes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'shuffle_questions' => 'boolean', 'shuffle_options' => 'boolean', 'is_required' => 'boolean',
        'max_attempts' => 'integer',
        'passing_score' => 'integer',
        'weight' => 'float',
        'time_limit_minutes' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<LearningMaterial, $this>
     */
    public function learningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class);
    }

    /**
     * @return HasMany<QuizQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Check if a user has passed this quiz.
     */
    public function hasPassedBy(int $userId): bool
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->where('is_passed', true)
            ->exists();
    }

    /**
     * Count attempts by a user.
     */
    public function attemptCountBy(int $userId): int
    {
        return $this->attempts()->where('user_id', $userId)->count();
    }

    /**
     * Check if a user has exhausted attempts.
     */
    public function attemptsExhaustedBy(int $userId): bool
    {
        return $this->attemptCountBy($userId) >= $this->max_attempts;
    }
}
