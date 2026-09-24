<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sponsor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'website_url',
        'tier',
        'tier_id',
        'weight',
        'start_date',
        'end_date',
        'is_active',
        'contact_email',
        'phone',
        'whatsapp',
        'address',
        'province_id',
        'city_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'weight' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SponsorProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(SponsorProduct::class);
    }

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<SponsorTier, $this>
     */
    public function sponsorTier(): BelongsTo
    {
        return $this->belongsTo(SponsorTier::class, 'tier_id');
    }

    /**
     * Alias for sponsorTier — used by eager loading (sponsor.tier).
     *
     * @return BelongsTo<SponsorTier, $this>
     */
    public function tier(): BelongsTo
    {
        return $this->sponsorTier();
    }

    /**
     * @return HasMany<SponsorBenefitOverride, $this>
     */
    public function benefitOverrides(): HasMany
    {
        return $this->hasMany(SponsorBenefitOverride::class);
    }

    /**
     * Resolve the effective benefit value for this sponsor.
     * Returns override value if exists, otherwise returns tier default.
     *
     * @param  string  $benefitCategorySlug  Benefit category slug (e.g. 'katalog_produk')
     * @return mixed|null
     */
    public function resolveBenefit(string $benefitCategorySlug): mixed
    {
        $category = BenefitCategory::where('slug', $benefitCategorySlug)->first();

        if (! $category) {
            return null;
        }

        // Check for sponsor-specific override
        $override = $this->benefitOverrides()
            ->where('benefit_category_id', $category->id)
            ->first();

        if ($override && $override->override_value !== null) {
            return $override->override_value;
        }

        // Fall back to tier default
        $tierBenefit = TierBenefit::where('tier_id', $this->tier_id)
            ->where('benefit_category_id', $category->id)
            ->first();

        return $tierBenefit?->value;
    }
}
