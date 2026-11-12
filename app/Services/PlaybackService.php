<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\PlaybackProgress;
use App\Models\PlaybackSession;
use App\Models\PlaybackSource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaybackService
{
    public function __construct(private EntitlementService $access, private YouTubeValidationService $youtube) {}

    /** @return array<string, mixed> */
    public function start(User $user, LearningMaterial $material, string $requestId): array
    {
        abort_unless($material->material_type === 'youtube_video' && $this->access->canAccessMaterial($user, $material), 403);
        $videoId = $material->youtube_video_id;
        $revision = $material->revision;
        $duration = $this->youtube->playbackDurationMs($videoId ?? '');
        $hash = hash('sha256', $videoId.':'.$duration);

        return DB::transaction(function () use ($user, $material, $requestId, $videoId, $revision, $duration, $hash) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $material = LearningMaterial::lockForUpdate()->findOrFail($material->id);
            abort_unless($material->revision === $revision && $material->youtube_video_id === $videoId, 409, 'Sumber berubah. Mulai sesi baru.');
            abort_unless($this->access->canAccessMaterial($user, $material), 403);
            $existing = PlaybackSession::where('user_id', $user->id)->where('request_id', $requestId)->first();
            if ($existing) {
                abort_unless($existing->material_id === $material->id && $existing->content_hash === $hash && $existing->material_revision === $revision
                    && ! $existing->ended_at && $existing->expires_at->isFuture(), 409, 'Permintaan sesi sudah tidak berlaku.');

                return $this->snapshot($existing, PlaybackProgress::findOrFail($existing->progress_id), true);
            }
            PlaybackSource::updateOrCreate(['material_id' => $material->id], ['video_id' => $videoId, 'duration_ms' => $duration, 'content_hash' => $hash]);
            $progress = PlaybackProgress::firstOrCreate(['user_id' => $user->id, 'material_id' => $material->id, 'content_hash' => $hash], ['ranges' => []]);
            PlaybackSession::where('user_id', $user->id)->whereNull('ended_at')->update(['ended_at' => now()]);
            $session = PlaybackSession::create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'material_id' => $material->id,
                'progress_id' => $progress->id, 'request_id' => $requestId, 'content_hash' => $hash, 'video_id' => $videoId,
                'duration_ms' => $duration, 'material_revision' => $revision, 'last_sequence' => 0,
                'last_position_ms' => $progress->completed_at ? 0 : $progress->resume_ms, 'last_state' => 'paused',
                'last_seen_at' => now(), 'expires_at' => now()->addSeconds(90)]);

            return $this->snapshot($session, $progress, true);
        });
    }

    /**
     * @param  array{event_id: string, sequence: int, position_seconds: int|float, state: string}  $data
     * @return array<string, mixed>
     */
    public function event(User $user, string $id, array $data): array
    {
        $result = DB::transaction(function () use ($user, $id, $data) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $session = PlaybackSession::whereKey($id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $material = LearningMaterial::lockForUpdate()->find($session->material_id);
            $source = PlaybackSource::where('material_id', $session->material_id)->first();
            if ($session->ended_at || ! $session->expires_at->isFuture() || ! $material || ! $this->access->canAccessMaterial($user, $material)
                || $material->material_type !== 'youtube_video' || $material->revision !== $session->material_revision || $material->youtube_video_id !== $session->video_id
                || $source?->content_hash !== $session->content_hash) {
                $session->update(['ended_at' => now()]);

                return ['invalid' => true];
            }
            $position = (int) round($data['position_seconds'] * 1000);
            abort_if($position > $session->duration_ms, 422, 'Posisi melebihi durasi.');
            $hash = hash('sha256', json_encode([$data['sequence'], $position, $data['state']], JSON_THROW_ON_ERROR));
            $replay = DB::table('lms_playback_events')->where('session_id', $id)->where('event_id', $data['event_id'])->first();
            if ($replay) {
                abort_unless($replay->payload_hash === $hash, 409, 'Identitas event telah dipakai.');

                return json_decode($replay->response, true, 512, JSON_THROW_ON_ERROR);
            }
            abort_unless($data['sequence'] === $session->last_sequence + 1, 409, 'Urutan event tidak sesuai.');
            $progress = PlaybackProgress::lockForUpdate()->findOrFail($session->progress_id);
            $elapsed = max(0, (int) $session->last_seen_at->diffInMilliseconds(now()));
            $delta = $position - $session->last_position_ms;
            $resync = false;
            if ($delta > 0) {
                $plausible = $session->last_state === 'playing' && $elapsed <= 15000 && $delta <= $elapsed * 1.25 + 350;
                if ($plausible) {
                    // Wall-clock cap prevents repeated jitter-sized reports from minting watch time.
                    $length = min($delta, $elapsed);
                    $ranges = $this->merge([...$progress->ranges, [$position - $length, $position]]);
                    $progress->ranges = $ranges;
                    $progress->covered_ms = array_sum(array_map(fn ($range) => $range[1] - $range[0], $ranges));
                    $progress->max_position_ms = max($progress->max_position_ms, $position);
                } else {
                    $resync = true;
                    $position = $session->last_position_ms;
                }
            }
            $progress->resume_ms = $position;
            if ($progress->covered_ms >= $session->duration_ms * .95 && $progress->max_position_ms >= $session->duration_ms * .95) {
                $progress->completed_at ??= now();
            }
            $progress->save();
            $session->update(['last_sequence' => $data['sequence'], 'last_position_ms' => $position,
                'last_state' => $resync ? 'paused' : $data['state'], 'last_seen_at' => now(), 'expires_at' => now()->addSeconds(90)]);
            $response = [...$this->snapshot($session, $progress), 'event_id' => $data['event_id'], 'needs_resync' => $resync];
            DB::table('lms_playback_events')->insert(['session_id' => $id, 'event_id' => $data['event_id'], 'sequence' => $data['sequence'],
                'payload_hash' => $hash, 'response' => json_encode($response, JSON_THROW_ON_ERROR)]);

            return $response;
        });
        abort_if(isset($result['invalid']), 409, 'Sesi berakhir atau akses berubah. Mulai sesi baru.');

        return $result;
    }

    /**
     * @param  list<array{0: int, 1: int}>  $ranges
     * @return list<array{0: int, 1: int}>
     */
    private function merge(array $ranges): array
    {
        usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($ranges as [$start, $end]) {
            if ($end <= $start) {
                continue;
            }
            $last = count($merged) - 1;
            if (isset($merged[$last]) && $start <= $merged[$last][1]) {
                $merged[$last] = [$merged[$last][0], max($merged[$last][1], $end)];
            } else {
                $merged[] = [$start, $end];
            }
        }

        return $merged;
    }

    /** @return array<string, mixed> */
    private function snapshot(PlaybackSession $session, PlaybackProgress $progress, bool $start = false): array
    {
        $result = ['session_id' => $session->id, 'sequence' => $session->last_sequence,
            'duration_seconds' => $session->duration_ms / 1000, 'resume_position' => $session->last_position_ms / 1000,
            'progress' => ['available' => true, 'percent' => round($progress->covered_ms / $session->duration_ms * 100, 2),
                'covered_seconds' => $progress->covered_ms / 1000, 'completed' => $progress->completed_at !== null]];
        if ($start) {
            $result['video_id'] = $session->video_id;
        }

        return $result;
    }
}
