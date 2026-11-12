<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\LmsUpload;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LmsReadingService
{
    public function __construct(private EntitlementService $access, private LmsUploadService $uploads, private MediaProxyService $media) {}

    /**
     * @return array<string, mixed>
     */
    public function start(User $user, int $id, string $requestId): array
    {
        return DB::transaction(function () use ($user, $id, $requestId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $material = LearningMaterial::lockForUpdate()->findOrFail($id);
            $upload = $this->authorize($user, $material);
            $existing = DB::table('lms_reading_sessions')->where('user_id', $user->id)->where('request_id', $requestId)->first();
            if ($existing) {
                abort_unless($existing->material_id === $id && $existing->source_hash === $upload->sha256 && $existing->revision === $material->revision && now()->lessThan($existing->expires_at), 409, 'Sesi bacaan sudah berubah atau berakhir.');

                return $this->snapshot($existing, $upload, true);
            }
            $identity = ['user_id' => $user->id, 'material_id' => $id, 'source_hash' => $upload->sha256];
            $progress = DB::table('lms_reading_progress')->where($identity)->first();
            $progressId = $progress->id ?? DB::table('lms_reading_progress')->insertGetId([...$identity, 'last_page' => 1, 'max_page' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $sessionId = (string) Str::uuid();
            DB::table('lms_reading_sessions')->insert(['id' => $sessionId, 'user_id' => $user->id, 'material_id' => $id, 'progress_id' => $progressId,
                'upload_id' => $upload->id, 'request_id' => $requestId, 'source_hash' => $upload->sha256, 'revision' => $material->revision,
                'sequence' => 0, 'expires_at' => now()->addMinutes(30), 'created_at' => now(), 'updated_at' => now()]);

            return $this->snapshot(DB::table('lms_reading_sessions')->where('id', $sessionId)->first(), $upload, true);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function event(User $user, string $id, array $data): array
    {
        return DB::transaction(function () use ($user, $id, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $session = DB::table('lms_reading_sessions')->where('id', $id)->where('user_id', $user->id)->lockForUpdate()->first();
            abort_unless($session !== null, 404);
            $material = LearningMaterial::whereKey($session->material_id)->lockForUpdate()->firstOrFail();
            $upload = $this->authorize($user, $material);
            abort_unless($session->upload_id === $upload->id && $session->source_hash === $upload->sha256 && $session->revision === $material->revision && now()->lessThan($session->expires_at), 409, 'Sumber atau sesi bacaan berubah.');
            abort_if($data['page'] > $upload->page_count, 422, 'Nomor halaman melebihi dokumen.');
            $hash = hash('sha256', json_encode([$data['sequence'], $data['page'], $data['confirm_read']], JSON_THROW_ON_ERROR));
            $replay = DB::table('lms_reading_events')->where('session_id', $id)->where('event_id', $data['event_id'])->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $hash, 409, 'Identitas event sudah digunakan.');

                return $this->snapshot($session, $upload);
            }
            abort_unless($data['sequence'] === $session->sequence + 1, 409, 'Urutan event tidak sesuai.');
            $progress = DB::table('lms_reading_progress')->where('id', $session->progress_id)->lockForUpdate()->first();
            abort_if($data['confirm_read'] && (! $progress->reached_end_at || $data['page'] !== $upload->page_count), 422, 'Buka halaman terakhir sebelum mengonfirmasi selesai.');
            DB::table('lms_reading_progress')->where('id', $progress->id)->update(['last_page' => $data['page'], 'max_page' => max($progress->max_page, $data['page']),
                'reached_end_at' => $progress->reached_end_at ?? ($data['page'] === $upload->page_count ? now() : null),
                'completed_at' => $progress->completed_at ?? ($data['confirm_read'] ? now() : null), 'updated_at' => now()]);
            DB::table('lms_reading_events')->insert(['session_id' => $id, 'event_id' => $data['event_id'], 'sequence' => $data['sequence'], 'payload_hash' => $hash, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('lms_reading_sessions')->where('id', $id)->update(['sequence' => $data['sequence'], 'expires_at' => now()->addMinutes(30), 'updated_at' => now()]);
            $session->sequence = $data['sequence'];

            return $this->snapshot($session, $upload);
        });
    }

    private function authorize(User $user, LearningMaterial $material): LmsUpload
    {
        abort_unless($material->material_type === 'pdf_document' && $this->access->canAccessMaterial($user, $material), 403);
        abort_unless($material->pdf_upload_id !== null, 422, 'Dokumen lama perlu pemeriksaan ulang sebelum memakai reader.');
        $upload = $this->uploads->ready($material->pdf_upload_id);
        abort_unless($material->pdf_path === $upload->path, 409, 'Referensi dokumen berubah.');

        return $upload;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(\stdClass $session, LmsUpload $upload, bool $start = false): array
    {
        $progress = DB::table('lms_reading_progress')->where('id', $session->progress_id)->first();
        $data = ['session_id' => $session->id, 'sequence' => $session->sequence, 'page_count' => $upload->page_count,
            'resume_page' => $progress->last_page, 'source_hash' => $upload->sha256,
            'progress' => ['available' => true, 'percent' => round($progress->max_page / $upload->page_count * 100, 2),
                'reached_end' => $progress->reached_end_at !== null, 'completed' => $progress->completed_at !== null]];
        if ($start) {
            $data = [...$data, 'media_url' => $this->media->generateProxyUrl('pdf', $session->material_id, $session->user_id), 'size_bytes' => $upload->size_bytes];
        }

        return $data;
    }
}
