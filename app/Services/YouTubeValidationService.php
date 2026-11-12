<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class YouTubeValidationService
{
    public function extractVideoId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        $parts = parse_url($url);
        if (! $parts || ! in_array($parts['scheme'] ?? '', ['https', 'http'], true)) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $id = null;
        if ($host === 'youtu.be') {
            $id = substr($path, 1);
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(?:embed|v|shorts)/([^/]+)$~', $path, $matches)) {
                $id = $matches[1];
            }
        }

        return is_string($id) && preg_match('/^[a-zA-Z0-9_-]{11}$/', $id) ? $id : null;
    }

    /** @return array<string, mixed> */
    public function validateVideoId(string $videoId): array
    {
        $result = ['valid' => false, 'accessible' => false, 'unlisted' => null, 'embed_verified' => false];
        if (! preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId)) {
            return $result;
        }
        try {
            $response = Http::timeout(5)->get('https://www.youtube.com/oembed', [
                'url' => 'https://www.youtube.com/watch?v='.$videoId, 'format' => 'json',
            ]);
            if (! $response->successful() || ! is_string($response->json('title'))) {
                return $result;
            }

            return [...$result, 'valid' => true, 'accessible' => true, 'title' => $response->json('title'),
                'author' => $response->json('author_name'), 'thumbnail' => $this->getThumbnailUrl($videoId),
                'embed_url' => $this->getEmbedUrl($videoId)];
        } catch (\Throwable $e) {
            return $result;
        }
    }

    public function assertEmbeddable(string $videoId): void
    {
        $key = config('services.youtube.api_key');
        $valid = false;
        if (is_string($key) && trim($key) !== '') {
            try {
                $response = Http::timeout(5)->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'status', 'id' => $videoId, 'key' => $key,
                ]);
                $item = $response->json('items.0');
                $valid = $response->successful() && is_array($item)
                    && ($item['id'] ?? null) === $videoId
                    && ($item['status']['embeddable'] ?? false) === true
                    && in_array($item['status']['privacyStatus'] ?? null, ['public', 'unlisted'], true)
                    && ($item['status']['uploadStatus'] ?? null) === 'processed';
            } catch (\Throwable $e) {
                // Provider failures leave content in Draft; credentials are never returned.
            }
        }
        if (! $valid) {
            throw ValidationException::withMessages([
                'youtube_url' => 'Video belum lolos verifikasi embed YouTube. Periksa API key, status video dan izin embed; simpan sebagai Draft.',
            ]);
        }
    }

    public function playbackDurationMs(string $videoId): int
    {
        $key = config('services.youtube.api_key');
        abort_unless(is_string($key) && trim($key) !== '' && preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId), 503, 'Verifikasi durasi video belum tersedia.');
        try {
            $response = Http::timeout(5)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'status,contentDetails', 'id' => $videoId, 'key' => $key,
            ]);
            $item = $response->json('items.0');
            $duration = $item['contentDetails']['duration'] ?? '';
            $seconds = 0;
            if (is_string($duration) && preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/', $duration, $match)) {
                $seconds = (int) ($match[1] ?? 0) * 3600 + (int) ($match[2] ?? 0) * 60 + (int) ($match[3] ?? 0);
            }
            $valid = $response->successful() && ($item['id'] ?? null) === $videoId
                && ($item['status']['embeddable'] ?? false) === true
                && in_array($item['status']['privacyStatus'] ?? null, ['public', 'unlisted'], true)
                && ($item['status']['uploadStatus'] ?? null) === 'processed'
                && $seconds > 0 && $seconds <= 86400;
        } catch (\Throwable $e) {
            abort(503, 'Provider video belum dapat diverifikasi.');
        }
        abort_unless($valid, 503, 'Durasi atau izin embed video belum dapat diverifikasi.');

        return $seconds * 1000;
    }

    public function getThumbnailUrl(string $videoId): string
    {
        return 'https://img.youtube.com/vi/'.$videoId.'/mqdefault.jpg';
    }

    public function getEmbedUrl(string $videoId): string
    {
        return 'https://www.youtube.com/embed/'.$videoId;
    }
}
