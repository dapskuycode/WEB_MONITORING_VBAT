<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property list<array{0: int, 1: int}> $ranges */
class PlaybackProgress extends Model
{
    protected $table = 'lms_playback_progress';

    protected $guarded = [];

    protected $attributes = ['covered_ms' => 0, 'max_position_ms' => 0, 'resume_ms' => 0];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = ['ranges' => 'array', 'completed_at' => 'datetime', 'covered_ms' => 'integer', 'max_position_ms' => 'integer', 'resume_ms' => 'integer'];
}
