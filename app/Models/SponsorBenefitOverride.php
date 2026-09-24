<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsorBenefitOverride extends Model
{
    protected $table = 'sponsor_benefit_overrides';

    protected $fillable = [
        'sponsor_id',
        'benefit_category_id',
        'override_value',
        'overridden_by',
        'reason',
    ];

    protected $casts = [
        'override_value' => 'array',
    ];

    /**
     * @return BelongsTo<Sponsor, $this>
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * @return BelongsTo<BenefitCategory, $this>
     */
    public function benefitCategory(): BelongsTo
    {
        return $this->belongsTo(BenefitCategory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
