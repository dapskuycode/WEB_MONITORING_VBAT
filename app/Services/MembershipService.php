<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MembershipService
{
    public static function createFromPurchase(User $user, array $purchaseData): Membership
    {
        // Idempotent: check if membership exists
        $existing = $user->membership;
        if ($existing && $existing->status === 'active') {
            return $existing;
        }

        $kta = Membership::create([
            'user_id' => $user->id,
            'kta_number' => self::generateKTANumber(),
            'status' => 'active',
            'issued_at' => now(),
            'expires_at' => null, // Permanent per D-013
            'tier' => $purchaseData['tier'] ?? 'basic',
        ]);

        return $kta;
    }

    private static function generateKTANumber(): string
    {
        // Format: KTA-YYYY-RANDOM-CHECKSUM
        $year = now()->year;
        $random = Str::random(6);
        $base = "KTA-{$year}-{$random}";
        $checksum = substr(md5($base), 0, 2);

        return "{$base}-{$checksum}";
    }

    public static function isMembershipValid(User $user): bool
    {
        $membership = $user->membership;

        if (!$membership) {
            return false;
        }

        return $membership->status === 'active' &&
            ($membership->expires_at === null || $membership->expires_at->isFuture());
    }
}
