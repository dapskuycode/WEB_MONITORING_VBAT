<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OAuthController extends Controller
{
    public function __construct(
        private SocialLoginService $socialLoginService
    ) {}

    public function callback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,apple,facebook',
            'provider_user' => 'required|array',
            'provider_user.id' => 'required|string',
            'provider_user.email' => 'nullable|email',
            'provider_user.name' => 'nullable|string',
            'provider_user.access_token' => 'nullable|string',
            'provider_user.refresh_token' => 'nullable|string',
            'provider_user.expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $provider = $request->input('provider');
        $providerUser = $request->input('provider_user');

        try {
            $result = $this->socialLoginService->handleCallback($provider, $providerUser);

            $user = $result['user'];
            $token = $user->createToken('mobile-app')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => $result['is_new'] ? 'User registered successfully' : 'Login successful',
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'profile_completed' => $result['profile_completed'],
                    ],
                    'is_new_user' => $result['is_new'],
                    'requires_profile_completion' => !$result['profile_completed'],
                ],
            ], $result['is_new'] ? 201 : 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'OAuth login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function checkProfileStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $profileComplete = $this->socialLoginService->checkProfileComplete($user);

        if ($profileComplete && !$user->profile_completed) {
            $user->update(['profile_completed' => true]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile_completed' => $profileComplete,
                'missing_fields' => $this->getMissingFields($user),
            ],
        ]);
    }

    private function getMissingFields($user): array
    {
        $required = [
            'name',
            'email',
            'birth_date',
            'gender',
            'phone',
            'province_id',
            'city_id',
            'address',
        ];

        $missing = [];
        foreach ($required as $field) {
            if (empty($user->{$field})) {
                $missing[] = $field;
            }
        }

        return $missing;
    }
}
