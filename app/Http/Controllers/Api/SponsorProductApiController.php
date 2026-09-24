<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use App\Models\SponsorProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SponsorProductApiController extends Controller
{
    /**
     * List products with optional filters.
     *
     * GET /api/v1/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = SponsorProduct::with(['sponsor'])->where('is_active', true);

        if ($request->has('sponsor_id')) {
            $query->where('sponsor_id', $request->input('sponsor_id'));
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->input('min_price'));
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->input('max_price'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $products = $query->orderBy('order')->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
            'message' => null,
        ]);
    }

    /**
     * Show single product.
     *
     * GET /api/v1/products/{id}
     */
    public function show(int $id): JsonResponse
    {
        $product = SponsorProduct::with(['sponsor'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
            'meta' => null,
            'message' => null,
        ]);
    }

    /**
     * Create product for a sponsor.
     *
     * POST /api/v1/products
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sponsor_id' => ['required', 'exists:sponsors,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:sponsor_products'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'shopee_url' => ['nullable', 'url', 'max:500'],
            'tokopedia_url' => ['nullable', 'url', 'max:500'],
            'is_featured' => ['boolean'],
            'order' => ['integer', 'min:0'],
        ]);

        $sponsor = Sponsor::findOrFail($validated['sponsor_id']);

        DB::beginTransaction();
        try {
            // Validate product quota against effective benefit
            $maxProducts = $sponsor->resolveBenefit('katalog_produk');

            if ($maxProducts !== null && is_numeric($maxProducts)) {
                $currentCount = $sponsor->products()->whereNull('deleted_at')->count();
                if ($currentCount >= (int) $maxProducts) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'data' => null,
                        'errors' => ['katalog_produk' => ['Product quota exceeded for this tier']],
                        'message' => 'Validation failed',
                    ], 422);
                }
            }

            // Validate marketplace URLs: at least one required
            if (empty($validated['shopee_url']) && empty($validated['tokopedia_url'])) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'errors' => ['marketplace' => ['At least one marketplace URL (Shopee or Tokopedia) is required']],
                    'message' => 'Validation failed',
                ], 422);
            }

            // Auto-generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = \Str::slug($validated['name']);
            }

            $product = SponsorProduct::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load('sponsor'),
                'meta' => null,
                'message' => 'Product created successfully',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => null,
                'errors' => ['general' => [$e->getMessage()]],
                'message' => 'Failed to create product',
            ], 500);
        }
    }

    /**
     * Update product.
     *
     * PUT /api/v1/products/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = SponsorProduct::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:sponsor_products,slug,' . $id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'image_path' => ['nullable', 'string', 'max:500'],
            'shopee_url' => ['nullable', 'url', 'max:500'],
            'tokopedia_url' => ['nullable', 'url', 'max:500'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $product->update($validated);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->fresh()->load('sponsor'),
                'meta' => null,
                'message' => 'Product updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => null,
                'errors' => ['general' => [$e->getMessage()]],
                'message' => 'Failed to update product',
            ], 500);
        }
    }

    /**
     * Soft delete product.
     *
     * DELETE /api/v1/products/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $product = SponsorProduct::findOrFail($id);

        DB::beginTransaction();
        try {
            $product->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => null,
                'meta' => null,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => null,
                'errors' => ['general' => [$e->getMessage()]],
                'message' => 'Failed to delete product',
            ], 500);
        }
    }

    /**
     * Upload product image.
     *
     * POST /api/v1/products/{id}/image
     */
    public function uploadImage(Request $request, int $id): JsonResponse
    {
        $product = SponsorProduct::findOrFail($id);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $path = $validated['image']->storeAs(
            'sponsor/products',
            $product->sponsor_id . '-' . $product->id . '-' . time() . '.' . $validated['image']->getClientOriginalExtension(),
            'public'
        );

        $product->update(['image_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'image_path' => $path,
                'image_url' => Storage::disk('public')->url($path),
            ],
            'meta' => null,
            'message' => 'Image uploaded successfully',
        ]);
    }
}
