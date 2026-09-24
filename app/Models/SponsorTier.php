<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SponsorTier extends Model
{
    use HasFactory;
    protected $fillable = [
        'slug',
        'name',
        'badge_label',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return HasMany<Sponsor, $this>
     */
    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class);
    }

    /**
     * @return BelongsToMany<BenefitCategory, $this>
     */
    public function benefitCategories(): BelongsToMany
    {
        return $this->belongsToMany(BenefitCategory::class, 'tier_benefits', 'tier_id', 'benefit_category_id')
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
}
