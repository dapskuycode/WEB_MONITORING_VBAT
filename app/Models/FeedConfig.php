<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_type',
        'banner_type',
        'insertion_interval',
        'is_active',
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
}
