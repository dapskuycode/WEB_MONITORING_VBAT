<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lesson_id',
        'unit_code',
        'unit_title',
        'material_type',
        'title',
        'description',
        'youtube_url',
        'youtube_video_id',
        'pdf_path',
        'thumbnail_path',
        'content_text',
        'external_url',
        'sort_order',
        'is_required',
        'quiz_required',
        'quiz_id',
        'status',
        'duration_seconds',
        'view_count',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'quiz_required' => 'boolean',
        'duration_seconds' => 'integer',
        'view_count' => 'integer',
    ];

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return HasMany<LearningMaterialView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(LearningMaterialView::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Extract YouTube video ID from various URL formats.
     */
    public static function extractYouTubeId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Check if a YouTube video is unlisted (best-effort via oEmbed).
     */
    public static function checkYouTubeUnlisted(string $videoId): array
    {
        $oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$videoId}&format=json";

        try {
            $response = @file_get_contents($oembedUrl);
            if ($response === false) {
                return ['accessible' => false, 'unlisted' => null, 'error' => 'HTTP request failed'];
            }

            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['accessible' => false, 'unlisted' => null, 'error' => 'Invalid JSON response'];
            }

            // oEmbed returns data = video is accessible (public or unlisted)
            // oEmbed returns 401/404 = video is private or deleted
            return [
                'accessible' => true,
                'unlisted' => null, // YouTube doesn't expose unlisted flag via oEmbed
                'title' => $data['title'] ?? null,
                'author' => $data['author_name'] ?? null,
                'thumbnail_url' => $data['thumbnail_url'] ?? null,
            ];
        } catch (\Throwable $e) {
            return ['accessible' => false, 'unlisted' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the thumbnail URL (stored or YouTube default).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/' . $this->thumbnail_path);
        }

        if ($this->youtube_video_id) {
            return "https://img.youtube.com/vi/{$this->youtube_video_id}/mqdefault.jpg";
        }

        return null;
    }

    /**
     * Get the embed URL for YouTube videos.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        if ($this->youtube_video_id) {
            return "https://www.youtube.com/embed/{$this->youtube_video_id}";
        }

        return null;
    }
}
