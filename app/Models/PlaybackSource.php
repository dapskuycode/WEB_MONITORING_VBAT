<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybackSource extends Model
{
    protected $table = 'lms_playback_sources';

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $casts = ['duration_ms' => 'integer'];
}
