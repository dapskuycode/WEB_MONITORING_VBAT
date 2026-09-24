<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    /**
     * Create an admin notification.
     *
     * @param string $type e.g. 'product.uploaded', 'campaign.paused'
     * @param string $title
     * @param string|null $body
     * @param string $actorType 'sponsor' | 'admin' | 'system'
     * @param int|null $actorId
     * @param string|null $targetType
     * @param int|null $targetId
     * @param array|null $metadata
     * @param string|null $deepLink
     * @param int|null $recipientAdminId null = broadcast
     * @return AdminNotification
     */
    public function notify(
        string $type,
        string $title,
        ?string $body = null,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $metadata = null,
        ?string $deepLink = null,
        ?int $recipientAdminId = null,
    ): AdminNotification {
        return AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'deep_link' => $deepLink,
            'recipient_admin_id' => $recipientAdminId,
        ]);
    }

    /**
     * List notifications for an admin with optional filters.
     *
     * @return array{data: Collection<int, AdminNotification>, unread_count: int}
     */
    public function listForAdmin(
        ?int $adminId = null,
        bool $unreadOnly = false,
        ?string $type = null,
        int $perPage = 20,
    ): array {
        $query = AdminNotification::query()
            ->when($adminId, fn (Builder $q) => $q->where(function (Builder $sub) use ($adminId) {
                $sub->whereNull('recipient_admin_id')
                    ->orWhere('recipient_admin_id', $adminId);
            }))
            ->when($unreadOnly, fn (Builder $q) => $q->where('is_read', false))
            ->when($type, fn (Builder $q) => $q->where('type', $type))
            ->orderByDesc('created_at');

        $unreadCount = AdminNotification::query()
            ->when($adminId, fn (Builder $q) => $q->where(function (Builder $sub) use ($adminId) {
                $sub->whereNull('recipient_admin_id')
                    ->orWhere('recipient_admin_id', $adminId);
            }))
            ->where('is_read', false)
            ->count();

        return [
            'data' => $query->limit($perPage)->get(),
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId, ?int $adminId = null): bool
    {
        $query = AdminNotification::where('id', $notificationId);

        if ($adminId) {
            $query->where(function (Builder $q) use ($adminId) {
                $q->whereNull('recipient_admin_id')
                  ->orWhere('recipient_admin_id', $adminId);
            });
        }

        $notification = $query->first();

        if (!$notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Log an admin audit action.
     */
    public function audit(
        string $action,
        int $actorId,
        string $targetType,
        int $targetId,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'action' => $action,
            'actor_type' => 'admin',
            'actor_id' => $actorId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'reason' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Convenience: notify when a sponsor uploads a product.
     */
    public function notifyProductUploaded(int $sponsorId, int $productId, string $productName): AdminNotification
    {
        return $this->notify(
            type: 'product.uploaded',
            title: "New product uploaded: {$productName}",
            body: "Sponsor #{$sponsorId} uploaded a new product.",
            actorType: 'sponsor',
            actorId: $sponsorId,
            targetType: 'product',
            targetId: $productId,
            deepLink: "/admin/products/{$productId}",
        );
    }

    /**
     * Convenience: notify when a campaign is paused by admin.
     */
    public function notifyCampaignPaused(int $adminId, int $campaignId, string $campaignTitle): AdminNotification
    {
        return $this->notify(
            type: 'campaign.paused',
            title: "Campaign paused: {$campaignTitle}",
            body: "Admin #{$adminId} paused the campaign.",
            actorType: 'admin',
            actorId: $adminId,
            targetType: 'campaign',
            targetId: $campaignId,
            deepLink: "/admin/campaigns/{$campaignId}",
        );
    }
}
