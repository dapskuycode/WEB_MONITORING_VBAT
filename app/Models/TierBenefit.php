<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TierBenefit extends Model
{
    protected $table = 'tier_benefits';

    protected $fillable = [
        'tier_id',
        'benefit_category_id',
        'value',
        'label',
        'is_default_awal',
    ];

    protected $casts = [
        'value' => 'array',
        'is_default_awal' => 'boolean',
    ];

    /**
     * @return BelongsTo<SponsorTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(SponsorTier::class);
    }

    /**
     * @return BelongsTo<BenefitCategory, $this>
     */
    public function benefitCategory(): BelongsTo
    {
        return $this->belongsTo(BenefitCategory::class);
    }
}
