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
            'name' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other',
            'province_id' => 'nullable|integer|exists:provinces,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $defaultStudent = User::where('role', 'student')->first();
        $userId = $validated['user_id'] ?? ($defaultStudent ? $defaultStudent->id : 4);
        $user = User::where('id', $userId)->firstOrFail();

        $updateData = ['profile_completed' => true];
        if (! empty($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (! empty($validated['birth_date'])) {
            $updateData['birth_date'] = $validated['birth_date'];
        }
        if (! empty($validated['gender'])) {
            $updateData['gender'] = $validated['gender'];
        }
        if (isset($validated['province_id'])) {
            $updateData['province_id'] = $validated['province_id'];
        }
        if (isset($validated['city_id'])) {
            $updateData['city_id'] = $validated['city_id'];
        }
        if (isset($validated['phone'])) {
            $updateData['phone'] = $validated['phone'];
        }
        if (isset($validated['whatsapp'])) {
            $updateData['whatsapp'] = $validated['whatsapp'];
        }
        if (isset($validated['address'])) {
            $updateData['address'] = $validated['address'];
        }

        $user->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Data demografi berhasil diperbarui ke database',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'age' => $user->age,
                'gender' => $user->gender,
                'city' => $user->city?->name,
                'province' => $user->province?->name,
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
