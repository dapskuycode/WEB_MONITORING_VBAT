<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BenefitCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'value_type',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return BelongsToMany<SponsorTier, $this>
     */
    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(SponsorTier::class, 'tier_benefits')
            ->withPivot(['value', 'label', 'is_default_awal'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<TierBenefit, $this>
     */
    public function tierBenefits(): HasMany
    {
        return $this->hasMany(TierBenefit::class);
    }

    /**
     * @return HasMany<SponsorBenefitOverride, $this>
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(SponsorBenefitOverride::class);
    }
}
