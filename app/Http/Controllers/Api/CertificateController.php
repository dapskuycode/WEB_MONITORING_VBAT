<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $certificates = $user->certificates()->latest('issued_at')->get();

        return response()->json([
            'success' => true,
            'data' => $certificates->map(fn($cert) => [
                'id' => $cert->id,
                'title' => $cert->title,
                'certificate_number' => $cert->certificate_number,
                'type' => $cert->type,
                'issued_at' => $cert->issued_at->toISOString(),
                'expires_at' => $cert->expires_at?->toISOString(),
                'is_valid' => $cert->isValid(),
                'pdf_url' => $cert->pdf_url,
                'qr_image_url' => $cert->qr_image_url,
            ]),
        ]);
    }

    public function show(Request $request, Certificate $certificate): JsonResponse
    {
        if ($certificate->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $certificate->id,
                'title' => $certificate->title,
                'certificate_number' => $certificate->certificate_number,
                'type' => $certificate->type,
                'issued_at' => $certificate->issued_at->toISOString(),
                'expires_at' => $certificate->expires_at?->toISOString(),
                'is_valid' => $certificate->isValid(),
                'pdf_url' => $certificate->pdf_url,
                'qr_data' => $certificate->qr_data,
                'qr_image_url' => $certificate->qr_image_url,
            ],
        ]);
    }

    public function verify(string $certificateNumber): JsonResponse
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->where('is_public', true)
            ->first();

        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate not found or not publicly verifiable',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'certificate_number' => $certificate->certificate_number,
                'title' => $certificate->title,
                'user_name' => $certificate->user->name,
                'type' => $certificate->type,
                'issued_at' => $certificate->issued_at->toISOString(),
                'expires_at' => $certificate->expires_at?->toISOString(),
                'is_valid' => $certificate->isValid(),
            ],
        ]);
    }
}
