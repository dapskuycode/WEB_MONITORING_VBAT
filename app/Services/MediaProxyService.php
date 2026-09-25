<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MediaProxyService
{
    private const URL_EXPIRY_MINUTES = 60;
    private const SIGNED_URL_KEY = 'media_access_token';

    /**
     * Generate signed URL for private media (PDF, object storage files)
     * Expiry: 1 hour by default
     */
    public function generateSignedUrl(string $path, ?int $expiryMinutes = null): string
    {
        $expiryMinutes ??= self::URL_EXPIRY_MINUTES;
        
        // Use Laravel's URL::signedRoute or build a custom signed URL
        // For now, use temporary signed URL via filesystem disk
        return URL::temporarySignedRoute(
            'api.media.serve',
            now()->addMinutes($expiryMinutes),
            ['path' => base64_encode($path)]
        );
    }

    /**
     * Generate YouTube embed URL dengan expiry check
     * YouTube tidak support signed URLs, jadi gunakan access token via API
     */
    public function generateYouTubeAccessUrl(string $videoId, ?int $expiryMinutes = null): array
    {
        $expiryMinutes ??= self::URL_EXPIRY_MINUTES;
        
        return [
            'video_id' => $videoId,
            'embed_url' => "https://www.youtube.com/embed/{$videoId}",
            'expires_at' => now()->addMinutes($expiryMinutes),
            'access_token' => Str::random(32), // Dummy token untuk tracking di API layer
        ];
    }

    /**
     * Verify signed URL still valid
     */
    public function verifySignedUrl(string $url): bool
    {
        try {
            // Laravel's URL verification
            return URL::hasValidSignature($url);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Generate proxy URL untuk media dengan entitlement check
     * Format: /api/v1/media/serve/{type}/{id}?token=xxx&sig=xxx
     */
    public function generateProxyUrl(
        string $mediaType,
        int $mediaId,
        ?int $expiryMinutes = null,
        ?string $accessToken = null
    ): string {
        $expiryMinutes ??= self::URL_EXPIRY_MINUTES;
        $accessToken ??= Str::random(32);

        $url = route('api.media.serve', [
            'type' => $mediaType,
            'id' => $mediaId,
        ], false);

        $queryParams = [
            'token' => $accessToken,
            'expires' => now()->addMinutes($expiryMinutes)->timestamp,
        ];

        return $url . '?' . http_build_query($queryParams);
    }
}
