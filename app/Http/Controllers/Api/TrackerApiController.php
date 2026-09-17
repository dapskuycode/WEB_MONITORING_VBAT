<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignLog;
use App\Models\SponsorProduct;
use App\Models\UserWishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackerApiController extends Controller
{
    /**
     * Poin 5.2 & 4.6:
     * Endpoint API efisien untuk menyimpan log aktivitas/interaksi
     * (tracker untuk tayangan banner, klik, dan penambahan wishlist).
     */
    public function logInteraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string|in:view,click,wishlist',
            'campaign_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // 1. Simpan Log Tracker
        CampaignLog::create([
            'campaign_id' => $validated['campaign_id'] ?? null,
            'sponsor_product_id' => $validated['product_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'event_type' => $validated['event_type'],
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        // 2. Increment Counter langsung pada Model terkait
        if (! empty($validated['product_id'])) {
            $product = SponsorProduct::where('id', $validated['product_id'])->first();
            if ($product) {
                if ($validated['event_type'] === 'view') {
                    $product->increment('view_count');
                } elseif ($validated['event_type'] === 'click') {
                    $product->increment('click_count');
                } elseif ($validated['event_type'] === 'wishlist' && ! empty($validated['user_id'])) {
                    UserWishlist::firstOrCreate([
                        'user_id' => $validated['user_id'],
                        'sponsor_product_id' => $product->id,
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Interaksi berhasil direkam',
        ]);
    }

    /**
     * Poin 2.3:
     * Toggle Wishlist Produk
     */
    public function toggleWishlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:sponsor_products,id',
            'user_id' => 'nullable|integer',
        ]);

        $userId = $validated['user_id'] ?? 1; // Fallback demo user
        $productId = $validated['product_id'];

        $existing = UserWishlist::where('user_id', $userId)
            ->where('sponsor_product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            $isWishlisted = false;
        } else {
            UserWishlist::create([
                'user_id' => $userId,
                'sponsor_product_id' => $productId,
            ]);
            $isWishlisted = true;

            // Catat ke campaign_logs juga
            CampaignLog::create([
                'sponsor_product_id' => $productId,
                'user_id' => $userId,
                'event_type' => 'wishlist',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'is_wishlisted' => $isWishlisted,
            'total_wishlists' => UserWishlist::where('sponsor_product_id', $productId)->count(),
        ]);
    }
}
