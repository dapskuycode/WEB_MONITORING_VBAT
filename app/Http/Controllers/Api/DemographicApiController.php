<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DemographicApiController extends Controller
{
    /**
     * Poin 1.4:
     * Update Kelengkapan Profil (Demografi: Tanggal Lahir, Gender, Domisili)
     */
    public function updateDemographics(Request $request): JsonResponse
    {
        // Normalize gender if Indonesian term is passed
        $genderInput = $request->input('gender');
        if ($genderInput === 'Laki-laki') {
            $genderInput = 'male';
        } elseif ($genderInput === 'Perempuan') {
            $genderInput = 'female';
        }
        $request->merge(['gender' => $genderInput]);

        // Normalize province if province_name is provided
        if ($request->filled('province_name') && ! $request->filled('province_id')) {
            $province = Province::where('name', 'LIKE', '%'.$request->input('province_name').'%')->first();
            if ($province) {
                $request->merge(['province_id' => $province->id]);
            }
        }

        // If city_name is provided but city_id is missing, look up city
        if ($request->filled('city_name') && ! $request->filled('city_id')) {
            $city = City::where('name', 'LIKE', '%'.$request->input('city_name').'%')->first();
            if ($city) {
                $request->merge([
                    'city_id' => $city->id,
                    'province_id' => $request->input('province_id') ?? $city->province_id,
                ]);
            }
        }

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'email' => 'nullable|string|email',
            'name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'district' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
        ]);

        $user = $request->user('sanctum') ?? $request->user();
        if (! $user && $request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }
        if (! $user && ! empty($validated['user_id'])) {
            $user = User::find($validated['user_id']);
        }
        if (! $user) {
            $user = User::where('role', 'student')->first();
        }

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan',
            ], 404);
        }

        $fields = [
            'name', 'birth_date', 'gender', 'province_id', 'city_id',
            'district', 'village', 'phone', 'whatsapp', 'address',
            'instagram', 'facebook', 'tiktok', 'youtube',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $user->{$field} = $validated[$field] ?? $request->input($field);
            }
        }

        // Automatically calculate profile_completed based on mandatory * fields
        $user->profile_completed = $user->isProfileComplete();
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data profil berhasil diperbarui ke database',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'age' => $user->age,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'gender' => $user->gender,
                'phone' => $user->phone,
                'whatsapp' => $user->whatsapp,
                'address' => $user->address,
                'city' => $user->city?->name,
                'province' => $user->province?->name,
                'district' => $user->district,
                'village' => $user->village,
                'instagram' => $user->instagram,
                'facebook' => $user->facebook,
                'tiktok' => $user->tiktok,
                'youtube' => $user->youtube,
                'profile_completed' => (bool)$user->profile_completed,
            ],
        ]);
    }

    public function getProvinces(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => Province::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function getCitiesByProvince(int $provinceId): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => City::where('province_id', $provinceId)->orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }
}
