<?php

namespace App\Services;

use App\Models\User;

class ProfileCompletenessService
{
    public static function isProfileComplete(User $user): bool
    {
        return $user->email
            && $user->name
            && $user->phone
            && $user->birth_date
            && $user->gender
            && $user->province_id
            && $user->city_id
            && $user->address;
    }

    public static function getMissingFields(User $user): array
    {
        $required = [
            'email' => $user->email,
            'name' => $user->name,
            'phone' => $user->phone,
            'birth_date' => $user->birth_date,
            'gender' => $user->gender,
            'province_id' => $user->province_id,
            'city_id' => $user->city_id,
            'address' => $user->address,
        ];

        return array_keys(array_filter($required, fn($v) => !$v));
    }
}
