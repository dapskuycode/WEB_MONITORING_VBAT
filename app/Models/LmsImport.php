<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsImport extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = ['rows' => 'array', 'errors' => 'array', 'snapshot' => 'array', 'result' => 'array', 'expires_at' => 'datetime'];
}
