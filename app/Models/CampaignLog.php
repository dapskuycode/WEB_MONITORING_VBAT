<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'sponsor_product_id',
        'user_id',
        'event_type',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product()
    {
        return $this->belongsTo(SponsorProduct::class, 'sponsor_product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
