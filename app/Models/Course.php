<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail_path',
        'status',
        'is_featured',
    ];

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Video, $this>
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<LearningMaterial, $this>
     */
    public function learningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class)->orderBy('sort_order');
    }
}
