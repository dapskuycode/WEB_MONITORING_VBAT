<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed> $snapshot
 * @property array<int, string|null>|null $answers
 */
class QuizAttempt extends Model
{
    protected $hidden = ['snapshot'];

    protected $fillable = [
        'grade_status', 'grade_version', 'published_revision',
        'status', 'final_reason', 'request_id', 'snapshot', 'flagged_question_ids', 'source_hash', 'version', 'deadline_at',
        'quiz_id',
        'user_id',
        'score',
        'is_passed',
        'answers',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'snapshot' => 'array', 'flagged_question_ids' => 'array', 'version' => 'integer', 'deadline_at' => 'datetime',
        'grade_version' => 'integer', 'published_revision' => 'integer',
        'score' => 'float',
        'is_passed' => 'boolean',
        'answers' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Quiz, $this>
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
