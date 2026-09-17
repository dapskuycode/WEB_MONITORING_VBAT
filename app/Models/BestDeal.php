<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BestDeal extends Model
{
    protected $fillable = [
        'title',
        'description',
        'banner_path',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(SponsorProduct::class, 'best_deal_products')
            ->withPivot(['badge_text', 'order'])
            ->withTimestamps()
            ->orderBy('best_deal_products.order');
    }

    public function scopeActive($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            });
    }
}
