<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Ambil daftar notifikasi yang sudah terkirim / siap ditampilkan di inbox aplikasi.
     */
    public function getNotifications(): JsonResponse
    {
        $notifications = PushNotification::where('status', 'sent')
            ->orWhereNotNull('sent_at')
            ->latest('sent_at')
            ->latest('id')
            ->limit(30)
            ->get();

        $data = $notifications->map(function ($notif) {
            return [
                'id' => (string) $notif->id,
                'title' => $notif->title,
                'body' => $notif->message,
                'target_url' => $notif->deep_link ?: 'https://shopee.co.id',
                'target_audience' => $notif->target_audience,
                'sent_at' => $notif->sent_at ? $notif->sent_at->toIso8601String() : $notif->created_at->toIso8601String(),
                'status' => $notif->status,
                'sponsor_name' => 'Sponsor Resmi VbatPonsel',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar notifikasi berhasil diambil',
            'data' => $data,
        ]);
    }

    /**
     * User-facing notification inbox (Phase 5 — REQ-ANA-02).
     * GET /api/v1/user/notifications
     */
    public function listUserNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        $result = $this->notificationService->listForRecipient(
            'user',
            $user->id,
            $perPage,
        );

        return response()->json([
            'success' => true,
            'message' => 'User notifications retrieved',
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Mark a user notification as read.
     */
    public function markUserNotificationRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $ok = $this->notificationService->markAsRead($id, 'user', $user->id);

        if (! $ok) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marked as read',
        ]);
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllUserNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead('user', $user->id);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marked as read",
            'data' => ['count' => $count],
        ]);
    }
}
