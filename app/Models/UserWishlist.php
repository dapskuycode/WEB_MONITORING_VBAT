<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWishlist extends Model
{
    protected $fillable = [
        'user_id',
        'sponsor_product_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SponsorProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(SponsorProduct::class, 'sponsor_product_id');
    }
}
