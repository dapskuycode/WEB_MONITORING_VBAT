<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserWishlist extends Model
{
    protected $fillable = [
        'user_id',
        'sponsor_product_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(SponsorProduct::class, 'sponsor_product_id');
    }
}
