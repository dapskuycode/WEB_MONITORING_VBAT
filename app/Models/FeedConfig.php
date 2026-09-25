<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_type',
        'tier_id',
        'banner_type',
        'insertion_interval',
        'max_banners',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForFeed($query, string $feedType)
    {
        return $query->where('feed_type', $feedType);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(SponsorTier::class);
    }
}
