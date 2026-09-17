<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
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

    public function sponsor()
    {
        return $this->belongsTo(Sponsor::class);
    }

    public function logs()
    {
        return $this->hasMany(CampaignLog::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query->approved()
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            });
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
        if ($views === 0) return 0.0;
        return round(($this->clicks_count / $views) * 100, 2);
    }

    public function getMediaUrlAttribute(): string
    {
        if (!$this->media_path) {
            return asset('assets/images/banner_braderparts.png');
        }
        if (str_starts_with($this->media_path, 'http')) {
            return $this->media_path;
        }
        if (str_starts_with($this->media_path, 'assets/')) {
            return asset($this->media_path);
        }
        return asset('storage/' . $this->media_path);
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
            return asset('storage/' . $this->thumbnail_path);
        }

        if ($this->media_type === 'image') {
            return $this->media_url;
        }

        if ($this->media_path) {
            $base = pathinfo($this->media_path, PATHINFO_FILENAME);
            $dir = pathinfo($this->media_path, PATHINFO_DIRNAME);
            $thumb = ($dir === '.' ? '' : $dir . '/') . $base . '_thumb.jpg';
            if (file_exists(public_path('storage/' . $thumb))) {
                return asset('storage/' . $thumb);
            }
        }

        return asset('assets/images/banner_braderparts.png');
    }
}
