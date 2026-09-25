<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'payment_package_id', 'external_reference', 'amount',
        'status', 'midtrans_response', 'payment_method', 'notes',
        'settled_at', 'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'midtrans_response' => 'json',
        'settled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'settlement', 'failed', 'cancelled', 'expired', 'refund'];

    // ─── Relationships ─────────────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PaymentPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PaymentPackage::class);
    }
}
