<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'certificate_number',
        'type',
        'issued_at',
        'expires_at',
        'qr_data',
        'qr_image_url',
        'pdf_url',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public static function generateCertificateNumber(string $type = 'CERT'): string
    {
        $date = now()->format('Ym');
        $random = strtoupper(substr(md5(uniqid('', true)), 0, 8));
        return strtoupper($type) . '-' . $date . '-' . $random;
    }

    public function generateQrData(): string
    {
        return json_encode([
            'certificate_number' => $this->certificate_number,
            'user_name' => $this->user->name,
            'title' => $this->title,
            'issued_at' => $this->issued_at->toISOString(),
            'verify_url' => url('/api/certificates/verify/' . $this->certificate_number),
        ]);
    }
}
