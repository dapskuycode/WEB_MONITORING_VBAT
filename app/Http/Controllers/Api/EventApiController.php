<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountEvent;
use App\Services\AnalyticsEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventApiController extends Controller
{
    /**
     * Batch event ingestion endpoint (Phase 5 — REQ-ANA-01).
     * POST /api/v1/events
     */
    public function ingestBatch(Request $request): JsonResponse
    {
        $request->validate([
            'events' => 'required|array|min:1|max:100',
            'events.*.event_type' => 'required|string|max:64',
            'events.*.session_id' => 'nullable|uuid',
            'events.*.target_type' => 'nullable|string|max:64',
            'events.*.target_id' => 'nullable|integer',
            'events.*.context' => 'nullable|array',
            'session_id' => 'nullable|uuid',
        ]);

        $service = new AnalyticsEventService;
        $sessionId = $request->input('session_id', (string) \Illuminate\Support\Str::uuid());

        $count = $service->logBatch(
            $request->input('events'),
            $sessionId,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} event(s) logged",
            'data' => [
                'count' => $count,
            ],
        ], 201);
    }
    /**
     * Poin 2.4 & 4.3 Harga Dinamis & Multi-Event Diskon Aktif (EVENT-02):
     * Cek seluruh event diskon aktif untuk Shop & Home serta daftar produk terpilih.
     */
    public function getActiveEvent(Request $request): JsonResponse
    {
        $events = DiscountEvent::active()
            ->with('products.sponsor')
            ->latest()
            ->get();

        if ($events->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'has_active_event' => false,
                'total_events' => 0,
                'events' => [],
                'data' => null,
            ]);
        }

        $formattedEvents = $events->map(function ($event) {
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
                    $image = url('storage/'.$image);
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

            return [
                'id' => $event->id,
                'name' => $event->name,
                'type' => $event->discount_type,
                'value' => (float) $event->discount_value,
                'banner_text' => $event->banner_text,
                'start_at' => $event->start_at?->toIso8601String(),
                'end_at' => $event->end_at?->toIso8601String(),
                'total_products' => $products->count(),
                'products' => $products,
            ];
        });

        return response()->json([
            'status' => 'success',
            'has_active_event' => true,
            'total_events' => $formattedEvents->count(),
            'events' => $formattedEvents,
            'data' => $formattedEvents->first(),
        ]);
    }

    /**
     * Show detail of specific discount event.
     */
    public function show(DiscountEvent $event): JsonResponse
    {
        $event->load('products.sponsor');

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
                $image = url('storage/'.$image);
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
            'data' => [
                'id' => $event->id,
                'name' => $event->name,
                'type' => $event->discount_type,
                'value' => (float) $event->discount_value,
                'banner_text' => $event->banner_text,
                'start_at' => $event->start_at?->toIso8601String(),
                'end_at' => $event->end_at?->toIso8601String(),
                'is_active' => (bool) $event->is_active,
                'total_products' => $products->count(),
                'products' => $products,
            ],
        ]);
    }
}
