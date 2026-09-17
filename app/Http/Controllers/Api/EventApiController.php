<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountEvent;
use Illuminate\Http\JsonResponse;

class EventApiController extends Controller
{
    /**
     * Poin 2.4 & 4.3 Harga Dinamis & Event Diskon Aktif:
     * Cek apakah ada event diskon aktif untuk Shop dan daftar produk terpilih.
     */
    public function getActiveEvent(): JsonResponse
    {
        $event = DiscountEvent::active()->with('products.sponsor')->latest()->first();

        if (! $event) {
            return response()->json([
                'status' => 'success',
                'has_active_event' => false,
                'data' => null,
            ]);
        }

        $products = $event->products->map(function ($p) use ($event) {
            $basePrice = (float) $p->price;
            if ($event->discount_type === 'percentage') {
                $discountRate = (float) $event->discount_value;
                $discountPrice = round($basePrice * (1 - ($discountRate / 100)));
                $discountPercent = (int) round($discountRate);
            } else {
                $discountPrice = max(0, $basePrice - (float) $event->discount_value);
                $discountPercent = $basePrice > 0 ? (int) round((($basePrice - $discountPrice) / $basePrice) * 100) : 0;
            }

            $image = $p->image_path;
            if ($image && ! str_starts_with($image, 'http') && ! str_starts_with($image, 'assets/')) {
                $image = url('api/v1/storage/'.$image);
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $basePrice,
                'discount_price' => $discountPrice,
                'discount_percentage' => $discountPercent,
                'image' => $image,
                'shopee_url' => $p->shopee_url,
                'tokopedia_url' => $p->tokopedia_url,
                'sponsor' => [
                    'id' => $p->sponsor?->id,
                    'name' => $p->sponsor?->name,
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'has_active_event' => true,
            'data' => [
                'id' => $event->id,
                'name' => $event->name,
                'type' => $event->discount_type,
                'value' => (float) $event->discount_value,
                'banner_text' => $event->banner_text,
                'start_at' => $event->start_at?->toIso8601String(),
                'end_at' => $event->end_at?->toIso8601String(),
                'total_products' => $products->count(),
                'products' => $products,
            ],
        ]);
    }
}
