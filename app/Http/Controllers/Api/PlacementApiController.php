<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BestDeal;
use App\Models\CampaignLog;
use App\Models\SponsorProduct;
use App\Services\PlacementSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlacementApiController extends Controller
{
    public function __construct(
        private PlacementSelectionService $placementService,
    ) {}

    /**
     * Get campaigns for a specific placement type (probabilistic selection).
     *
     * GET /api/v1/placements/{type}
     */
    public function show(string $type): JsonResponse
    {
        $validTypes = ['hero_slider', 'horizontal_infeed', 'popup', 'best_deal', 'card_infeed'];

        if (!in_array($type, $validTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid placement type. Valid: ' . implode(', ', $validTypes),
            ], 422);
        }

        $selected = $this->placementService->select($type);

        return response()->json([
            'success' => true,
            'data' => $selected->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'placement_type' => $c->placement_type,
                'media_path' => $c->media_path,
                'media_type' => $c->media_type,
                'target_url' => $c->target_url,
                'description' => $c->description,
                'sponsor' => [
                    'id' => $c->sponsor->id,
                    'name' => $c->sponsor->name,
                    'slug' => $c->sponsor->slug,
                    'tier' => $c->sponsor->tier?->name,
                ],
                'weight' => $c->weight,
                'start_date' => $c->start_date?->toDateString(),
                'end_date' => $c->end_date?->toDateString(),
            ]),
            'meta' => [
                'placement_type' => $type,
                'count' => $selected->count(),
            ],
        ]);
    }

    /**
     * Log impression event.
     *
     * POST /api/v1/placements/impression
     */
    public function logImpression(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'placement_type' => ['required', 'string', 'max:50'],
            'session_id' => ['nullable', 'string', 'max:64'],
        ]);

        CampaignLog::create([
            'campaign_id' => $validated['campaign_id'],
            'user_id' => auth()->id(),
            'event_type' => 'impression',
            'placement_context' => $validated['placement_type'],
            'session_id' => $validated['session_id'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Impression logged',
        ]);
    }

    /**
     * Log click event.
     *
     * POST /api/v1/placements/click
     */
    public function logClick(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'placement_type' => ['required', 'string', 'max:50'],
            'session_id' => ['nullable', 'string', 'max:64'],
        ]);

        CampaignLog::create([
            'campaign_id' => $validated['campaign_id'],
            'user_id' => auth()->id(),
            'event_type' => 'click',
            'placement_context' => $validated['placement_type'],
            'session_id' => $validated['session_id'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Click logged',
        ]);
    }

    /**
     * Get Best Deal products (manual selection + auto fallback).
     *
     * GET /api/v1/placements/best-deal
     */
    public function bestDeal(): JsonResponse
    {
        $activeDeals = BestDeal::active()
            ->with(['sponsorProduct.sponsor:id,name,slug', 'products.sponsor:id,name,slug'])
            ->limit(10)
            ->get();

        $products = $activeDeals->flatMap(function ($deal) {
            // If manual selection exists, use it
            if ($deal->sponsor_product_id && $deal->sponsorProduct) {
                return [[
                    'id' => $deal->sponsorProduct->id,
                    'name' => $deal->sponsorProduct->name,
                    'slug' => $deal->sponsorProduct->slug,
                    'price' => $deal->sponsorProduct->price,
                    'image_path' => $deal->sponsorProduct->image_path,
                    'marketplace_url' => $deal->sponsorProduct->marketplace_url,
                    'sponsor' => [
                        'id' => $deal->sponsorProduct->sponsor->id,
                        'name' => $deal->sponsorProduct->sponsor->name,
                        'slug' => $deal->sponsorProduct->sponsor->slug,
                    ],
                    'deal_title' => $deal->title,
                    'deal_description' => $deal->description,
                    'deal_banner' => $deal->banner_path,
                    'selection_type' => $deal->selection_type,
                ]];
            }

            // Otherwise use pivot products
            return $deal->products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $p->price,
                'image_path' => $p->image_path,
                'marketplace_url' => $p->marketplace_url,
                'sponsor' => [
                    'id' => $p->sponsor->id,
                    'name' => $p->sponsor->name,
                    'slug' => $p->sponsor->slug,
                ],
                'deal_title' => $deal->title,
                'deal_description' => $deal->description,
                'deal_banner' => $deal->banner_path,
                'selection_type' => $deal->selection_type,
            ]);
        });

        return response()->json([
            'success' => true,
            'data' => $products->values(),
            'meta' => [
                'count' => $products->count(),
            ],
        ]);
    }

    /**
     * Admin: manually select product for Best Deal.
     *
     * POST /api/v1/admin/best-deals/select
     */
    public function selectBestDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sponsor_product_id' => ['required', 'integer', 'exists:sponsor_products,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'banner_path' => ['nullable', 'string', 'max:255'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        $product = SponsorProduct::findOrFail($validated['sponsor_product_id']);

        $deal = BestDeal::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'banner_path' => $validated['banner_path'] ?? null,
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'is_active' => true,
            'selected_by' => auth()->id(),
            'selection_type' => 'manual',
            'sponsor_product_id' => $product->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $deal,
            'message' => 'Best Deal selected successfully',
        ], 201);
    }

    /**
     * Admin: remove product from Best Deal.
     *
     * DELETE /api/v1/admin/best-deals/{id}
     */
    public function removeBestDeal(int $id): JsonResponse
    {
        $deal = BestDeal::findOrFail($id);
        $deal->update(['is_active' => false, 'selection_type' => 'none']);

        return response()->json([
            'success' => true,
            'message' => 'Best Deal removed',
        ]);
    }

    /**
     * Admin: list all placement configs.
     *
     * GET /api/v1/admin/placement-configs
     */
    public function listConfigs(): JsonResponse
    {
        $configs = $this->placementService->getConfigs();

        return response()->json([
            'success' => true,
            'data' => $configs,
        ]);
    }
}
