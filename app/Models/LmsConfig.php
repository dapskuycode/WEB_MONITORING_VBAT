<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsConfig extends Model
{
    // Config keys for LMS domain (all configurable via Admin)
    public const KEY_VIDEO_COMPLETION_THRESHOLD = 'video_completion_threshold';
    public const KEY_HARDWARE_SOLUTION_THRESHOLD = 'hardware_solution_threshold';
    public const KEY_QUIZ_DEFAULT_MAX_ATTEMPTS = 'quiz_default_max_attempts';
    public const KEY_QUIZ_DEFAULT_PASSING_SCORE = 'quiz_default_passing_score';
    public const KEY_ALUMNI_CUTOFF_DATE = 'alumni_cutoff_date';

    // Default values (NOT hardcoded in business logic — read from DB)
    public const DEFAULTS = [
        self::KEY_VIDEO_COMPLETION_THRESHOLD => 70,
        self::KEY_HARDWARE_SOLUTION_THRESHOLD => 90,
        self::KEY_QUIZ_DEFAULT_MAX_ATTEMPTS => 5,
        self::KEY_QUIZ_DEFAULT_PASSING_SCORE => 70,
        self::KEY_ALUMNI_CUTOFF_DATE => '2027-01-01 00:00:00',
    ];

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        // no special casts — value stored as text, cast on read
    ];

    public $timestamps = true;

    /**
     * Get a config value, falling back to DEFAULTS.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::where('key', $key)->first();
        if ($config) {
            return self::castValue($config->value, $config->type);
        }
        return self::DEFAULTS[$key] ?? $default;
    }

    /**
     * Set a config value.
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $description = null): void
    {
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => (string) $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }

    /**
     * Cast a stored value to its proper PHP type.
     */
    protected static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
