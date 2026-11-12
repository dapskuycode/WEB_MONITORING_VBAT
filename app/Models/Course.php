<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    public const TYPE_FREE_CLASS = 'free_class';

    public const TYPE_ANDROID = 'android';

    public const TYPE_IPHONE = 'iphone';

    public const TYPE_HARDWARE_SOLUTION = 'hardware_solution';

    public const TYPES = [
        self::TYPE_FREE_CLASS,
        self::TYPE_ANDROID,
        self::TYPE_IPHONE,
        self::TYPE_HARDWARE_SOLUTION,
    ];

    protected $attributes = ['revision' => 1];

    protected $fillable = [
        'prerequisite_course_id',
        'code',
        'revision',

        'title',
        'slug',
        'description',
        'thumbnail_path',
        'status',
        'type',
        'is_free_class',
        'is_featured',
        'published_at',
        'archived_at',
    ];

    protected $casts = [
        'is_free_class' => 'boolean',
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
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
     * @return HasManyThrough<LearningMaterial, Lesson, $this>
     */
    public function learningMaterials(): HasManyThrough
    {
        return $this->hasManyThrough(LearningMaterial::class, Lesson::class)->orderBy('learning_materials.sort_order');
    }

    /**
     * Scope: published courses only.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNull('archived_at');
    }

    /**
     * Scope: free class courses.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeFreeClass($query)
    {
        return $query->where('is_free_class', true);
    }

    /**
     * Scope: by type.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if the course is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
