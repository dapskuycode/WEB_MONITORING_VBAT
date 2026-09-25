<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BenefitCategory;
use App\Models\BestDeal;
use App\Models\Campaign;
use App\Models\FeedConfig;
use App\Models\Sponsor;
use App\Models\SponsorBenefitOverride;
use App\Models\SponsorProduct;
use App\Services\AdminNotificationService;
use App\Services\AnalyticsEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AdminApiController extends Controller
{
    public function __construct(
        private readonly AdminNotificationService $notificationService,
        private readonly AnalyticsEventService $analyticsService,
    ) {}

    // ─── Notifications ───────────────────────────────────────────

    /**
     * List admin notifications (broadcast + targeted).
     */
    public function notifications(Request $request): JsonResponse
    {
        $adminId = $request->user()?->id;

        $result = $this->notificationService->listForAdmin(
            adminId: $adminId,
            unreadOnly: $request->boolean('unread_only'),
            type: $request->query('type'),
            perPage: min((int) $request->query('per_page', 20), 100),
        );

        return response()->json([
            'success' => true,
            'data' => $result['data']->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'actor_type' => $n->actor_type,
                'actor_id' => $n->actor_id,
                'target_type' => $n->target_type,
                'target_id' => $n->target_id,
                'metadata' => $n->metadata,
                'deep_link' => $n->deep_link,
                'is_read' => $n->is_read,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ])->values(),
            'meta' => [
                'unread_count' => $result['unread_count'],
            ],
            'message' => null,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        $adminId = $request->user()?->id;
        $success = $this->notificationService->markAsRead($id, $adminId);

        if (!$success) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Notification not found or not accessible.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => ['id' => $id, 'is_read' => true],
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $adminId = $request->user()?->id;

        $query = \App\Models\AdminNotification::query()
            ->when($adminId, fn ($q) => $q->where(function ($sub) use ($adminId) {
                $sub->whereNull('recipient_admin_id')
                    ->orWhere('recipient_admin_id', $adminId);
            }))
            ->where('is_read', false);

        $count = $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['marked_read' => $count],
            'message' => "{$count} notifications marked as read.",
        ]);
    }

    // ─── Sponsor Override ────────────────────────────────────────

    /**
     * Get unread notification count for badge/poll.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $adminId = $request->user()?->id;

        $count = \App\Models\AdminNotification::query()
            ->when($adminId, fn ($q) => $q->where(function ($sub) use ($adminId) {
                $sub->whereNull('recipient_admin_id')
                    ->orWhere('recipient_admin_id', $adminId);
            }))
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => ['unread_count' => $count],
            'message' => null,
        ]);
    }

    /**
     * Override a benefit value for a specific sponsor.
     */
    public function overrideBenefit(Request $request, Sponsor $sponsor): JsonResponse
    {
        $validated = $request->validate([
            'benefit_category_id' => ['required', 'exists:benefit_categories,id'],
            'value' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $before = SponsorBenefitOverride::where('sponsor_id', $sponsor->id)
            ->where('benefit_category_id', $validated['benefit_category_id'])
            ->first();

        $overrideValue = [
            'value' => $validated['value'] ?? null,
            'label' => $validated['label'] ?? null,
        ];

        $adminId = $request->user()?->id;

        $override = SponsorBenefitOverride::updateOrCreate(
            [
                'sponsor_id' => $sponsor->id,
                'benefit_category_id' => $validated['benefit_category_id'],
            ],
            [
                'override_value' => $overrideValue,
                'overridden_by' => $adminId,
                'reason' => $request->input('reason'),
            ],
        );

        $this->notificationService->audit(
            action: 'benefit.overridden',
            actorId: $adminId ?? 0,
            targetType: 'sponsor',
            targetId: $sponsor->id,
            beforeState: $before ? $before->toArray() : null,
            afterState: $override->fresh()->toArray(),
            reason: $request->input('reason'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $this->notificationService->notify(
            type: 'benefit.overridden',
            title: "Benefit override applied to {$sponsor->name}",
            body: "Admin #{$adminId} overrode benefit #{$validated['benefit_category_id']}.",
            actorType: 'admin',
            actorId: $adminId,
            targetType: 'sponsor',
            targetId: $sponsor->id,
            deepLink: "/admin/sponsors/{$sponsor->id}/benefits",
        );

        $fresh = $override->fresh();
        $fresh->setRawAttributes([
            'override_value' => $overrideValue,
        ] + $fresh->getAttributes());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $fresh->id,
                'sponsor_id' => $fresh->sponsor_id,
                'benefit_category_id' => $fresh->benefit_category_id,
                'value' => $overrideValue['value'],
                'label' => $overrideValue['label'],
                'override_value' => $overrideValue,
                'reason' => $fresh->reason,
                'overridden_by' => $fresh->overridden_by,
                'created_at' => $fresh->created_at?->toIso8601String(),
                'updated_at' => $fresh->updated_at?->toIso8601String(),
            ],
            'message' => 'Benefit override applied.',
        ], 201);
    }

    /**
     * Change a sponsor's tier.
     */
    public function changeSponsorTier(Request $request, Sponsor $sponsor): JsonResponse
    {
        $validated = $request->validate([
            'tier_id' => ['required', 'exists:sponsor_tiers,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $before = ['tier_id' => $sponsor->tier_id];
        $sponsor->update(['tier_id' => $validated['tier_id']]);
        $after = ['tier_id' => $sponsor->fresh()->tier_id];

        $adminId = $request->user()?->id ?? 0;
        $this->notificationService->audit(
            action: 'sponsor.tier_changed',
            actorId: $adminId,
            targetType: 'sponsor',
            targetId: $sponsor->id,
            beforeState: $before,
            afterState: $after,
            reason: $validated['reason'] ?? null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'data' => $sponsor->fresh()->load('sponsorTier'),
            'message' => 'Sponsor tier updated.',
        ]);
    }

    // ─── Campaign Override ────────────────────────────────────────

    /**
     * Admin can pause, resume, or delete a campaign immediately.
     */
    public function overrideCampaign(Request $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,paused,deleted'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // [m1] Hero Slider max 6 validation
        if (
            $validated['status'] === 'active'
            && $campaign->placement_type === 'hero_slider'
            && $campaign->status !== 'active'
        ) {
            $activeHeroCount = Campaign::where('placement_type', 'hero_slider')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->count();

            if ($activeHeroCount >= 6) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Cannot activate this campaign. Maximum of 6 active hero slider slides has been reached.',
                ], 422);
            }
        }

        $before = ['status' => $campaign->status];
        $campaign->update(['status' => $validated['status']]);
        $after = ['status' => $campaign->fresh()->status];

        $adminId = $request->user()?->id ?? 0;
        $this->notificationService->audit(
            action: "campaign.{$validated['status']}",
            actorId: $adminId,
            targetType: 'campaign',
            targetId: $campaign->id,
            beforeState: $before,
            afterState: $after,
            reason: $validated['reason'] ?? null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if ($validated['status'] === 'paused') {
            $this->notificationService->notifyCampaignPaused($adminId, $campaign->id, $campaign->title);
        }

        return response()->json([
            'success' => true,
            'data' => $campaign->fresh(),
            'message' => "Campaign status set to {$validated['status']}.",
        ]);
    }

    // ─── Product Override ────────────────────────────────────────

    /**
     * Admin can delete any sponsor product immediately.
     */
    public function deleteProduct(Request $request, SponsorProduct $product): JsonResponse
    {
        $before = $product->toArray();
        $product->delete();

        $adminId = $request->user()?->id ?? 0;
        $this->notificationService->audit(
            action: 'product.deleted',
            actorId: $adminId,
            targetType: 'product',
            targetId: $product->id,
            beforeState: $before,
            afterState: null,
            reason: $request->input('reason'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Product deleted by admin.',
        ]);
    }

    // ─── Best Deal Manual Selection ─────────────────────────────

    /**
     * List best deals.
     */
    public function listBestDeals(Request $request): JsonResponse
    {
        $deals = BestDeal::with(['products'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $deals,
            'message' => null,
        ]);
    }

    /**
     * Manually select products for a best deal slot.
     */
    public function createBestDeal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['exists:sponsor_products,id'],
            'tier_id' => ['nullable', 'exists:sponsor_tiers,id'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $deal = BestDeal::create(Arr::except($validated, ['product_ids']));
        $deal->products()->sync($validated['product_ids']);

        $adminId = $request->user()?->id ?? 0;
        $this->notificationService->audit(
            action: 'best_deal.created',
            actorId: $adminId,
            targetType: 'best_deal',
            targetId: $deal->id,
            beforeState: null,
            afterState: $deal->fresh()->load('products')->toArray(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'data' => $deal->fresh()->load('products'),
            'message' => 'Best deal created.',
        ], 201);
    }

    /**
     * Remove a best deal.
     */
    public function deleteBestDeal(Request $request, BestDeal $bestDeal): JsonResponse
    {
        $before = $bestDeal->load('products')->toArray();
        $bestDeal->delete();

        $adminId = $request->user()?->id ?? 0;
        $this->notificationService->audit(
            action: 'best_deal.deleted',
            actorId: $adminId,
            targetType: 'best_deal',
            targetId: $bestDeal->id,
            beforeState: $before,
            afterState: null,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Best deal removed.',
        ]);
    }

    // ─── Audit Log ───────────────────────────────────────────────

    /**
     * List audit logs with optional filters.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $query = AdminAuditLog::query()
            ->when($request->query('action'), fn ($q) => $q->where('action', $request->query('action')))
            ->when($request->query('target_type'), fn ($q) => $q->where('target_type', $request->query('target_type')))
            ->when($request->query('actor_id'), fn ($q) => $q->where('actor_id', $request->query('actor_id')))
            ->orderByDesc('created_at');

        $perPage = min((int) $request->query('per_page', 20), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
            'message' => null,
        ]);
    }

    // ─── Analytics Dashboard (Phase 5 / REQ-ANA-01) ──────────────

    /**
     * Admin analytics dashboard.
     */
    public function analyticsDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $stats = $this->analyticsService->getDashboardStats(
            $request->input('from'),
            $request->input('to'),
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
            'message' => null,
        ]);
    }

    /**
     * Export analytics data to CSV.
     */
    public function analyticsExport(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'event_type' => ['nullable', 'string', 'max:64'],
        ]);

        $filename = $this->analyticsService->exportToCsv(
            $request->input('from'),
            $request->input('to'),
            $request->input('event_type'),
        );

        // Build a download URL relative to storage/app/public so it matches
        // the /api/v1/storage/{path} proxy route (MAJOR review fix).
        $relativePath = str_replace(storage_path('app/public/'), '', $filename);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => url('api/v1/storage/'.$relativePath),
                'filename' => basename($filename),
            ],
            'message' => 'Export ready',
        ]);
    }

    // ─── Push Broadcast (M2) ─────────────────────────────────────────

    /**
     * Admin creates a push broadcast notification.
     *
     * POST /api/v1/admin/push-broadcast
     */
    public function createPushBroadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'target_role' => ['nullable', 'string', 'in:all,student,sponsor'],
            'deep_link_type' => ['nullable', 'string', 'max:64'],
            'deep_link_id' => ['nullable', 'integer'],
        ]);

        $admin = $request->user();
        $targetRole = $validated['target_role'] ?? 'all';

        // Create the push notification record
        $pushNotification = \App\Models\PushNotification::create([
            'title' => $validated['title'],
            'message' => $validated['body'],
            'target_audience' => $targetRole,
            'deep_link' => isset($validated['deep_link_type'])
                ? $validated['deep_link_type'].':'.($validated['deep_link_id'] ?? '')
                : null,
            'status' => 'sent',
            'sent_at' => now(),
            'success_count' => 0,
            'failure_count' => 0,
        ]);

        // Also create an admin notification for the audit trail
        $this->notificationService->notify(
            type: 'push.broadcast_created',
            title: 'Push Broadcast: '.$validated['title'],
            body: $validated['body'],
            actorType: 'admin',
            actorId: $admin->id,
            targetType: 'push_notification',
            targetId: $pushNotification->id,
            metadata: [
                'target_role' => $targetRole,
                'deep_link_type' => $validated['deep_link_type'] ?? null,
                'deep_link_id' => $validated['deep_link_id'] ?? null,
            ],
        );

        // Audit log
        AdminAuditLog::create([
            'action' => 'push_broadcast_created',
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'target_type' => 'PushNotification',
            'target_id' => $pushNotification->id,
            'after_state' => $validated,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pushNotification->id,
                'title' => $pushNotification->title,
                'target_role' => $targetRole,
                'status' => $pushNotification->status,
                'sent_at' => $pushNotification->sent_at->toIso8601String(),
            ],
            'meta' => null,
            'message' => 'Push broadcast created and logged successfully.',
        ], 201);
    }

    // ─── Feed Config CRUD ────────────────────────────────────────

    /**
     * List all feed configs.
     */
    public function listFeedConfigs(Request $request): JsonResponse
    {
        $configs = FeedConfig::with('tier:id,slug,name')
            ->orderBy('feed_type')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $configs,
        ]);
    }

    /**
     * Store a new feed config.
     */
    public function storeFeedConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'feed_type' => 'required|string|in:home,shop,search,profile',
            'tier_id' => 'nullable|integer|exists:sponsor_tiers,id',
            'banner_type' => 'required|string|in:hero,horizontal,popup,card',
            'insertion_interval' => 'required|integer|min:1|max:100',
            'max_banners' => 'nullable|integer|min:0|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $config = FeedConfig::create($validated);

        return response()->json([
            'success' => true,
            'data' => $config->load('tier:id,slug,name'),
            'message' => 'Feed config created successfully.',
        ], 201);
    }

    /**
     * Update a feed config.
     */
    public function updateFeedConfig(Request $request, FeedConfig $config): JsonResponse
    {
        $validated = $request->validate([
            'feed_type' => 'sometimes|string|in:home,shop,search,profile',
            'tier_id' => 'nullable|integer|exists:sponsor_tiers,id',
            'banner_type' => 'sometimes|string|in:hero,horizontal,popup,card',
            'insertion_interval' => 'sometimes|integer|min:1|max:100',
            'max_banners' => 'nullable|integer|min:0|max:20',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $config->update($validated);

        return response()->json([
            'success' => true,
            'data' => $config->fresh()->load('tier:id,slug,name'),
            'message' => 'Feed config updated successfully.',
        ]);
    }

    /**
     * Delete a feed config.
     */
    public function destroyFeedConfig(FeedConfig $config): JsonResponse
    {
        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feed config deleted successfully.',
        ]);
    }

    // ─── Benefit Category CRUD ───────────────────────────────────

    /**
     * List all benefit categories.
     */
    public function listBenefitCategories(Request $request): JsonResponse
    {
        $categories = BenefitCategory::orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Store a new benefit category.
     */
    public function storeBenefitCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:benefit_categories,name',
            'slug' => 'required|string|max:100|unique:benefit_categories,slug',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category = BenefitCategory::create($validated);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'Benefit category created successfully.',
        ], 201);
    }

    /**
     * Update a benefit category.
     */
    public function updateBenefitCategory(Request $request, BenefitCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|unique:benefit_categories,name,'.$category->id,
            'slug' => 'sometimes|string|max:100|unique:benefit_categories,slug,'.$category->id,
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'data' => $category->fresh(),
            'message' => 'Benefit category updated successfully.',
        ]);
    }

    /**
     * Delete a benefit category.
     */
    public function destroyBenefitCategory(BenefitCategory $category): JsonResponse
    {
        // Prevent deletion if category is referenced by tier benefits or overrides
        $refCount = \App\Models\TierBenefit::where('benefit_category_id', $category->id)->count()
            + \App\Models\SponsorBenefitOverride::where('benefit_category_id', $category->id)->count();

        if ($refCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: category is referenced by {$refCount} records.",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Benefit category deleted successfully.',
        ]);
    }
}
