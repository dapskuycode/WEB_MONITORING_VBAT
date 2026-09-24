<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\FeedConfig;
use App\Models\LearningMaterial;
use App\Models\SponsorProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FeedService
{
    /**
     * Generate shop feed (product-only) with cursor pagination.
     *
     * @return array<string, mixed>
     */
    public function getShopFeed(?string $cursor = null, int $perPage = 20): array
    {
        $query = SponsorProduct::with('sponsor')
            ->where('is_active', true)
            ->whereHas('sponsor', function ($q) {
                $q->where('is_active', true);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $products = $this->cursorPaginate($query, $cursor, $perPage);
        $nextCursor = $this->encodeCursor($products->last());

        $items = $products->map(fn ($p) => $this->formatProductItem($p));

        // Insert banners
        $config = FeedConfig::active()->forFeed('shop')->first();
        $items = $this->insertBanners($items, $config, 'shop');

        return [
            'items' => $items,
            'meta' => [
                'next_cursor' => $nextCursor,
                'has_more' => $products->count() === $perPage,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Generate home feed (mixed: products + learning materials + sponsor cards).
     *
     * Strategy: query each type separately, merge in PHP, sort by created_at desc,
     * then apply cursor-based slice. This avoids SQL UNION column mismatch.
     *
     * @return array<string, mixed>
     */
    public function getHomeFeed(?string $cursor = null, int $perPage = 20): array
    {
        // Determine cursor cutoff
        $cursorCutoff = null;
        if ($cursor) {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded) {
                $cursorCutoff = $decoded;
            }
        }

        // Fetch products (with sponsor eager load)
        $products = SponsorProduct::with('sponsor')
            ->where('is_active', true)
            ->whereHas('sponsor', fn ($q) => $q->where('is_active', true))
            ->when($cursorCutoff, function ($q, $cut) {
                $q->where(function ($q2) use ($cut) {
                    $q2->where('created_at', '<', $cut['created_at'])
                        ->orWhere(function ($q3) use ($cut) {
                            $q3->where('created_at', '=', $cut['created_at'])
                                ->where('id', '<', $cut['id']);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($perPage)
            ->get()
            ->map(fn ($p) => (object) [
                'sort_date' => $p->created_at,
                'sort_id' => $p->id,
                'type' => 'product',
                'model' => $p,
            ]);

        // Fetch published learning materials
        $materials = LearningMaterial::where('status', 'published')
            ->when($cursorCutoff, function ($q, $cut) {
                $q->where(function ($q2) use ($cut) {
                    $q2->where('created_at', '<', $cut['created_at'])
                        ->orWhere(function ($q3) use ($cut) {
                            $q3->where('created_at', '=', $cut['created_at'])
                                ->where('id', '<', $cut['id']);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($perPage)
            ->get()
            ->map(fn ($m) => (object) [
                'sort_date' => $m->created_at,
                'sort_id' => $m->id,
                'type' => 'material',
                'model' => $m,
            ]);

        // Merge and sort by created_at desc, then id desc
        $merged = $products->merge($materials)
            ->sortByDesc(fn ($item) => $item->sort_date->timestamp)
            ->sortByDesc(fn ($item) => $item->sort_id)
            ->values();

        // Stable sort: primary key = created_at desc, secondary = id desc
        $merged = $merged->sort(function ($a, $b) {
            $timeCmp = $b->sort_date->timestamp <=> $a->sort_date->timestamp;
            if ($timeCmp !== 0) {
                return $timeCmp;
            }

            return $b->sort_id <=> $a->sort_id;
        })->values();

        // Take perPage items
        $page = $merged->take($perPage);

        // Format items
        $formattedItems = $page->map(function ($item) {
            if ($item->type === 'product') {
                return $this->formatProductItem($item->model);
            }

            return $this->formatMaterialItem($item->model);
        });

        // Determine next cursor
        $lastItem = $page->last();
        $nextCursor = null;
        if ($lastItem && $page->count() === $perPage) {
            $nextCursor = base64_encode(json_encode([
                'created_at' => $lastItem->sort_date->toDateTimeString(),
                'id' => $lastItem->sort_id,
            ]));
        }

        // Insert banners
        $config = FeedConfig::active()->forFeed('home')->first();
        $formattedItems = $this->insertBanners($formattedItems, $config, 'home');

        return [
            'items' => $formattedItems,
            'meta' => [
                'next_cursor' => $nextCursor,
                'has_more' => $page->count() === $perPage,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Cursor-based pagination for Eloquent query.
     *
     * @param Builder $query
     * @return Collection<int, mixed>
     */
    private function cursorPaginate(Builder $query, ?string $cursor, int $perPage): Collection
    {
        if ($cursor) {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded) {
                $query->where(function ($q) use ($decoded) {
                    $q->where('created_at', '<', $decoded['created_at'])
                        ->orWhere(function ($q2) use ($decoded) {
                            $q2->where('created_at', '=', $decoded['created_at'])
                                ->where('id', '<', $decoded['id']);
                        });
                });
            }
        }

        return $query->limit($perPage)->get();
    }

    /**
     * Insert banner items into feed at configured intervals.
     *
     * @param Collection<int, array<string, mixed>> $items
     * @return Collection<int, array<string, mixed>>
     */
    private function insertBanners(Collection $items, ?FeedConfig $config, string $feedType): Collection
    {
        $interval = $config?->insertion_interval ?? 12;
        $bannerType = $config?->banner_type ?? ($feedType === 'shop' ? 'shop_horizontal' : 'hero_slider');

        $banners = Campaign::with('sponsor')
            ->active()
            ->where('placement_type', $bannerType)
            ->orderByDesc('weight')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        if ($banners->isEmpty()) {
            return $items;
        }

        $result = collect();
        $bannerIndex = 0;

        foreach ($items as $index => $item) {
            $result->push($item);

            // Insert banner after every N items (starting from index = interval - 1)
            if (($index + 1) % $interval === 0 && $bannerIndex < $banners->count()) {
                $banner = $banners[$bannerIndex];
                $result->push($this->formatBannerItem($banner));
                $bannerIndex++;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProductItem(SponsorProduct $p): array
    {
        $image = $p->image_path;
        if ($image && ! str_starts_with($image, 'http') && ! str_starts_with($image, 'assets/')) {
            $image = url('api/v1/storage/'.$image);
        }

        return [
            'content_type' => 'product',
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'price' => (float) $p->price,
            'discount_price' => (float) ($p->discount_price ?: $p->price),
            'image' => $image ?: 'assets/images/product_1.png',
            'shopee_url' => $p->shopee_url,
            'tokopedia_url' => $p->tokopedia_url,
            'rating' => '4.9',
            'sold' => '250+',
            'sponsor' => [
                'id' => $p->sponsor?->id,
                'name' => $p->sponsor?->name ?? 'Mitra Resmi',
                'tier' => $p->sponsor?->tier ?? 'PARTNER',
            ],
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMaterialItem(LearningMaterial $m): array
    {
        $thumbnail = $m->thumbnail_path;
        if ($thumbnail && ! str_starts_with($thumbnail, 'http') && ! str_starts_with($thumbnail, 'assets/')) {
            $thumbnail = url('api/v1/storage/'.$thumbnail);
        }

        return [
            'content_type' => 'material',
            'id' => $m->id,
            'title' => $m->title,
            'description' => $m->description,
            'material_type' => $m->material_type,
            'thumbnail' => $thumbnail ?: 'assets/images/default_thumbnail.png',
            'youtube_url' => $m->youtube_url,
            'pdf_path' => $m->pdf_path,
            'external_url' => $m->external_url,
            'duration_seconds' => $m->duration_seconds,
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBannerItem(Campaign $c): array
    {
        $mediaPath = $c->media_path;
        if ($mediaPath && ! str_starts_with($mediaPath, 'http') && ! str_starts_with($mediaPath, 'assets/')) {
            $mediaPath = url('api/v1/storage/'.$mediaPath);
        }

        return [
            'content_type' => 'banner',
            'id' => $c->id,
            'title' => $c->title,
            'description' => $c->description,
            'media_path' => $mediaPath,
            'media_type' => $c->media_type,
            'target_url' => $c->target_url,
            'placement_type' => $c->placement_type,
            'sponsor_name' => $c->sponsor?->name ?? 'Sponsor',
            'tier' => $c->sponsor ? strtoupper($c->sponsor->tier ?? 'PARTNER') : 'PARTNER',
        ];
    }

    /**
     * Encode cursor from last item.
     */
    private function encodeCursor(?object $item): ?string
    {
        if (! $item) {
            return null;
        }

        $payload = [
            'created_at' => $item->created_at?->toDateTimeString(),
            'id' => $item->id,
        ];

        return base64_encode(json_encode($payload));
    }

    /**
     * Decode cursor string.
     *
     * @return array<string, mixed>|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        try {
            $decoded = json_decode(base64_decode($cursor), true);
            if (! is_array($decoded) || ! isset($decoded['created_at'], $decoded['id'])) {
                return null;
            }

            return $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
