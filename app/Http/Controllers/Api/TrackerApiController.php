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
            'product_name' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'email' => 'nullable|string|email',
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();

        // Resolve user_id
        $userId = $validated['user_id'] ?? null;
        if (! $userId && $request->user('sanctum')) {
            $userId = $request->user('sanctum')->id;
        }
        if (! $userId && $request->filled('email')) {
            $userId = \App\Models\User::where('email', $request->input('email'))->value('id');
        }

        // Resolve product
        $product = null;
        if (! empty($validated['product_id'])) {
            $product = SponsorProduct::find($validated['product_id']);
        }
        if (! $product && $request->filled('product_name')) {
            $pName = trim($request->input('product_name'));
            $product = SponsorProduct::where('name', $pName)
                ->orWhere('name', 'LIKE', '%' . $pName . '%')
                ->first();
            if (! $product && str_contains(strtolower($pName), 'infinix')) {
                $product = SponsorProduct::where('name', 'LIKE', '%Infinix%')->first();
            }
        }

        // 1. Simpan Log Tracker
        CampaignLog::create([
            'campaign_id' => $validated['campaign_id'] ?? null,
            'sponsor_product_id' => $product?->id ?? ($validated['product_id'] ?? null),
            'user_id' => $userId,
            'event_type' => $validated['event_type'],
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);

        // 2. Increment Counter langsung pada Model terkait
        if ($product) {
            if ($validated['event_type'] === 'view') {
                $product->increment('view_count');
            } elseif ($validated['event_type'] === 'click') {
                $product->increment('click_count');
            } elseif ($validated['event_type'] === 'wishlist' && $userId) {
                UserWishlist::firstOrCreate([
                    'user_id' => $userId,
                    'sponsor_product_id' => $product->id,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Interaksi berhasil direkam',
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'click_count' => $product?->click_count,
        ]);
    }

    /**
     * Poin 2.3:
     * Toggle Wishlist Produk
     */
    public function toggleWishlist(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'nullable|integer',
            'product_name' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'email' => 'nullable|string|email',
        ]);

        $userId = $validated['user_id'] ?? null;
        if (! $userId && $request->user('sanctum')) {
            $userId = $request->user('sanctum')->id;
        }
        if (! $userId && $request->filled('email')) {
            $userId = \App\Models\User::where('email', $request->input('email'))->value('id');
        }
        $userId = $userId ?? 1; // Fallback

        // Resolve product
        $product = null;
        if (! empty($validated['product_id'])) {
            $product = SponsorProduct::find($validated['product_id']);
        }
        if (! $product && $request->filled('product_name')) {
            $pName = trim($request->input('product_name'));
            $product = SponsorProduct::where('name', $pName)
                ->orWhere('name', 'LIKE', '%' . $pName . '%')
                ->first();
            if (! $product && str_contains(strtolower($pName), 'infinix')) {
                $product = SponsorProduct::where('name', 'LIKE', '%Infinix%')->first();
            }
        }

        if (! $product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan',
            ], 404);
        }

        $existing = UserWishlist::where('user_id', $userId)
            ->where('sponsor_product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isWishlisted = false;
        } else {
            UserWishlist::create([
                'user_id' => $userId,
                'sponsor_product_id' => $product->id,
            ]);
            $isWishlisted = true;

            // Catat ke campaign_logs juga
            CampaignLog::create([
                'sponsor_product_id' => $product->id,
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
            'product_id' => $product->id,
            'total_wishlists' => UserWishlist::where('sponsor_product_id', $product->id)->count(),
        ]);
    }

    /**
     * Get wishlist items for the specified user.
     */
    public function getUserWishlist(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        if (! $userId && $request->user('sanctum')) {
            $userId = $request->user('sanctum')->id;
        }
        if (! $userId && $request->filled('email')) {
            $userId = \App\Models\User::where('email', $request->input('email'))->value('id');
        }

        if (! $userId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $wishlists = UserWishlist::with('sponsorProduct')
            ->where('user_id', $userId)
            ->get();

        $items = $wishlists->map(function ($w) {
            $p = $w->sponsorProduct;
            return [
                'id' => $p?->id,
                'name' => $p?->name ?? 'Produk Sponsor',
                'price' => 'Rp' . number_format($p?->price ?? 0, 0, ',', '.'),
                'image' => $p?->image_path ?? 'assets/images/product_battery.png',
                'link' => $p?->shopee_url ?? ($p?->tokopedia_url ?? ''),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $items,
        ]);
    }
}
