<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BenefitCategory;
use App\Models\Sponsor;
use App\Models\SponsorBenefitOverride;
use App\Models\SponsorProduct;
use App\Models\SponsorTier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SponsorApiController extends Controller
{
    /**
     * List all active sponsors with tier info.
     *
     * GET /api/v1/sponsors
     */
    public function index(Request $request): JsonResponse
    {
        $query = Sponsor::with(['sponsorTier', 'province', 'city'])
            ->where('is_active', true);

        // Filter by tier
        if ($request->has('tier')) {
            $query->whereHas('sponsorTier', fn ($q) => $q->where('slug', $request->input('tier')));
        }

        // Filter by province
        if ($request->has('province_id')) {
            $query->where('province_id', $request->input('province_id'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $sponsors = $query->orderByDesc('weight')->orderByDesc('created_at')
            ->paginate($perPage);

        // Append badge metadata to each sponsor
        $items = collect($sponsors->items())->map(function ($sponsor) {
            $tier = $sponsor->sponsorTier;
            $sponsor->logo_url = $sponsor->logo_path
                ? Storage::disk('public')->url($sponsor->logo_path) : null;
            $sponsor->tier_badge = [
                'label' => $tier?->badge_label ?? strtoupper($sponsor->tier ?? ''),
                'color' => $tier?->badge_color ?? '#999999',
                'icon_url' => $tier?->icon_url
                    ? Storage::disk('public')->url($tier->icon_url) : null,
            ];
            return $sponsor;
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $sponsors->currentPage(),
                'last_page' => $sponsors->lastPage(),
                'per_page' => $sponsors->perPage(),
                'total' => $sponsors->total(),
            ],
            'message' => null,
        ]);
    }

    /**
     * Get a single sponsor with full details including benefits.
     *
     * GET /api/v1/sponsors/{id}
     */
    public function show(int $id): JsonResponse
    {
        $sponsor = Sponsor::with([
            'sponsorTier',
            'province',
            'city',
            'products' => function ($q) {
                $q->where('is_active', true)->orderBy('order');
            },
        ])->findOrFail($id);

        // Resolve all effective benefits for this sponsor
        $benefits = BenefitCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($category) use ($sponsor) {
                return [
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'value_type' => $category->value_type,
                    'value' => $sponsor->resolveBenefit($category->slug),
                ];
            });

        $tier = $sponsor->sponsorTier;

        // Append badge metadata and absolute asset URLs
        $sponsorData = array_merge($sponsor->toArray(), [
            'logo_url' => $sponsor->logo_path
                ? Storage::disk('public')->url($sponsor->logo_path) : null,
            'co_branding_header_url' => $sponsor->co_branding_header_url
                ? Storage::disk('public')->url($sponsor->co_branding_header_url) : null,
            'co_branding_splash_url' => $sponsor->co_branding_splash_url
                ? Storage::disk('public')->url($sponsor->co_branding_splash_url) : null,
            'tier_badge' => [
                'label' => $tier?->badge_label ?? strtoupper($sponsor->tier ?? ''),
                'color' => $tier?->badge_color ?? '#999999',
                'icon_url' => $tier?->icon_url
                    ? Storage::disk('public')->url($tier->icon_url) : null,
            ],
            'effective_benefits' => $benefits,
        ]);

        return response()->json([
            'success' => true,
            'data' => $sponsorData,
            'meta' => null,
            'message' => null,
        ]);
    }

    /**
     * Create a new sponsor.
     *
     * POST /api/v1/sponsors
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:sponsors'],
            'description' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'tier_slug' => ['required', 'exists:sponsor_tiers,slug'],
            'weight' => ['integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contact_email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        $tier = SponsorTier::where('slug', $validated['tier_slug'])->firstOrFail();

        DB::beginTransaction();
        try {
            $sponsor = Sponsor::create(array_merge(
                $validated,
                [
                    'tier_id' => $tier->id,
                    'tier' => $tier->slug, // Keep legacy column in sync
                    'is_active' => true,
                ]
            ));

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $sponsor->load(['sponsorTier', 'province', 'city']),
                'meta' => null,
                'message' => 'Sponsor created successfully',
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => null,
                'errors' => ['general' => [$e->getMessage()]],
                'message' => 'Failed to create sponsor',
            ], 500);
        }
    }

    /**
     * Update an existing sponsor.
     *
     * PUT /api/v1/sponsors/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $sponsor = Sponsor::findOrFail($id);

        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:sponsors,slug,'.$id],
            'description' => ['nullable', 'string'],
            'logo_path' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'tier_slug' => ['sometimes', 'exists:sponsor_tiers,slug'],
            'weight' => ['sometimes', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
            'contact_email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
        ]);

        DB::beginTransaction();
        try {
            // If tier_slug is provided, resolve to tier_id
            if (isset($validated['tier_slug'])) {
                $tier = SponsorTier::where('slug', $validated['tier_slug'])->firstOrFail();
                $validated['tier_id'] = $tier->id;
                $validated['tier'] = $tier->slug;
                unset($validated['tier_slug']);
            }

            $sponsor->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $sponsor->fresh()->load(['sponsorTier', 'province', 'city']),
                'meta' => null,
                'message' => 'Sponsor updated successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => null,
                'errors' => ['general' => [$e->getMessage()]],
                'message' => 'Failed to update sponsor',
            ], 500);
        }
    }

    /**
     * Soft delete a sponsor.
     *
     * DELETE /api/v1/sponsors/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $sponsor = Sponsor::findOrFail($id);

        DB::beginTransaction();
        try {
            $sponsor->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => null,
                'meta' => null,
                'message' => 'Sponsor deleted successfully',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'data' => null,
                'errors' => ['general' => [$e->getMessage()]],
                'message' => 'Failed to delete sponsor',
            ], 500);
        }
    }

    /**
     * Upload sponsor logo and return the path.
     *
     * POST /api/v1/sponsors/{id}/logo
     */
    public function uploadLogo(Request $request, int $id): JsonResponse
    {
        $sponsor = Sponsor::findOrFail($id);

        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048'], // 2MB max
        ]);

        $path = $validated['logo']->storeAs(
            'sponsor/logos',
            $sponsor->slug.'-'.time().'.'.$validated['logo']->getClientOriginalExtension(),
            'public'
        );

        $sponsor->update(['logo_path' => $path]);

        return response()->json([
            'success' => true,
            'data' => [
                'logo_path' => $path,
                'logo_url' => Storage::disk('public')->url($path),
            ],
            'meta' => null,
            'message' => 'Logo uploaded successfully',
        ]);
    }

    /**
     * Upload co-branding assets (header banner & splash screen logo).
     * Only available for Diamond-tier sponsors.
     *
     * POST /api/v1/sponsors/{id}/co-branding
     */
    public function uploadCoBranding(Request $request, int $id): JsonResponse
    {
        $sponsor = Sponsor::findOrFail($id);

        // Verify this sponsor is Diamond tier
        if ($sponsor->tier !== 'diamond' && ($sponsor->sponsorTier?->slug !== 'diamond')) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Co-branding assets are only available for Diamond-tier sponsors.',
            ], 403);
        }

        $validated = $request->validate([
            'header_banner' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'], // 5MB
            'splash_logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $updates = [];

        if (!empty($validated['header_banner'])) {
            $path = $validated['header_banner']->storeAs(
                'sponsor/co-branding',
                $sponsor->slug.'-header-'.time().'.'.$validated['header_banner']->getClientOriginalExtension(),
                'public'
            );
            $updates['co_branding_header_url'] = $path;
        }

        if (!empty($validated['splash_logo'])) {
            $path = $validated['splash_logo']->storeAs(
                'sponsor/co-branding',
                $sponsor->slug.'-splash-'.time().'.'.$validated['splash_logo']->getClientOriginalExtension(),
                'public'
            );
            $updates['co_branding_splash_url'] = $path;
        }

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'At least one file (header_banner or splash_logo) must be provided.',
            ], 422);
        }

        $sponsor->update($updates);

        return response()->json([
            'success' => true,
            'data' => [
                'co_branding_header_url' => $sponsor->co_branding_header_url
                    ? Storage::disk('public')->url($sponsor->co_branding_header_url) : null,
                'co_branding_splash_url' => $sponsor->co_branding_splash_url
                    ? Storage::disk('public')->url($sponsor->co_branding_splash_url) : null,
            ],
            'meta' => null,
            'message' => 'Co-branding assets uploaded successfully.',
        ]);
    }

    /**
     * Dedicated storefront page for a sponsor.
     * Shows profile, tier info, badge, and paginated products.
     *
     * GET /api/v1/sponsors/{id}/storefront
     */
    public function storefront(int $id): JsonResponse
    {
        $sponsor = Sponsor::with(['sponsorTier', 'province', 'city'])
            ->where('is_active', true)
            ->findOrFail($id);

        $tier = $sponsor->sponsorTier;

        // Build badge data
        $badge = [
            'label' => $tier?->badge_label ?? strtoupper($sponsor->tier ?? ''),
            'color' => $tier?->badge_color ?? '#999999',
            'icon_url' => $tier?->icon_url ? Storage::disk('public')->url($tier->icon_url) : null,
        ];

        // Paginated products for this sponsor
        $perPage = min((int) request('per_page', 20), 100);
        $products = SponsorProduct::with('sponsor')
            ->where('sponsor_id', $sponsor->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'sponsor' => array_merge($sponsor->toArray(), [
                    'logo_url' => $sponsor->logo_path
                        ? Storage::disk('public')->url($sponsor->logo_path) : null,
                    'co_branding_header_url' => $sponsor->co_branding_header_url
                        ? Storage::disk('public')->url($sponsor->co_branding_header_url) : null,
                    'co_branding_splash_url' => $sponsor->co_branding_splash_url
                        ? Storage::disk('public')->url($sponsor->co_branding_splash_url) : null,
                ]),
                'tier_info' => $tier ? [
                    'id' => $tier->id,
                    'slug' => $tier->slug,
                    'name' => $tier->name,
                    'badge_label' => $tier->badge_label,
                    'badge_color' => $tier->badge_color ?? '#999999',
                    'icon_url' => $tier->icon_url
                        ? Storage::disk('public')->url($tier->icon_url) : null,
                    'sort_order' => $tier->sort_order,
                ] : null,
                'badge' => $badge,
                'products' => $products->items(),
            ],
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total_products' => $products->total(),
            ],
            'message' => null,
        ]);
    }

    /**
     * List all available tiers with their benefit summary.
     *
     * GET /api/v1/sponsors/tiers
     */
    public function listTiers(): JsonResponse
    {
        $tiers = SponsorTier::with(['benefitCategories'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($tier) {
                return array_merge($tier->toArray(), [
                    'badge_color' => $tier->badge_color ?? '#999999',
                    'icon_url' => $tier->icon_url
                        ? Storage::disk('public')->url($tier->icon_url) : null,
                    'benefit_summary' => $tier->benefitCategories->map(fn ($bc) => [
                        'slug' => $bc->slug,
                        'name' => $bc->name,
                        'value_type' => $bc->value_type,
                        'value' => $bc->pivot?->value,
                        'label' => $bc->pivot?->label,
                    ]),
                ]);
            });

        return response()->json([
            'success' => true,
            'data' => $tiers,
            'meta' => null,
            'message' => null,
        ]);
    }
}
