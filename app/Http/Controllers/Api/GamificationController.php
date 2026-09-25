<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Achievement;
use App\Models\Streak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    public function getAchievements(Request $request): JsonResponse
    {
        $user = $request->user();
        $achievements = $user->achievements()->with('badge')->latest('unlocked_at')->get();

        return response()->json([
            'success' => true,
            'data' => $achievements->map(fn($ach) => [
                'id' => $ach->id,
                'badge' => [
                    'code' => $ach->badge->code,
                    'name' => $ach->badge->name,
                    'description' => $ach->badge->description,
                    'icon_url' => $ach->badge->icon_url,
                    'xp_reward' => $ach->badge->xp_reward,
                ],
                'unlocked_at' => $ach->unlocked_at->toISOString(),
            ]),
        ]);
    }

    public function getStreaks(Request $request): JsonResponse
    {
        $user = $request->user();
        $streaks = $user->streaks;

        return response()->json([
            'success' => true,
            'data' => $streaks->map(fn($s) => [
                'type' => $s->type,
                'current_streak' => $s->current_streak,
                'last_activity_date' => $s->last_activity_date->toDateString(),
            ]),
        ]);
    }

    public function getAllBadges(): JsonResponse
    {
        $badges = Badge::all();

        return response()->json([
            'success' => true,
            'data' => $badges->map(fn($b) => [
                'code' => $b->code,
                'name' => $b->name,
                'description' => $b->description,
                'icon_url' => $b->icon_url,
                'xp_reward' => $b->xp_reward,
            ]),
        ]);
    }
}
