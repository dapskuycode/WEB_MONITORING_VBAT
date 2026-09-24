<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlacementConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'placement_type',
        'tier_id',
        'max_slots',
        'target_probability',
        'share_of_voice',
        'insertion_interval',
        'fallback_behavior',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'target_probability' => 'float',
        'share_of_voice' => 'float',
        'is_active' => 'boolean',
        'max_slots' => 'integer',
        'insertion_interval' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsTo<SponsorTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(SponsorTier::class);
    }

    /**
     * Scope active configs.
     *
     * @param \Illuminate\Database\Eloquent\Builder<self> $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
