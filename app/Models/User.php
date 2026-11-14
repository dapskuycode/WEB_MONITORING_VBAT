<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'birth_date',
        'gender',
        'province_id',
        'city_id',
        'district',
        'village',
        'phone',
        'whatsapp',
        'address',
        'instagram',
        'facebook',
        'tiktok',
        'youtube',
        'profile_completed',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'profile_completed' => 'boolean',
        ];
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isSponsor(): bool
    {
        return $this->role === 'sponsor';
    }

    public function isTeknisi(): bool
    {
        return $this->role === 'teknisi';
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    public function isLmsManager(): bool
    {
        return $this->isSuperAdmin() || $this->isInstructor();
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function assignedCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'lms_course_instructors', 'user_id', 'course_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    /**
     * @return list<int>
     */
    public function managedCourseIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Course::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        if (! $this->isInstructor()) {
            return [];
        }
        $primary = Course::query()->where('instructor_id', $this->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $co = DB::table('lms_course_instructors')->where('user_id', $this->id)->pluck('course_id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_unique(array_merge($primary, $co)));
    }

    public function canManageCourse(Course|int|null $course): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if (! $this->isInstructor() || $course === null) {
            return false;
        }
        $courseId = $course instanceof Course ? (int) $course->id : (int) $course;
        if ($course instanceof Course && (int) $course->instructor_id === (int) $this->id) {
            return true;
        }

        return Course::query()->whereKey($courseId)->where('instructor_id', $this->id)->exists()
            || DB::table('lms_course_instructors')->where('course_id', $courseId)->where('user_id', $this->id)->exists();
    }

    /**
     * @return HasOne<Sponsor, $this>
     */
    public function sponsor(): HasOne
    {
        return $this->hasOne(Sponsor::class);
    }

    /**
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<UserWishlist, $this>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(UserWishlist::class);
    }

    /**
     * @return HasMany<Entitlement, $this>
     */
    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }

    /**
     * @return HasMany<OAuthProvider, $this>
     */
    public function oauthProviders(): HasMany
    {
        return $this->hasMany(OAuthProvider::class);
    }

    /**
     * @return HasOne<Membership, $this>
     */
    public function membership(): HasOne
    {
        return $this->hasOne(Membership::class);
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * @return HasMany<Achievement, $this>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * @return HasMany<Streak, $this>
     */
    public function streaks(): HasMany
    {
        return $this->hasMany(Streak::class);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }

    public function isProfileComplete(): bool
    {
        return ! empty($this->name)
            && ! empty($this->birth_date)
            && ! empty($this->gender)
            && ! empty($this->phone)
            && ! empty($this->whatsapp)
            && ! empty($this->address)
            && ! empty($this->province_id)
            && ! empty($this->city_id);
    }
}
