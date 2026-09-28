<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /**
     * Register a new user and issue a Sanctum token.
     *
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'role'              => 'student',
            'profile_completed' => false,
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
                'token' => $token,
                'type'  => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Issue a Sanctum token for the user.
     *
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data'    => [
                'user'  => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'role'       => $user->role,
                ],
                'token' => $token,
                'type'  => 'Bearer',
            ],
        ]);
    }

    /**
     * Revoke current token (logout).
     *
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user profile.
     *
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['province', 'city', 'membership']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'role'              => $user->role,
                'birth_date'        => $user->birth_date?->format('Y-m-d'),
                'gender'            => $user->gender,
                'phone'             => $user->phone,
                'whatsapp'          => $user->whatsapp,
                'address'           => $user->address,
                'province_id'       => $user->province_id,
                'province'          => $user->province?->name,
                'city_id'           => $user->city_id,
                'city'              => $user->city?->name,
                'district'          => $user->district,
                'village'           => $user->village,
                'instagram'         => $user->instagram,
                'facebook'          => $user->facebook,
                'tiktok'            => $user->tiktok,
                'youtube'           => $user->youtube,
                'profile_completed' => (bool)$user->isProfileComplete(),
                'membership'        => $user->membership?->membership_number,
                'is_active_member'  => $user->membership?->isActive() ?? false,
                'created_at'        => $user->created_at?->toIso8601String(),
            ],
        ]);
    }
}
