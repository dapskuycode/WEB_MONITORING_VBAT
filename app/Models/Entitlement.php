<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entitlement extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_REVOKED = 'revoked';

    public const PACKAGE_ANDROID = 'android';
    public const PACKAGE_IPHONE = 'iphone';
    public const PACKAGE_BUNDLING = 'bundling';
    public const PACKAGE_HARDWARE_SOLUTION = 'hardware_solution';
    public const PACKAGE_PREMIUM = 'premium';

    protected $fillable = [
        'user_id',
        'package_type',
        'starts_at',
        'expires_at',
        'status',
        'source',
        'transaction_reference',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this entitlement is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Scope: only active and non-expired.
     *
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if user has access to a package type.
     */
    public static function hasAccess(int $userId, string $packageType): bool
    {
        return self::where('user_id', $userId)
            ->where('package_type', $packageType)
            ->active()
            ->exists();
    }

    /**
     * Check if user has access to ANY of the given package types.
     */
    public static function hasAnyAccess(int $userId, array $packageTypes): bool
    {
        return self::where('user_id', $userId)
            ->whereIn('package_type', $packageTypes)
            ->active()
            ->exists();
    }
}
