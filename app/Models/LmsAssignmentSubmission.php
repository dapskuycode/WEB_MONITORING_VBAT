<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAssignmentSubmission extends Model
{
    protected $table = 'lms_assignment_submissions';

    protected $guarded = [];

    protected $casts = [
        'version' => 'integer',
        'files_snapshot' => 'array',
        'assignment_snapshot' => 'array',
        'grade_version' => 'integer',
        'published_revision' => 'integer',
        'score' => 'float',
        'score_percent' => 'float',
        'is_passed' => 'boolean',
        'is_late' => 'boolean',
        'submitted_at' => 'datetime',
        'returned_at' => 'datetime',
        'resubmit_allowed' => 'boolean',
        'resubmit_deadline_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<LmsAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(LmsAssignment::class, 'assignment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
