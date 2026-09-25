<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_type', 'external_id', 'status', 'payload', 'signature_data',
        'signature_valid', 'error_message', 'retry_count', 'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_data' => 'array',
        'signature_valid' => 'boolean',
        'retry_count' => 'integer',
        'processed_at' => 'datetime',
    ];

    public const WEBHOOK_TYPES = ['midtrans'];
    public const STATUSES = ['pending', 'received', 'processed', 'failed'];
}
