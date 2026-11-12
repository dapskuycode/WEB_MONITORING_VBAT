<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SocialLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    public function __construct(
        private SocialLoginService $socialLoginService
    ) {}

    public function callback(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => ['code' => 'OAUTH_UNAVAILABLE'],
            'message' => 'SSO belum tersedia. Gunakan email dan kata sandi.',
        ], 503);
    }

    public function checkProfileStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $profileComplete = $this->socialLoginService->checkProfileComplete($user);

        if ($profileComplete && ! $user->profile_completed) {
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
