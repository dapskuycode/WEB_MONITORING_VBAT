<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsAssignmentDraft extends Model
{
    protected $table = 'lms_assignment_drafts';

    protected $guarded = [];

    protected $casts = [
        'upload_ids' => 'array',
        'version' => 'integer',
        'allow_resubmit' => 'boolean',
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
