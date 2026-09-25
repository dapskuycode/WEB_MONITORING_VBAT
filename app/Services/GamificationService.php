<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Achievement;
use App\Models\Streak;
use App\Models\User;
use Carbon\Carbon;

class GamificationService
{
    public static function updateStreak(User $user, string $type): Streak
    {
        $streak = $user->streaks()->where('type', $type)->first();
        $today = now()->toDateString();

        if (!$streak) {
            // First streak
            $streak = Streak::create([
                'user_id' => $user->id,
                'type' => $type,
                'current_streak' => 1,
                'last_activity_date' => $today,
            ]);

            return $streak;
        }

        // Idempotent: same day, no increment
        if ($streak->last_activity_date->toDateString() === $today) {
            return $streak;
        }

        $yesterday = now()->subDay()->toDateString();

        if ($streak->last_activity_date->toDateString() === $yesterday) {
            // Consecutive day
            $streak->increment('current_streak');
        } else {
            // Broken streak
            $streak->current_streak = 1;
        }

        $streak->last_activity_date = $today;
        $streak->save();

        return $streak;
    }

    public static function awardBadge(User $user, string $badgeCode, ?string $context = null): ?Achievement
    {
        $badge = Badge::where('code', $badgeCode)->first();

        if (!$badge) {
            return null;
        }

        // Check if already awarded
        $existing = Achievement::where('user_id', $user->id)
            ->where('badge_id', $badge->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Server-side verification: badge logic
        if (!self::verifyBadgeEvent($user, $badgeCode, $context)) {
            return null;
        }

        return Achievement::create([
            'user_id' => $user->id,
            'badge_id' => $badge->id,
            'unlocked_at' => now(),
        ]);
    }

    private static function verifyBadgeEvent(User $user, string $badgeCode, ?string $context): bool
    {
        // Server-side verification logic per badge
        return match ($badgeCode) {
            'first_login' => !Achievement::where('user_id', $user->id)->exists(),
            'quiz_master' => (int)$context >= 10, // Must have 10+ quizzes passed
            'lesson_complete' => (int)$context > 0,
            'streak_7' => $user->streaks()->where('current_streak', '>=', 7)->exists(),
            default => false,
        };
    }
}
