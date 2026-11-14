<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LmsNotificationService;
use App\Services\LmsOfflineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LmsBatch11Controller extends Controller
{
    public function __construct(
        private LmsOfflineService $offline,
        private LmsNotificationService $notifications,
    ) {}

    public function listOfflineLicenses(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->offline->listLicenses(
                $request->user(),
                $request->query('device_id') ? (string) $request->query('device_id') : null,
            ),
        ]);
    }

    public function issueOfflineLicense(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->offline->issueLicense($request->user(), $id, $request->all()),
        ], 201);
    }

    public function renewOfflineLicense(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->offline->renewLicense($request->user(), $id, $request->all()),
        ]);
    }

    public function revokeOfflineLicense(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->offline->revokeLicense(
                $request->user(),
                $id,
                $request->input('reason') ? (string) $request->input('reason') : null,
            ),
        ]);
    }

    public function downloadOfflineFile(Request $request, string $id): StreamedResponse
    {
        $deviceId = (string) ($request->header('X-LMS-Device-Id') ?: $request->query('device_id', ''));
        abort_if(trim($deviceId) === '', 422, 'Identitas perangkat (device_id) wajib disertakan.');

        return $this->offline->downloadFile($request->user(), $id, $deviceId);
    }

    public function syncOfflineEvents(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->offline->syncEvents($request->user(), $request->all()),
        ]);
    }

    public function revokeOfflineDevice(Request $request, string $deviceId): JsonResponse
    {
        $targetUserId = $request->user()->isSuperAdmin() && $request->input('user_id')
            ? (int) $request->input('user_id')
            : (int) $request->user()->id;

        $revokedCount = $this->offline->revokeDevice($request->user(), $targetUserId, $deviceId);

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $deviceId,
                'revoked_licenses' => $revokedCount,
            ],
        ]);
    }

    public function registerPushToken(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->notifications->registerDeviceToken($request->user(), $request->all()),
        ]);
    }

    public function revokePushToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|min:4|max:128',
        ]);
        $revoked = $this->notifications->revokeDeviceToken($request->user(), $data['device_id']);

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $data['device_id'],
                'revoked' => $revoked > 0,
            ],
        ]);
    }

    public function resolveDeepLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'deep_link' => 'required|string|min:2|max:255',
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->notifications->resolveDeepLink($request->user(), $data['deep_link']),
        ]);
    }
}
