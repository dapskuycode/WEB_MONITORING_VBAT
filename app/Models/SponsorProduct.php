<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SponsorProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sponsor_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'image_path',
        'shopee_url',
        'tokopedia_url',
        'is_featured',
        'is_active',
        'order',
        'view_count',
        'click_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'view_count' => 'integer',
        'click_count' => 'integer',
    ];

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function wishlists()
    {
        return $this->hasMany(UserWishlist::class);
    }

    public function logs()
    {
        return $this->hasMany(CampaignLog::class);
    }

    /**
     * Hitung Heat Index untuk produk ini (Poin 4.6):
     * Score = (Clicks * 0.5) + (Views * 0.2) + (Wishlists * 0.3)
     */
    public function getHeatIndexAttribute(): float
    {
        $wishlistCount = $this->wishlists()->count();

        return round(($this->click_count * 0.5) + ($this->view_count * 0.2) + ($wishlistCount * 0.3), 2);
    }

    public function discountEvents()
    {
        return $this->belongsToMany(DiscountEvent::class, 'discount_event_products')
            ->withPivot(['custom_discount_type', 'custom_discount_value'])
            ->withTimestamps();
    }

    public function bestDeals()
    {
        return $this->belongsToMany(BestDeal::class, 'best_deal_products')
            ->withPivot(['badge_text', 'order'])
            ->withTimestamps();
    }
}
