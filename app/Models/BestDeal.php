<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BestDeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'banner_path',
        'start_at',
        'end_at',
        'is_active',
        'selected_by',
        'selection_type',
        'sponsor_product_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<SponsorProduct, $this>
     */
    public function sponsorProduct(): BelongsTo
    {
        return $this->belongsTo(SponsorProduct::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function selector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    /**
     * @return BelongsToMany<SponsorProduct, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(SponsorProduct::class, 'best_deal_products')
            ->withPivot(['badge_text', 'order'])
            ->withTimestamps()
            ->orderBy('best_deal_products.order');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
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
