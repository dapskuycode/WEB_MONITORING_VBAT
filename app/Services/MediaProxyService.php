<?php

namespace App\Services;

use Illuminate\Support\Facades\URL;

class MediaProxyService
{
    public function generateProxyUrl(string $mediaType, int $mediaId, int $userId): string
    {
        return URL::temporarySignedRoute('api.media.serve', now()->addMinutes(5), [
            'type' => $mediaType, 'id' => $mediaId, 'user' => $userId,
        ]);
    }
}
