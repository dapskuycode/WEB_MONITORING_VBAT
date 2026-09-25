<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'body',
        'is_premium',
        'whatsapp_number',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_premium' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
}
