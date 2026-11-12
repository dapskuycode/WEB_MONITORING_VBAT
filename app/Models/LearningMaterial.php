<?php

namespace App\Models;

use App\Services\YouTubeValidationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = ['revision' => 1, 'is_preview' => false];

    protected $fillable = [
        'is_preview',
        'revision',

        'lesson_id',
        'unit_code',
        'unit_title',
        'material_type',
        'title',
        'description',
        'youtube_url',
        'youtube_video_id',
        'pdf_path', 'pdf_upload_id',
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
        'is_preview' => 'boolean',
        'revision' => 'integer',
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

    /** @return HasMany<Quiz, $this> */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<LearningMaterialView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(LearningMaterialView::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
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
        return app(YouTubeValidationService::class)->extractVideoId($url);
    }

    public static function checkYouTubeUnlisted(string $videoId): array
    {
        return app(YouTubeValidationService::class)->validateVideoId($videoId);
    }

    /**
     * Get the thumbnail URL (stored or YouTube default).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail_path) {
            return asset('storage/'.$this->thumbnail_path);
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
