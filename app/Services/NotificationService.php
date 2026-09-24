<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Create a notification for a recipient.
     */
    public function create(
        string $recipientType,
        int $recipientId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $deepLink = null,
        ?array $data = null,
    ): Notification {
        return Notification::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'deep_link' => $deepLink,
            'data' => $data,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    /**
     * Create notification idempotently — same type + target within window → skip.
     */
    public function createIdempotent(
        string $recipientType,
        int $recipientId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $deepLink = null,
        ?array $data = null,
        int $dedupWindowMinutes = 5,
    ): ?Notification {
        // Check for duplicate
        $cutoff = now()->subMinutes($dedupWindowMinutes);
        $exists = Notification::where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->where('type', $type)
            ->where('created_at', '>', $cutoff)
            ->exists();

        if ($exists) {
            return null;
        }

        return $this->create($recipientType, $recipientId, $type, $title, $body, $deepLink, $data);
    }

    /**
     * List notifications for a recipient (paginated).
     */
    public function listForRecipient(string $recipientType, int $recipientId, int $perPage = 20): array
    {
        $query = Notification::forRecipient($recipientType, $recipientId)
            ->orderByDesc('created_at');

        $unreadCount = (clone $query)->unread()->count();

        $notifications = $query->paginate($perPage);

        return [
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => $unreadCount,
            ],
        ];
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, string $recipientType, int $recipientId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->first();

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Mark all as read for a recipient.
     */
    public function markAllAsRead(string $recipientType, int $recipientId): int
    {
        return Notification::forRecipient($recipientType, $recipientId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
