<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;
    protected $fillable = [
        'sponsor_id',
        'title',
        'placement_type',
        'media_path',
        'media_type',
        'thumbnail_path',
        'target_url',
        'description',
        'daily_limit',
        'weight',
        'start_date',
        'end_date',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'daily_limit' => 'integer',
        'weight' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return BelongsTo<Sponsor, $this>
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * @return HasMany<CampaignLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CampaignLog::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: campaign yang aktif saat ini (status=active DAN dalam rentang tanggal).
     * Digunakan oleh endpoint publik Flutter.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query->published()
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
    }

    /**
     * Scope: campaign dalam status draft/paused (untuk admin review).
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeInDraft(Builder $query): Builder
    {
        return $query->whereIn('status', ['draft', 'paused']);
    }

    public function getViewsCountAttribute(): int
    {
        return $this->logs()->where('event_type', 'view')->count();
    }

    public function getClicksCountAttribute(): int
    {
        return $this->logs()->where('event_type', 'click')->count();
    }

    public function getCtrAttribute(): float
    {
        $views = $this->views_count;
        if ($views === 0) {
            return 0.0;
        }

        return round(($this->clicks_count / $views) * 100, 2);
    }

    public function getMediaUrlAttribute(): string
    {
        if (! $this->media_path) {
            return asset('assets/images/banner_braderparts.png');
        }
        if (str_starts_with($this->media_path, 'http')) {
            return $this->media_path;
        }
        if (str_starts_with($this->media_path, 'assets/')) {
            return asset($this->media_path);
        }

        return asset('storage/'.$this->media_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path) {
            if (str_starts_with($this->thumbnail_path, 'http')) {
                return $this->thumbnail_path;
            }
            if (str_starts_with($this->thumbnail_path, 'assets/')) {
                return asset($this->thumbnail_path);
            }

            return asset('storage/'.$this->thumbnail_path);
        }

        if ($this->media_type === 'image') {
            return $this->media_url;
        }

        if ($this->media_path) {
            $base = pathinfo($this->media_path, PATHINFO_FILENAME);
            $dir = pathinfo($this->media_path, PATHINFO_DIRNAME);
            $thumb = ($dir === '.' ? '' : $dir.'/').$base.'_thumb.jpg';
            if (file_exists(public_path('storage/'.$thumb))) {
                return asset('storage/'.$thumb);
            }
        }

        return asset('assets/images/banner_braderparts.png');
    }
}
