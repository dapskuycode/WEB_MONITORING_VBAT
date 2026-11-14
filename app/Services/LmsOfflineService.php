<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\LmsUpload;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LmsOfflineService
{
    public function __construct(
        private EntitlementService $access,
        private LmsUploadService $uploads,
        private LmsFileService $files,
    ) {}

    /**
     * Issue or replay an offline reading license for an eligible PDF material.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function issueLicense(User $user, int $materialId, array $input): array
    {
        $data = Validator::make($input, [
            'request_id' => 'required|uuid',
            'device_id' => 'required|string|min:4|max:128|regex:/^[A-Za-z0-9\-_:\.]+$/',
            'device_name' => 'nullable|string|max:160',
            'platform' => 'nullable|string|in:android,ios,macos,web,unknown',
        ])->validate();

        return DB::transaction(function () use ($user, $materialId, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $material = LearningMaterial::with('lesson.course')->lockForUpdate()->findOrFail($materialId);

            if ($material->material_type !== 'pdf_document') {
                abort(422, 'Materi video YouTube hanya dapat diputar secara online dan tidak mendukung unduhan offline.');
            }

            $course = $material->lesson?->course;
            abort_unless($course !== null && $this->access->canAccessMaterial($user, $material), 403, 'Anda tidak memiliki akses aktif ke materi ini.');
            abort_unless($material->pdf_upload_id !== null, 422, 'Dokumen belum memiliki paket berkas terverifikasi.');

            $upload = $this->uploads->ready($material->pdf_upload_id);
            abort_unless($material->pdf_path === $upload->path, 409, 'Referensi dokumen materi telah berubah.');

            $replay = DB::table('lms_offline_licenses')
                ->where('user_id', $user->id)
                ->where('request_id', $data['request_id'])
                ->first();

            if ($replay) {
                abort_unless(
                    (int) $replay->material_id === (int) $material->id && $replay->device_id === $data['device_id'],
                    409,
                    'Request ID lisensi offline sudah digunakan untuk materi/perangkat berbeda.',
                );

                return $this->formatLicense($replay);
            }

            $this->ensureDeviceAuthorized($user, $data['device_id'], $data['device_name'] ?? null, $data['platform'] ?? 'unknown');

            // Expire any stale licenses first
            $this->cleanupExpiredLicensesForUser($user->id);

            $existingActive = DB::table('lms_offline_licenses')
                ->where('user_id', $user->id)
                ->where('device_id', $data['device_id'])
                ->where('material_id', $material->id)
                ->where('status', 'active')
                ->first();

            $quotaBytes = (int) config('lms.offline_quota_bytes', 104857600);
            $currentUsedBytes = (int) DB::table('lms_offline_licenses')
                ->where('user_id', $user->id)
                ->where('device_id', $data['device_id'])
                ->where('status', 'active')
                ->when($existingActive, fn ($q) => $q->where('id', '!=', $existingActive->id))
                ->sum('size_bytes');

            if ($currentUsedBytes + (int) $upload->size_bytes > $quotaBytes) {
                abort(422, 'Kuota penyimpanan offline perangkat telah penuh. Hapus materi offline lain terlebih dahulu.');
            }

            $expiresAt = $this->computeLicenseExpiry($user, $course);
            $now = now();

            if ($existingActive) {
                DB::table('lms_offline_licenses')
                    ->where('id', $existingActive->id)
                    ->update([
                        'upload_id' => $upload->id,
                        'source_hash' => $upload->sha256,
                        'material_revision' => (int) $material->revision,
                        'size_bytes' => (int) $upload->size_bytes,
                        'page_count' => (int) ($upload->page_count ?? 1),
                        'request_id' => $data['request_id'],
                        'issued_at' => $now,
                        'expires_at' => $expiresAt,
                        'updated_at' => $now,
                    ]);
                $licenseId = $existingActive->id;
            } else {
                $licenseId = (string) Str::uuid();
                DB::table('lms_offline_licenses')->insert([
                    'id' => $licenseId,
                    'user_id' => $user->id,
                    'device_id' => $data['device_id'],
                    'material_id' => $material->id,
                    'upload_id' => $upload->id,
                    'source_hash' => $upload->sha256,
                    'material_revision' => (int) $material->revision,
                    'size_bytes' => (int) $upload->size_bytes,
                    'page_count' => (int) ($upload->page_count ?? 1),
                    'status' => 'active',
                    'last_sequence' => 0,
                    'request_id' => $data['request_id'],
                    'issued_at' => $now,
                    'expires_at' => $expiresAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            AuditLog::create([
                'actor_type' => 'user',
                'actor_id' => $user->id,
                'action' => 'lms.offline.license_issued',
                'auditable_type' => LearningMaterial::class,
                'auditable_id' => $material->id,
                'new_values' => [
                    'license_id' => $licenseId,
                    'device_id' => $data['device_id'],
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
                'ip_address' => request()->ip(),
            ]);

            $license = DB::table('lms_offline_licenses')->where('id', $licenseId)->first();

            return $this->formatLicense($license);
        });
    }

    /**
     * Renew an existing offline license if entitlement and content revision are still valid.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function renewLicense(User $user, string $licenseId, array $input): array
    {
        $data = Validator::make($input, [
            'device_id' => 'required|string|min:4|max:128',
        ])->validate();

        return DB::transaction(function () use ($user, $licenseId, $data) {
            $license = DB::table('lms_offline_licenses')->where('id', $licenseId)->lockForUpdate()->first();
            abort_unless($license !== null && (int) $license->user_id === (int) $user->id, 404, 'Lisensi offline tidak ditemukan.');
            abort_unless($license->device_id === $data['device_id'], 403, 'Lisensi terikat pada perangkat berbeda.');
            abort_if($license->status === 'revoked', 403, 'Lisensi offline telah dicabut.');

            $device = DB::table('lms_offline_devices')
                ->where('user_id', $user->id)
                ->where('device_id', $data['device_id'])
                ->first();
            abort_unless($device !== null && $device->status === 'active', 403, 'Perangkat telah dicabut dari akses offline.');

            $material = LearningMaterial::with('lesson.course')->findOrFail($license->material_id);
            $course = $material->lesson?->course;
            if (! $course || ! $this->access->canAccessMaterial($user, $material)) {
                DB::table('lms_offline_licenses')->where('id', $licenseId)->update([
                    'status' => 'revoked',
                    'revoked_at' => now(),
                    'revoke_reason' => 'entitlement_revoked_or_expired',
                    'updated_at' => now(),
                ]);
                abort(403, 'Hak akses kelas sudah berakhir atau dicabut.');
            }

            $upload = $this->uploads->ready($material->pdf_upload_id);
            $expiresAt = $this->computeLicenseExpiry($user, $course);

            DB::table('lms_offline_licenses')->where('id', $licenseId)->update([
                'upload_id' => $upload->id,
                'source_hash' => $upload->sha256,
                'material_revision' => (int) $material->revision,
                'size_bytes' => (int) $upload->size_bytes,
                'page_count' => (int) ($upload->page_count ?? 1),
                'status' => 'active',
                'expires_at' => $expiresAt,
                'updated_at' => now(),
            ]);

            $updated = DB::table('lms_offline_licenses')->where('id', $licenseId)->first();

            return $this->formatLicense($updated);
        });
    }

    /**
     * Revoke an offline license (by owner on logout/remove or by super_admin).
     *
     * @return array<string, mixed>
     */
    public function revokeLicense(User $actor, string $licenseId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($actor, $licenseId, $reason) {
            $license = DB::table('lms_offline_licenses')->where('id', $licenseId)->lockForUpdate()->first();
            abort_unless($license !== null, 404, 'Lisensi offline tidak ditemukan.');
            $isOwner = (int) $license->user_id === (int) $actor->id;
            abort_unless($isOwner || $actor->isSuperAdmin(), 403, 'Tidak diizinkan mencabut lisensi ini.');

            $now = now();
            DB::table('lms_offline_licenses')->where('id', $licenseId)->update([
                'status' => 'revoked',
                'revoked_at' => $now,
                'revoke_reason' => $reason ?: ($isOwner ? 'user_revoked' : 'admin_revoked'),
                'updated_at' => $now,
            ]);

            $updated = DB::table('lms_offline_licenses')->where('id', $licenseId)->first();

            return $this->formatLicense($updated);
        });
    }

    /**
     * Stream the protected PDF binary for a valid active offline license.
     */
    public function downloadFile(User $user, string $licenseId, string $deviceId): StreamedResponse
    {
        $license = DB::table('lms_offline_licenses')->where('id', $licenseId)->first();
        abort_unless($license !== null && (int) $license->user_id === (int) $user->id, 404, 'Lisensi offline tidak ditemukan.');
        abort_unless($license->device_id === $deviceId, 403, 'Perangkat tidak sesuai dengan lisensi offline.');
        abort_if($license->status !== 'active', 403, 'Lisensi offline sudah tidak aktif.');
        abort_if(now()->greaterThanOrEqualTo(Carbon::parse($license->expires_at)), 403, 'Masa berlaku lisensi offline telah habis.');

        $device = DB::table('lms_offline_devices')
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();
        abort_unless($device !== null && $device->status === 'active', 403, 'Perangkat tidak terdaftar atau telah dicabut.');

        $material = LearningMaterial::with('lesson.course')->findOrFail($license->material_id);
        abort_unless($this->access->canAccessMaterial($user, $material), 403, 'Akses materi telah berakhir atau dicabut.');

        $upload = LmsUpload::findOrFail($license->upload_id);
        abort_unless($upload->status === 'ready' && $upload->sha256 === $license->source_hash, 409, 'Berkas sumber materi telah berubah.');

        $realPath = $this->files->path('lms', $upload->path);
        abort_unless($realPath && hash_file('sha256', $realPath) === $license->source_hash, 404, 'Berkas dokumen tidak tersedia di penyimpanan privat.');

        return Storage::disk('lms')->download($upload->path, $upload->original_name ?: "material-{$material->id}.pdf", [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-LMS-License-Id' => $license->id,
            'X-LMS-SHA256' => $license->source_hash,
        ]);
    }

    /**
     * List active offline licenses for the authenticated user and optional device.
     *
     * @return array<string, mixed>
     */
    public function listLicenses(User $user, ?string $deviceId = null): array
    {
        $this->cleanupExpiredLicensesForUser($user->id);

        $query = DB::table('lms_offline_licenses')
            ->where('user_id', $user->id)
            ->when($deviceId, fn ($q) => $q->where('device_id', $deviceId))
            ->orderByDesc('issued_at');

        $items = $query->get()->map(fn ($row) => $this->formatLicense($row))->all();
        $devices = DB::table('lms_offline_devices')
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn ($d) => [
                'device_id' => $d->device_id,
                'device_name' => $d->device_name,
                'platform' => $d->platform,
                'status' => $d->status,
                'last_seen_at' => $d->last_seen_at,
            ])
            ->all();

        return [
            'max_devices' => (int) config('lms.offline_max_devices', 2),
            'quota_bytes' => (int) config('lms.offline_quota_bytes', 104857600),
            'devices' => $devices,
            'licenses' => $items,
        ];
    }

    /**
     * Synchronize queued offline reading progress events with per-event acceptance/rejection.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function syncEvents(User $user, array $input): array
    {
        $data = Validator::make($input, [
            'device_id' => 'required|string|min:4|max:128',
            'client_time' => 'nullable|date',
            'events' => 'required|array|min:1|max:100',
            'events.*.event_id' => 'required|uuid',
            'events.*.license_id' => 'nullable|uuid',
            'events.*.material_id' => 'required|integer|min:1',
            'events.*.sequence' => 'required|integer|min:1',
            'events.*.event_type' => 'required|string|max:48',
            'events.*.occurred_at' => 'required|date',
            'events.*.page' => 'nullable|integer|min:1',
            'events.*.confirm_read' => 'nullable|boolean',
        ])->validate();

        return DB::transaction(function () use ($user, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $now = now();
            $maxAgeSeconds = (int) config('lms.offline_max_event_age_seconds', 86400);
            $maxSkewSeconds = (int) config('lms.offline_clock_skew_seconds', 300);

            $clientClockSkewExceeded = false;
            if (! empty($data['client_time'])) {
                $clientTime = Carbon::parse($data['client_time']);
                if (abs($now->diffInSeconds($clientTime, false)) > $maxSkewSeconds) {
                    $clientClockSkewExceeded = true;
                }
            }

            $device = DB::table('lms_offline_devices')
                ->where('user_id', $user->id)
                ->where('device_id', $data['device_id'])
                ->first();

            $results = [];
            $acceptedCount = 0;
            $rejectedCount = 0;

            foreach ($data['events'] as $event) {
                $eventId = (string) $event['event_id'];
                $eventType = (string) $event['event_type'];
                $materialId = (int) $event['material_id'];
                $sequence = (int) $event['sequence'];
                $licenseId = $event['license_id'] ?? null;
                $page = isset($event['page']) ? (int) $event['page'] : 0;
                $confirmRead = (bool) ($event['confirm_read'] ?? false);
                $occurredAt = Carbon::parse($event['occurred_at']);

                $payload = [
                    'license_id' => $licenseId,
                    'material_id' => $materialId,
                    'sequence' => $sequence,
                    'event_type' => $eventType,
                    'page' => $page,
                    'confirm_read' => $confirmRead,
                    'occurred_at' => $occurredAt->toIso8601String(),
                ];
                $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

                // 1. Check idempotent replay
                $existing = DB::table('lms_offline_sync_events')
                    ->where('user_id', $user->id)
                    ->where('event_id', $eventId)
                    ->first();

                if ($existing) {
                    if ($existing->payload_hash !== $payloadHash) {
                        $rejectedCount++;
                        $results[] = [
                            'event_id' => $eventId,
                            'sequence' => $sequence,
                            'status' => 'rejected',
                            'reason' => 'event_id_conflict',
                            'message' => 'Event ID sudah pernah digunakan dengan muatan berbeda.',
                            'idempotent_replay' => false,
                        ];

                        continue;
                    }

                    if ($existing->status === 'accepted') {
                        $acceptedCount++;
                    } else {
                        $rejectedCount++;
                    }
                    $results[] = [
                        'event_id' => $eventId,
                        'sequence' => $sequence,
                        'status' => $existing->status,
                        'reason' => $existing->reason,
                        'message' => $existing->status === 'accepted'
                            ? 'Event sudah pernah disinkronkan sebelumnya.'
                            : 'Event ditolak pada sinkronisasi sebelumnya.',
                        'idempotent_replay' => true,
                    ];

                    continue;
                }

                // 2. Reject offline quiz or assignment submission attempts
                if ($eventType !== 'reading_progress') {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'offline_assessment_not_allowed',
                        'Kuis dan tugas wajib dikirim secara online melalui verifikasi waktu server.',
                    );
                    $rejectedCount++;

                    continue;
                }

                // 3. Check device authorization
                if (! $device || $device->status !== 'active') {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'device_unauthorized',
                        'Perangkat tidak terdaftar atau telah dicabut dari akses offline.',
                    );
                    $rejectedCount++;

                    continue;
                }

                // 4. Check clock skew & max event age (24h)
                if ($clientClockSkewExceeded || $occurredAt->greaterThan($now->copy()->addSeconds($maxSkewSeconds))) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'clock_skew_exceeded',
                        'Selisih jam perangkat melebihi batas toleransi 5 menit.',
                    );
                    $rejectedCount++;

                    continue;
                }

                if ($occurredAt->lessThan($now->copy()->subSeconds($maxAgeSeconds))) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'event_too_old',
                        'Event offline melebihi batas usia antrean maksimal 24 jam.',
                    );
                    $rejectedCount++;

                    continue;
                }

                // 5. Verify license ownership, device binding, and validity
                $license = $licenseId
                    ? DB::table('lms_offline_licenses')->where('id', $licenseId)->lockForUpdate()->first()
                    : null;

                if (
                    ! $license
                    || (int) $license->user_id !== (int) $user->id
                    || $license->device_id !== $data['device_id']
                    || (int) $license->material_id !== $materialId
                    || $license->status !== 'active'
                    || $occurredAt->greaterThan(Carbon::parse($license->expires_at))
                ) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'license_invalid_or_expired',
                        'Lisensi offline tidak valid, sudah kedaluwarsa, atau telah dicabut.',
                    );
                    $rejectedCount++;

                    continue;
                }

                // 6. Verify current material & course entitlement
                $material = LearningMaterial::with('lesson.course')->find($materialId);
                if (! $material || $material->material_type !== 'pdf_document' || ! $this->access->canAccessMaterial($user, $material)) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'entitlement_revoked_or_expired',
                        'Hak akses paket kelas telah berakhir atau dicabut sebelum sinkronisasi.',
                    );
                    $rejectedCount++;

                    continue;
                }

                $upload = $material->pdf_upload_id ? LmsUpload::find($material->pdf_upload_id) : null;
                if (
                    ! $upload
                    || $upload->status !== 'ready'
                    || $upload->sha256 !== $license->source_hash
                    || (int) $material->revision !== (int) $license->material_revision
                ) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'content_revision_changed',
                        'Dokumen atau revisi materi telah diperbarui di server. Unduh ulang materi terbaru.',
                    );
                    $rejectedCount++;

                    continue;
                }

                // 7. Verify strictly increasing sequence per license
                if ($sequence <= (int) $license->last_sequence) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'out_of_order_sequence',
                        'Urutan sequence event lebih lama dari event terakhir yang sudah diterima.',
                    );
                    $rejectedCount++;

                    continue;
                }

                // 8. Validate page range and completion order
                $pageCount = (int) ($upload->page_count ?? 1);
                if ($page < 1 || $page > $pageCount) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'invalid_page_or_completion_order',
                        'Nomor halaman bacaan melebihi jumlah halaman dokumen.',
                    );
                    $rejectedCount++;

                    continue;
                }

                $identity = [
                    'user_id' => $user->id,
                    'material_id' => $materialId,
                    'source_hash' => $upload->sha256,
                ];
                $progress = DB::table('lms_reading_progress')->where($identity)->lockForUpdate()->first();
                if (! $progress) {
                    $progressId = DB::table('lms_reading_progress')->insertGetId([
                        ...$identity,
                        'last_page' => 1,
                        'max_page' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $progress = DB::table('lms_reading_progress')->where('id', $progressId)->first();
                }

                $reachedEndAt = $progress->reached_end_at ?? ($page === $pageCount ? $occurredAt : null);
                if ($confirmRead && ($reachedEndAt === null || $page !== $pageCount)) {
                    $results[] = $this->recordSyncOutcome(
                        $user->id,
                        $data['device_id'],
                        $licenseId,
                        $materialId,
                        $eventId,
                        $sequence,
                        $eventType,
                        $payload,
                        $payloadHash,
                        $occurredAt,
                        'rejected',
                        'invalid_page_or_completion_order',
                        'Halaman terakhir harus dibuka sebelum mengonfirmasi bacaan selesai.',
                    );
                    $rejectedCount++;

                    continue;
                }

                $completedAt = $progress->completed_at ?? ($confirmRead ? $occurredAt : null);
                $maxPage = max((int) $progress->max_page, $page);

                DB::table('lms_reading_progress')->where('id', $progress->id)->update([
                    'last_page' => $page,
                    'max_page' => $maxPage,
                    'reached_end_at' => $reachedEndAt,
                    'completed_at' => $completedAt,
                    'updated_at' => $now,
                ]);

                DB::table('lms_offline_licenses')->where('id', $license->id)->update([
                    'last_sequence' => $sequence,
                    'updated_at' => $now,
                ]);

                $outcome = $this->recordSyncOutcome(
                    $user->id,
                    $data['device_id'],
                    $licenseId,
                    $materialId,
                    $eventId,
                    $sequence,
                    $eventType,
                    $payload,
                    $payloadHash,
                    $occurredAt,
                    'accepted',
                    null,
                    'Event progres bacaan offline diterima.',
                );
                $outcome['progress'] = [
                    'last_page' => $page,
                    'max_page' => $maxPage,
                    'page_count' => $pageCount,
                    'percent' => round($maxPage / max(1, $pageCount) * 100, 2),
                    'completed' => $completedAt !== null,
                ];
                $results[] = $outcome;
                $acceptedCount++;
            }

            return [
                'server_time' => $now->toIso8601String(),
                'accepted_count' => $acceptedCount,
                'rejected_count' => $rejectedCount,
                'results' => $results,
            ];
        });
    }

    /**
     * Revoke a registered offline device and all of its active licenses.
     */
    public function revokeDevice(User $actor, int $targetUserId, string $deviceId): int
    {
        abort_unless((int) $actor->id === $targetUserId || $actor->isSuperAdmin(), 403);

        return DB::transaction(function () use ($targetUserId, $deviceId) {
            $now = now();
            DB::table('lms_offline_devices')
                ->where('user_id', $targetUserId)
                ->where('device_id', $deviceId)
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'updated_at' => $now,
                ]);

            return DB::table('lms_offline_licenses')
                ->where('user_id', $targetUserId)
                ->where('device_id', $deviceId)
                ->where('status', 'active')
                ->update([
                    'status' => 'revoked',
                    'revoked_at' => $now,
                    'revoke_reason' => 'device_revoked',
                    'updated_at' => $now,
                ]);
        });
    }

    public function cleanupExpiredLicenses(): int
    {
        return DB::table('lms_offline_licenses')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);
    }

    private function cleanupExpiredLicensesForUser(int $userId): void
    {
        DB::table('lms_offline_licenses')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
                'updated_at' => now(),
            ]);
    }

    private function ensureDeviceAuthorized(User $user, string $deviceId, ?string $deviceName, string $platform): void
    {
        $existing = DB::table('lms_offline_devices')
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->first();

        if ($existing) {
            abort_if($existing->status !== 'active', 403, 'Perangkat ini telah dicabut dari akses offline.');
            DB::table('lms_offline_devices')->where('id', $existing->id)->update([
                'device_name' => $deviceName ?? $existing->device_name,
                'platform' => $platform ?: $existing->platform,
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $maxDevices = (int) config('lms.offline_max_devices', 2);
        $activeCount = DB::table('lms_offline_devices')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        if ($activeCount >= $maxDevices) {
            abort(409, "Batas maksimal perangkat offline ({$maxDevices} perangkat) telah tercapai.");
        }

        DB::table('lms_offline_devices')->insert([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'device_name' => $deviceName ?? 'Perangkat VBAT',
            'platform' => $platform,
            'status' => 'active',
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function computeLicenseExpiry(User $user, Course $course): CarbonInterface
    {
        $ttlHours = (int) config('lms.offline_license_ttl_hours', 168);
        $defaultExpiry = now()->addHours($ttlHours);
        $courseAccess = $this->access->courseAccess($user, $course);
        if (! empty($courseAccess['expires_at'])) {
            $entitlementExpiry = Carbon::parse($courseAccess['expires_at']);
            if ($entitlementExpiry->lessThan($defaultExpiry)) {
                return $entitlementExpiry;
            }
        }

        return $defaultExpiry;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function recordSyncOutcome(
        int $userId,
        string $deviceId,
        ?string $licenseId,
        int $materialId,
        string $eventId,
        int $sequence,
        string $eventType,
        array $payload,
        string $payloadHash,
        Carbon $occurredAt,
        string $status,
        ?string $reason,
        string $message,
    ): array {
        $now = now();
        DB::table('lms_offline_sync_events')->insert([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'license_id' => $licenseId,
            'material_id' => $materialId,
            'event_id' => $eventId,
            'sequence' => $sequence,
            'event_type' => $eventType,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'payload_hash' => $payloadHash,
            'occurred_at' => $occurredAt,
            'status' => $status,
            'reason' => $reason,
            'processed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'event_id' => $eventId,
            'sequence' => $sequence,
            'status' => $status,
            'reason' => $reason,
            'message' => $message,
            'idempotent_replay' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatLicense(\stdClass $row): array
    {
        return [
            'license_id' => $row->id,
            'user_id' => (int) $row->user_id,
            'device_id' => $row->device_id,
            'material_id' => (int) $row->material_id,
            'upload_id' => $row->upload_id,
            'source_hash' => $row->source_hash,
            'material_revision' => (int) $row->material_revision,
            'size_bytes' => (int) $row->size_bytes,
            'page_count' => (int) $row->page_count,
            'status' => $row->status,
            'last_sequence' => (int) $row->last_sequence,
            'issued_at' => Carbon::parse($row->issued_at)->toIso8601String(),
            'expires_at' => Carbon::parse($row->expires_at)->toIso8601String(),
            'revoked_at' => $row->revoked_at ? Carbon::parse($row->revoked_at)->toIso8601String() : null,
            'revoke_reason' => $row->revoke_reason,
            'download_url' => url("/api/v1/lms/offline/licenses/{$row->id}/file"),
        ];
    }
}
