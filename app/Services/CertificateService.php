<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Support\Str;

class CertificateService
{
    public static function issueCertificate(User $user, array $data): Certificate
    {
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'course_id' => $data['course_id'],
            'certificate_number' => self::generateCertificateNumber(),
            'status' => 'valid',
            'issued_at' => now(),
            'signed_url' => null, // Generated on demand
            'qr_code' => null, // Generated on demand
        ]);

        return $certificate;
    }

    public static function reissueCertificate(Certificate $certificate): Certificate
    {
        $certificate->update([
            'status' => 'valid',
            'issued_at' => now(),
        ]);

        return $certificate;
    }

    public static function revokeCertificate(Certificate $certificate, ?string $reason = null): Certificate
    {
        $certificate->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);

        return $certificate;
    }

    public static function getVerificationUrl(Certificate $certificate): string
    {
        $signature = hash_hmac('sha256', $certificate->certificate_number, config('app.key'));

        return route('api.certificates.verify', [
            'number' => $certificate->certificate_number,
            'sig' => $signature,
        ]);
    }

    public static function verifyCertificate(string $certificateNumber, ?string $signature = null): ?Certificate
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)->first();

        if (!$certificate) {
            return null;
        }

        if ($signature) {
            $expectedSig = hash_hmac('sha256', $certificateNumber, config('app.key'));
            if ($signature !== $expectedSig) {
                return null;
            }
        }

        return $certificate;
    }

    private static function generateCertificateNumber(): string
    {
        // Format: CERT-YYYY-RANDOM-CHECKSUM
        $year = now()->year;
        $random = Str::random(8);
        $base = "CERT-{$year}-{$random}";
        $checksum = substr(md5($base), 0, 3);

        return "{$base}-{$checksum}";
    }
}
