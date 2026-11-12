<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LmsCredentialService
{
    public function issue(User $user, Course $course): Certificate
    {
        return DB::transaction(function () use ($user, $course) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $course = Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $existing = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->where('type', 'course_completion')->first();
            if ($existing) {
                return $existing;
            }
            abort_unless(in_array($course->type, ['android', 'iphone', 'free_class'], true) && app(EntitlementService::class)->canAccessCourse($user, $course), 403);
            $curriculum = app(LmsProgressService::class)->curriculum($user, $course);
            abort_unless($curriculum['progress']['completed'], 409, 'Kelas belum tuntas.');
            abort_unless(trim($user->name ?? '') !== '', 422, 'Lengkapi identitas sebelum menerbitkan sertifikat.');
            $token = (string) Str::uuid();
            $number = 'VBAT-'.now()->format('Y').'-'.Str::upper(str_replace('-', '', $token));
            $identity = ['name' => $user->name, 'course_title' => $course->title, 'issued_at' => now()->toIso8601String(), 'number' => $number];
            $certificate = Certificate::create(['user_id' => $user->id, 'course_id' => $course->id, 'title' => 'Kelulusan '.$course->title,
                'certificate_number' => $number, 'type' => 'course_completion', 'issued_at' => now(), 'expires_at' => null,
                'is_public' => false, 'status' => 'valid', 'verification_token' => $token, 'identity_snapshot' => $identity,
                'completion_snapshot' => ['course_revision' => $course->revision, 'progress' => $curriculum['progress'], 'units' => $curriculum['units'], 'quizzes' => $curriculum['quizzes']]]);
            $path = 'certificates/'.$token.'.pdf';
            try {
                $options = new Options(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false,
                    'chroot' => base_path('vendor/dompdf')]);
                $pdf = new Dompdf($options);
                $pdf->loadHtml(view('lms.certificate-document', ['identity' => $identity, 'verification' => $this->verificationUrl($certificate)])->render());
                $pdf->setPaper('A4', 'landscape');
                $pdf->render();
                abort_unless(Storage::disk('lms')->put($path, $pdf->output()) !== false, 503, 'Dokumen belum dapat disimpan.');
                $certificate->update(['document_path' => $path, 'qr_data' => $this->verificationUrl($certificate)]);
                AuditLog::create(['actor_type' => 'system', 'actor_id' => $user->id, 'action' => 'lms.certificate.issued', 'auditable_type' => Certificate::class,
                    'auditable_id' => $certificate->id, 'new_values' => ['number' => $number, 'course_id' => $course->id], 'old_values' => []]);

                return $certificate;
            } catch (\Throwable $e) {
                Storage::disk('lms')->delete($path);
                throw $e;
            }
        });
    }

    public function verificationUrl(Certificate $certificate): string
    {
        return url('/api/v1/lms/credentials/verify/'.$certificate->verification_token);
    }

    /**
     * @return array<string, mixed>
     */
    public function data(Certificate $certificate, bool $public = false): array
    {
        $snapshot = $certificate->identity_snapshot;
        $base = ['id' => $certificate->id, 'title' => $certificate->title, 'type' => $certificate->type, 'number' => $certificate->certificate_number,
            'issued_at' => $certificate->issued_at->toIso8601String(), 'expires_at' => $certificate->expires_at?->toIso8601String(),
            'status' => $certificate->status ?? 'valid', 'is_valid' => $certificate->isValid(), 'learner_name' => $snapshot['name'] ?? $certificate->user->name,
            'course_title' => $snapshot['course_title'] ?? null];
        if (! $public) {
            $base = [...$base, 'is_public' => $certificate->is_public, 'verification_url' => $certificate->verification_token ? $this->verificationUrl($certificate) : null,
                'has_document' => $certificate->document_path !== null, 'revoke_reason' => $certificate->revoke_reason];
        }

        return $base;
    }

    public function visibility(User $user, int $id, bool $visible): Certificate
    {
        return DB::transaction(function () use ($user, $id, $visible) {
            $c = Certificate::whereKey($id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            abort_unless($c->type === 'course_completion', 422);
            if ($c->is_public !== $visible) {
                $c->update(['is_public' => $visible]);
                AuditLog::create(['actor_type' => 'student', 'actor_id' => $user->id, 'action' => 'lms.certificate.visibility', 'auditable_type' => Certificate::class,
                    'auditable_id' => $c->id, 'old_values' => ['is_public' => ! $visible], 'new_values' => ['is_public' => $visible]]);
            }

            return $c;
        });
    }

    public function revoke(User $admin, int $id, string $reason): Certificate
    {
        abort_unless($admin->isSuperAdmin(), 403);

        return DB::transaction(function () use ($admin, $id, $reason) {
            $c = Certificate::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($c->status === 'revoked') {
                return $c;
            }
            $c->update(['status' => 'revoked', 'revoked_at' => now(), 'revoke_reason' => $reason]);
            AuditLog::create(['actor_type' => 'admin', 'actor_id' => $admin->id, 'action' => 'lms.certificate.revoked', 'auditable_type' => Certificate::class,
                'auditable_id' => $c->id, 'old_values' => ['status' => 'valid'], 'new_values' => ['status' => 'revoked', 'reason' => $reason]]);

            return $c;
        });
    }
}
