<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedApiController extends Controller
{
    public function __construct(
        private FeedService $feedService
    ) {}

    /**
     * GET /api/v1/feed/shop
     * Product-only feed with cursor pagination.
     */
    public function shop(Request $request): JsonResponse
    {
        $cursor = $request->query('cursor');
        $perPage = min((int) $request->query('per_page', 20), 50);

        $result = $this->feedService->getShopFeed($cursor, $perPage);

        return response()->json([
            'success' => true,
            'data' => $result['items'],
            'meta' => $result['meta'],
            'message' => null,
        ]);
    }

    /**
     * GET /api/v1/feed/home
     * Mixed feed (products + free materials) with cursor pagination.
     */
    public function home(Request $request): JsonResponse
    {
        $cursor = $request->query('cursor');
        $perPage = min((int) $request->query('per_page', 20), 50);

        $result = $this->feedService->getHomeFeed($cursor, $perPage);

        return response()->json([
            'success' => true,
            'data' => $result['items'],
            'meta' => $result['meta'],
            'message' => null,
        ]);
    }
}
