<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalContent;
use Illuminate\Http\JsonResponse;

class LegalController extends Controller
{
    public function getTerms(): JsonResponse
    {
        $legal = LegalContent::getLatest('terms');

        if (!$legal) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $legal->type,
                'version' => $legal->version,
                'content' => $legal->content,
                'published_at' => $legal->published_at->toISOString(),
            ],
        ]);
    }

    public function getPrivacy(): JsonResponse
    {
        $legal = LegalContent::getLatest('privacy');

        if (!$legal) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $legal->type,
                'version' => $legal->version,
                'content' => $legal->content,
                'published_at' => $legal->published_at->toISOString(),
            ],
        ]);
    }

    public function getAbout(): JsonResponse
    {
        $legal = LegalContent::getLatest('about');

        if (!$legal) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $legal->type,
                'version' => $legal->version,
                'content' => $legal->content,
                'published_at' => $legal->published_at->toISOString(),
            ],
        ]);
    }

    public function getConsent(): JsonResponse
    {
        $legal = LegalContent::getLatest('consent');

        if (!$legal) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $legal->type,
                'version' => $legal->version,
                'content' => $legal->content,
                'published_at' => $legal->published_at->toISOString(),
            ],
        ]);
    }
}
