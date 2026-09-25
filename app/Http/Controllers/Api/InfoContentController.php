<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InfoContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InfoContentController extends Controller
{
    public function index(): JsonResponse
    {
        $contents = InfoContent::published()->get();

        return response()->json([
            'success' => true,
            'data' => $contents->map(fn($c) => [
                'slug' => $c->slug,
                'title' => $c->title,
                'body' => $c->body,
                'is_premium' => $c->is_premium,
                'whatsapp_number' => $c->whatsapp_number,
                'published_at' => $c->published_at->toISOString(),
            ]),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $content = InfoContent::published()->where('slug', $slug)->first();

        if (!$content) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $content->slug,
                'title' => $content->title,
                'body' => $content->body,
                'is_premium' => $content->is_premium,
                'whatsapp_number' => $content->whatsapp_number,
                'published_at' => $content->published_at->toISOString(),
            ],
        ]);
    }
}
