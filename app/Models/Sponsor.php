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

    protected static function booted(): void
    {
        static::saving(function (self $sponsor) {
            if (! $sponsor->tier_id && $sponsor->tier) {
                $tierModel = SponsorTier::where('slug', strtolower($sponsor->tier))->first();
                if ($tierModel) {
                    $sponsor->tier_id = $tierModel->id;
                }
            } elseif ($sponsor->tier_id && ! $sponsor->tier) {
                $sponsor->tier = SponsorTier::find($sponsor->tier_id)?->slug;
            }

            // SPONSOR-04: Otomatis sinkronisasi bobot sponsor dari Share of Voice tier
            if ($sponsor->isDirty(['tier_id', 'tier'])) {
                $sov = $sponsor->resolveBenefit('share_of_voice');
                if ($sov !== null && is_numeric($sov)) {
                    $sponsor->weight = (int) $sov;
                }
            }
        });

        static::saved(function (self $sponsor) {
            if ($sponsor->wasChanged(['tier_id', 'tier', 'weight']) || $sponsor->wasRecentlyCreated) {
                $sponsor->syncCampaignWeights();
            }
        });
    }

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'co_branding_header_url',
        'co_branding_splash_url',
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

    protected $appends = [
        'logo_url',
    ];

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        return url('storage/' . ltrim($this->logo_path, '/'));
    }

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
        $tierId = $this->tier_id;
        if (! $tierId && $this->tier) {
            $tierId = SponsorTier::where('slug', strtolower($this->tier))->value('id');
        }

        if (! $tierId) {
            return null;
        }

        $tierBenefit = TierBenefit::where('tier_id', $tierId)
            ->where('benefit_category_id', $category->id)
            ->first();

        return $tierBenefit?->value;
    }

    /**
     * Sinkronisasi bobot kampanye aktif sponsor dengan Share of Voice tier (SPONSOR-04).
     */
    public function syncCampaignWeights(): void
    {
        $sov = $this->resolveBenefit('share_of_voice');
        $weight = ($sov !== null && is_numeric($sov)) ? (int)$sov : (int)($this->weight ?? 0);
        $this->campaigns()->update(['weight' => $weight]);
    }

    /**
     * Ambil batas kuota unggah produk bulanan (SPONSOR-05).
     * Mengembalikan null jika unlimited (Diamond).
     */
    public function getMonthlyProductQuota(): ?int
    {
        // Diamond tier is unlimited
        if (strtolower($this->tier ?? '') === 'diamond') {
            return null;
        }

        $quota = $this->resolveBenefit('kuota_produk');
        if ($quota === null) {
            return null; // Unlimited
        }

        return (int)$quota;
    }

    /**
     * Hitung total produk yang diunggah pada bulan kalender berjalan (SPONSOR-05).
     */
    public function getCurrentMonthProductsCount(): int
    {
        return $this->products()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    /**
     * Cek apakah sponsor telah memenuhi batas kuota produk bulan ini (SPONSOR-05).
     */
    public function hasReachedProductQuota(): bool
    {
        $max = $this->getMonthlyProductQuota();
        if ($max === null) {
            return false; // Unlimited untuk Diamond
        }

        return $this->getCurrentMonthProductsCount() >= $max;
    }

    /**
     * Ambil kuota slot Best Deal sponsor (SPONSOR-06).
     * Diamond: null (Unlimited), Kontribusi: 0 (Disabled).
     */
    public function getBestDealSlotQuota(): ?int
    {
        if (strtolower($this->tier ?? '') === 'kontribusi') {
            return 0;
        }

        if (strtolower($this->tier ?? '') === 'diamond') {
            return null; // Unlimited
        }

        $slot = $this->resolveBenefit('best_deal_slot');
        return $slot !== null ? (int)$slot : 1;
    }
}
