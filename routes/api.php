<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\DemographicApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LearningMaterialApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PlacementApiController;
use App\Http\Controllers\Api\SponsorProductApiController;
use App\Http\Controllers\Api\SponsorApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\TrackerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 0. Health Check Endpoint
    Route::get('/health', [HealthController::class, 'index']);

    // Auth (Phase 6 — TASK-BE-06)
    Route::post('/auth/login', [AuthApiController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/auth/me', [AuthApiController::class, 'me']);
    });

    // 1. Sponsor & Product CRUD (Phase 1 — REQ-SF-01, REQ-SF-02)
    Route::get('/sponsors/tiers', [SponsorApiController::class, 'listTiers']);
    Route::apiResource('sponsors', SponsorApiController::class);
    Route::post('/sponsors/{id}/logo', [SponsorApiController::class, 'uploadLogo']);
    Route::apiResource('products', SponsorProductApiController::class);
    Route::post('/products/{id}/image', [SponsorProductApiController::class, 'uploadImage']);

    // 3. Learning Material CRUD (Phase 2 — REQ-LM-01, REQ-LM-02, REQ-LM-03)
    Route::apiResource('learning-materials', LearningMaterialApiController::class);
    Route::post('/learning-materials/{id}/thumbnail', [LearningMaterialApiController::class, 'uploadThumbnail']);
    Route::post('/learning-materials/{id}/pdf', [LearningMaterialApiController::class, 'uploadPdf']);
    Route::post('/learning-materials/validate-youtube', [LearningMaterialApiController::class, 'validateYouTube']);
    Route::post('/learning-materials/{id}/progress', [LearningMaterialApiController::class, 'recordProgress']);
    Route::get('/learning-materials/{id}/analytics', [LearningMaterialApiController::class, 'analytics']);

    // 4. Placement & Probabilistic Selection (Phase 3 — REQ-SF-03)
    Route::get('/placements/best-deal', [PlacementApiController::class, 'bestDeal']);
    Route::get('/placements/{type}', [PlacementApiController::class, 'show']);
    Route::post('/placements/impression', [PlacementApiController::class, 'logImpression']);
    Route::post('/placements/click', [PlacementApiController::class, 'logClick']);

    // Admin placement endpoints
    Route::get('/admin/placement-configs', [PlacementApiController::class, 'listConfigs']);

    // 7. Admin Override & Notification System (Phase 4 — REQ-ADM-02)
    Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
        Route::get('/notifications', [AdminApiController::class, 'notifications']);
        Route::get('/notifications/unread-count', [AdminApiController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [AdminApiController::class, 'markNotificationRead']);
        Route::post('/notifications/{id}/read-all', [AdminApiController::class, 'markAllNotificationsRead']);

        Route::post('/sponsors/{sponsor}/benefit-overrides', [AdminApiController::class, 'overrideBenefit']);
        Route::put('/sponsors/{sponsor}/tier', [AdminApiController::class, 'changeSponsorTier']);

        Route::put('/campaigns/{campaign}', [AdminApiController::class, 'overrideCampaign']);
        Route::delete('/products/{product}', [AdminApiController::class, 'deleteProduct']);

        Route::get('/best-deals', [AdminApiController::class, 'listBestDeals']);
        Route::post('/best-deals', [AdminApiController::class, 'createBestDeal']);
        Route::delete('/best-deals/{bestDeal}', [AdminApiController::class, 'deleteBestDeal']);

        Route::get('/audit-logs', [AdminApiController::class, 'auditLogs']);
    });

    // 2. Banners & Promosi Sponsor (Existing — REQ-SF-03)
    Route::get('/banners/hero', [CampaignApiController::class, 'getHeroSliders']);
    Route::get('/banners/shop-horizontal', [CampaignApiController::class, 'getShopHorizontalBanners']);
    Route::get('/banners/cards', [CampaignApiController::class, 'getCardSliders']);
    Route::get('/banners/card', [CampaignApiController::class, 'getCardSliders']);
    Route::get('/banners/popup', [CampaignApiController::class, 'getPopupBanners']);
    Route::get('/sponsors/partners', [CampaignApiController::class, 'getBrandPartners']);
    Route::get('/shop/products', [CampaignApiController::class, 'getAllProducts']);
    Route::get('/shop/best-deals', [CampaignApiController::class, 'getBestDeals']);

    // 2. Harga Dinamis & Event Diskon Global
    Route::get('/shop/events/active', [EventApiController::class, 'getActiveEvent']);

    // 3. Log Tracker (Impression, Click, Wishlist)
    Route::post('/track', [TrackerApiController::class, 'logInteraction']);
    Route::post('/wishlist/toggle', [TrackerApiController::class, 'toggleWishlist']);

    // 4. Demografi Profil Pengguna & Wilayah
    Route::post('/user/demographics', [DemographicApiController::class, 'updateDemographics']);
    Route::get('/regions/provinces', [DemographicApiController::class, 'getProvinces']);
    Route::get('/regions/cities/{provinceId}', [DemographicApiController::class, 'getCitiesByProvince']);

    // 5. Push Notifications Broadcast Feed
    Route::get('/notifications', [NotificationApiController::class, 'getNotifications']);

    // 8. Analytics Event Ingestion (Phase 5 — REQ-ANA-01)
    Route::post('/events', [EventApiController::class, 'ingestBatch']);

    // 9. User Notification API (Phase 5 — REQ-ANA-02)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/notifications', [NotificationApiController::class, 'listUserNotifications']);
        Route::post('/user/notifications/{id}/read', [NotificationApiController::class, 'markUserNotificationRead']);
        Route::post('/user/notifications/read-all', [NotificationApiController::class, 'markAllUserNotificationsRead']);
    });

    // 10. Admin Analytics Dashboard (Phase 5 — REQ-ANA-01)
    Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
        Route::get('/analytics/dashboard', [AdminApiController::class, 'analyticsDashboard']);
        Route::get('/analytics/export', [AdminApiController::class, 'analyticsExport']);
    });

    // 6. Storage File Proxy with CORS (for Flutter Web CanvasKit & Mobile)
    Route::get('/storage/{path}', function ($path) {
        $fullPath = storage_path('app/public/'.$path);
        if (! file_exists($fullPath)) {
            $assetPath = public_path($path);
            if (file_exists($assetPath)) {
                $fullPath = $assetPath;
            } else {
                abort(404);
            }
        }
        $mime = mime_content_type($fullPath) ?: 'image/jpeg';

        return response()->file($fullPath, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Content-Type' => $mime,
        ]);
    })->where('path', '.*');
});
