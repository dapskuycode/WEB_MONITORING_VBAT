<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use Illuminate\Http\JsonResponse;

class NotificationApiController extends Controller
{
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
}
