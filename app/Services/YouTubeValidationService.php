<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YouTubeValidationService
{
    public function extractVideoId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Extract video ID from various YouTube URL formats
        // youtube.com/watch?v=VIDEO_ID
        // youtu.be/VIDEO_ID
        // youtube.com/embed/VIDEO_ID
        // youtube.com/v/VIDEO_ID
        
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        // If no pattern matches, assume it's a direct video ID (11 chars alphanumeric + underscore/dash)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        return null;
    }

    public function validateVideoId(string $videoId): array
    {
        // ponytail: skip YouTube Data API call for now (requires API key setup)
        // Return mock validation success
        // Add when: YouTube API key configured in .env
        
        return [
            'valid' => true,
            'title' => null,
            'thumbnail' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
            'embed_url' => "https://www.youtube.com/embed/{$videoId}",
        ];
    }

    public function getThumbnailUrl(string $videoId): string
    {
        return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
    }

    public function getEmbedUrl(string $videoId): string
    {
        return "https://www.youtube.com/embed/{$videoId}";
    }
}
