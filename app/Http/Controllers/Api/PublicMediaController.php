<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Services\LmsFileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    public function query(Request $request): BinaryFileResponse
    {
        $path = $request->query('path');
        abort_unless(is_string($path), 404);

        return $this->serve($path);
    }

    public function serve(string $path): BinaryFileResponse
    {
        abort_if(str_starts_with($path, 'learning/pdfs/') || LearningMaterial::withTrashed()->where('pdf_path', $path)->exists(), 404);
        $file = app(LmsFileService::class)->path('public', $path);
        if (! $file && ! str_contains($path, '..') && ! str_contains($path, '\\') && ! str_contains($path, "\0")) {
            $root = realpath(public_path());
            $candidate = realpath(public_path($path));
            if ($root && $candidate && str_starts_with($candidate, $root.DIRECTORY_SEPARATOR) && is_file($candidate)) {
                $file = $candidate;
            }
        }
        abort_unless($file && in_array(mime_content_type($file), ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/avif'], true), 404);

        return response()->file($file, ['Access-Control-Allow-Origin' => '*', 'X-Content-Type-Options' => 'nosniff']);
    }
}
