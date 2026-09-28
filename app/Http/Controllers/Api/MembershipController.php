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

    /**
     * Activate membership upon purchase from mobile app.
     */
    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'email' => 'nullable|string|email',
            'package' => 'required|string',
        ]);

        $user = $request->user('sanctum') ?? $request->user();
        if (! $user && $request->filled('email')) {
            $user = \App\Models\User::where('email', $request->input('email'))->first();
        }
        if (! $user && ! empty($validated['user_id'])) {
            $user = \App\Models\User::find($validated['user_id']);
        }
        if (! $user) {
            $user = \App\Models\User::where('role', 'student')->first();
        }

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $package = strtolower($validated['package']);

        // 1. Create or update Membership
        $membershipNumber = $user->membership?->membership_number ?? Membership::generateMembershipNumber();
        $membership = Membership::updateOrCreate(
            ['user_id' => $user->id],
            [
                'membership_number' => $membershipNumber,
                'status' => 'active',
                'purchase_type' => $package,
                'issued_at' => now(),
                'expires_at' => null, // permanent
            ]
        );

        // 2. Grant Entitlements
        $courseTypes = match($package) {
            'bundling' => ['android', 'iphone', 'bundling'],
            'android' => ['android'],
            'iphone' => ['iphone'],
            default => [$package],
        };

        foreach ($courseTypes as $cType) {
            \App\Models\Entitlement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'package_type' => $cType,
                ],
                [
                    'source' => 'purchase',
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Membership KTA dan hak akses berhasil diaktifkan di database',
            'data' => [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'membership_number' => $membership->membership_number,
                'status' => $membership->status,
                'purchase_type' => $membership->purchase_type,
                'issued_at' => $membership->issued_at->toIso8601String(),
                'is_permanent' => true,
                'is_active' => true,
            ],
        ]);
    }
}
