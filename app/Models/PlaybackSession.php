<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybackSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'lms_playback_sessions';

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = ['last_seen_at' => 'datetime', 'expires_at' => 'datetime', 'ended_at' => 'datetime', 'duration_ms' => 'integer', 'last_sequence' => 'integer', 'last_position_ms' => 'integer', 'material_revision' => 'integer'];
}
