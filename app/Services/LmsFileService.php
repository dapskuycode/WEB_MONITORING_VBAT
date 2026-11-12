<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class LmsFileService
{
    public function path(string $disk, ?string $path): ?string
    {
        if (! $path || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/') || in_array('..', explode('/', $path), true)) {
            return null;
        }
        $root = realpath(Storage::disk($disk)->path(''));
        $file = realpath(Storage::disk($disk)->path($path));

        return $root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file) ? $file : null;
    }
}
