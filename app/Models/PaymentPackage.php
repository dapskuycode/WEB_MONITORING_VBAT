<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'description', 'price', 'type', 'subscription_months',
        'entitlements', 'metadata', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'entitlements' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPES = ['one_time', 'subscription'];

    // ─── Relationships ─────────────────────────────────────────────────

    /**
     * @return HasMany<PaymentTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
