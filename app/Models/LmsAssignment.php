<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsAssignment extends Model
{
    protected $table = 'lms_assignments';

    protected $attributes = [
        'status' => 'draft',
        'sort_order' => 0,
        'is_required' => false,
        'max_score' => 100,
        'passing_score' => 70,
        'weight' => 1,
        'max_files' => 5,
        'max_file_bytes' => 20971520,
        'requires_text' => false,
        'requires_attachment' => false,
        'late_submission_allowed' => false,
        'revision' => 1,
    ];

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
        'max_score' => 'float',
        'passing_score' => 'float',
        'weight' => 'float',
        'rubric' => 'array',
        'allowed_mimes' => 'array',
        'max_files' => 'integer',
        'max_file_bytes' => 'integer',
        'requires_text' => 'boolean',
        'requires_attachment' => 'boolean',
        'due_at' => 'datetime',
        'late_submission_allowed' => 'boolean',
        'late_until' => 'datetime',
        'revision' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<LmsAssignmentSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LmsAssignmentSubmission::class, 'assignment_id')->orderByDesc('version');
    }

    /**
     * @return HasMany<LmsAssignmentDraft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(LmsAssignmentDraft::class, 'assignment_id');
    }
}
