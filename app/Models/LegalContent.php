<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'version',
        'content',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public static function getLatest(string $type): ?self
    {
        return self::where('type', $type)
            ->published()
            ->orderBy('version', 'desc')
            ->first();
    }
}
