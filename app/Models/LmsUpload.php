<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsUpload extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['page_count' => 'integer', 'size_bytes' => 'integer', 'scanned_at' => 'datetime'];
}
