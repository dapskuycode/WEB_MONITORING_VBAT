<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'membership_number',
        'status',
        'purchase_type',
        'issued_at',
        'expires_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }

    public static function generateMembershipNumber(string $prefix = 'VBAT'): string
    {
        $date = now()->format('Ym');
        $random = strtoupper(substr(md5(uniqid('', true)), 0, 6));
        return $prefix . '-' . $date . '-' . $random;
    }
}
