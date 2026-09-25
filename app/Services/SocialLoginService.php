<?php

namespace App\Services;

use App\Models\OAuthProvider;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialLoginService
{
    public function handleCallback(string $provider, array $providerUser): array
    {
        // Extract provider data
        $providerId = $providerUser['id'];
        $email = $providerUser['email'] ?? null;
        $name = $providerUser['name'] ?? $providerUser['email'] ?? 'User';
        $avatar = $providerUser['avatar'] ?? null;

        // Find existing OAuth provider link
        $oauth = OAuthProvider::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($oauth) {
            // Update token if needed
            $oauth->update([
                'access_token' => $providerUser['access_token'] ?? null,
                'refresh_token' => $providerUser['refresh_token'] ?? null,
                'expires_at' => $providerUser['expires_at'] ?? null,
            ]);

            $user = $oauth->user;
            $isNew = false;
        } else {
            // No OAuth link found — link to existing user or create new
            $user = $email
                ? User::where('email', $email)->first()
                : null;

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $name,
                    'email' => $email ?? $this->generateTempEmail($providerId),
                    'password' => Hash::make(Str::random(40)),
                    'role' => 'student',
                ]);

                $isNew = true;
            } else {
                $isNew = false;
            }

            // Link OAuth provider
            OAuthProvider::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $providerId,
                'provider_email' => $email,
                'access_token' => $providerUser['access_token'] ?? null,
                'refresh_token' => $providerUser['refresh_token'] ?? null,
                'expires_at' => $providerUser['expires_at'] ?? null,
            ]);
        }

        return [
            'user' => $user,
            'is_new' => $isNew,
            'profile_completed' => $user->profile_completed,
        ];
    }

    public function checkProfileComplete(User $user): bool
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

        foreach ($required as $field) {
            if (empty($user->{$field})) {
                return false;
            }
        }

        return true;
    }

    private function generateTempEmail(string $providerId): string
    {
        return $providerId . '@' . Str::random(8) . '.temp.vbat.local';
    }
}
