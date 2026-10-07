<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DiscountEvent extends Model
{
    protected $fillable = [
        'name',
        'discount_type',
        'discount_value',
        'banner_text',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<SponsorProduct, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(SponsorProduct::class, 'discount_event_products')
            ->withPivot(['custom_discount_type', 'custom_discount_value'])
            ->withTimestamps();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();
        $startThreshold = $now->copy()->addMinutes(1);

        return $query->where('is_active', true)
            ->where(function ($q) use ($startThreshold) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $startThreshold);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }

    public function isOngoing(): bool
    {
        $now = now();
        return (bool)$this->is_active 
            && ($this->start_at === null || $this->start_at <= $now->copy()->addMinutes(1)) 
            && ($this->end_at === null || $this->end_at >= $now);
    }

    public function isScheduled(): bool
    {
        return (bool)$this->is_active && $this->start_at !== null && $this->start_at > now()->copy()->addMinutes(1);
    }

    public function isExpired(): bool
    {
        return $this->end_at !== null && $this->end_at < now();
    }
}
