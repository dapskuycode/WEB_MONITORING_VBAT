<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $membership = $user->membership;

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'No active membership found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'membership_number' => $membership->membership_number,
                'status' => $membership->status,
                'purchase_type' => $membership->purchase_type,
                'issued_at' => $membership->issued_at->toISOString(),
                'expires_at' => $membership->expires_at?->toISOString(),
                'is_permanent' => $membership->isPermanent(),
                'is_active' => $membership->isActive(),
                'notes' => $membership->notes,
            ],
        ]);
    }

    public function verify(Request $request, string $membershipNumber): JsonResponse
    {
        $membership = Membership::where('membership_number', $membershipNumber)->first();

        if (!$membership) {
            return response()->json([
                'success' => false,
                'message' => 'Membership not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'membership_number' => $membership->membership_number,
                'user_name' => $membership->user->name,
                'status' => $membership->status,
                'is_active' => $membership->isActive(),
                'issued_at' => $membership->issued_at->toISOString(),
                'expires_at' => $membership->expires_at?->toISOString(),
                'is_permanent' => $membership->isPermanent(),
            ],
        ]);
    }
}
