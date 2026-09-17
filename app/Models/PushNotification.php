<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    protected $fillable = [
        'sponsor_id',
        'title',
        'message',
        'deep_link',
        'image_url',
        'target_audience',
        'scheduled_at',
        'sent_at',
        'status',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'success_count' => 'integer',
        'failure_count' => 'integer',
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }
}
