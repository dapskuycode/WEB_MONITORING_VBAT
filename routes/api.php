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
use App\Http\Controllers\Api\BookmarkApiController;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\EntitlementApiController;
use App\Http\Controllers\Api\FeedApiController;
use App\Http\Controllers\Api\LessonApiController;
use App\Http\Controllers\Api\MediaServeController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\TrackerApiController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 0. Health Check Endpoint
    Route::get('/health', [HealthController::class, 'index']);

    // Auth (Phase 6 — TASK-BE-06)
    Route::post('/auth/login', [AuthApiController::class, 'login']);
    
    // OAuth Social Login (Phase G — AUTH-BE-01)
    Route::post('/auth/oauth/callback', [\App\Http\Controllers\Api\OAuthController::class, 'callback']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/auth/me', [AuthApiController::class, 'me']);
        Route::get('/auth/profile/status', [\App\Http\Controllers\Api\OAuthController::class, 'checkProfileStatus']);
    });

    // 1. Feed Dinamis & Infinite Scroll (Phase 7 — REQ-SF-01)
    Route::get('/feed/shop', [FeedApiController::class, 'shop']);
    Route::get('/feed/home', [FeedApiController::class, 'home']);

    // ─── PUBLIC READ-ONLY ENDPOINTS (no auth required) ──────────────────

    // 1. Sponsor & Product — Public read
    Route::get('/sponsors/tiers', [SponsorApiController::class, 'listTiers']);
    Route::get('/sponsors', [SponsorApiController::class, 'index']);
    Route::get('/sponsors/{sponsor}', [SponsorApiController::class, 'show']);
    Route::get('/sponsors/{sponsor}/storefront', [SponsorApiController::class, 'storefront']);
    Route::get('/products', [SponsorProductApiController::class, 'index']);
    Route::get('/products/{product}', [SponsorProductApiController::class, 'show']);

    // 3. Learning Material — Public read
    Route::get('/learning-materials', [LearningMaterialApiController::class, 'index']);
    Route::get('/learning-materials/{material}', [LearningMaterialApiController::class, 'show']);
    Route::post('/learning-materials/validate-youtube', [LearningMaterialApiController::class, 'validateYouTube']);

    // 3a. Course & Lesson — Public read
    Route::get('/courses', [CourseApiController::class, 'index']);
    Route::get('/courses/free-class', [CourseApiController::class, 'freeClass']);
    Route::get('/courses/{id}', [CourseApiController::class, 'show']);
    Route::get('/lessons', [LessonApiController::class, 'index']);
    Route::get('/lessons/{id}', [LessonApiController::class, 'show']);

    // 3b. Quiz — Public read
    Route::get('/quizzes', [QuizApiController::class, 'index']);
    Route::get('/quizzes/{id}', [QuizApiController::class, 'show']);

    // 4. Placement & Probabilistic Selection — Public read
    Route::get('/placements/best-deal', [PlacementApiController::class, 'bestDeal']);
    Route::get('/placements/{type}', [PlacementApiController::class, 'show']);

    // 4. Payment — Package catalog (public)
    Route::get('/payment/packages', [PaymentApiController::class, 'packages']);

    // 2. Banners & Promosi Sponsor — Public read
    Route::get('/banners/hero', [CampaignApiController::class, 'getHeroSliders']);
    Route::get('/banners/shop-horizontal', [CampaignApiController::class, 'getShopHorizontalBanners']);
    Route::get('/banners/cards', [CampaignApiController::class, 'getCardSliders']);
    Route::get('/banners/card', [CampaignApiController::class, 'getCardSliders']);
    Route::get('/banners/popup', [CampaignApiController::class, 'getPopupBanners']);
    Route::get('/sponsors/partners', [CampaignApiController::class, 'getBrandPartners']);
    Route::get('/shop/products', [CampaignApiController::class, 'getAllProducts']);
    Route::get('/shop/best-deals', [CampaignApiController::class, 'getBestDeals']);

    // 2. Harga Dinamis & Event Diskon Global — Public read
    Route::get('/shop/events/active', [EventApiController::class, 'getActiveEvent']);

    // 8. Analytics Event Ingestion (Phase 5 — REQ-ANA-01) — Public (anonymous tracking)
    Route::post('/events', [EventApiController::class, 'ingestBatch']);

    // 4. Demografi Profil Wilayah — Public read
    Route::get('/regions/provinces', [DemographicApiController::class, 'getProvinces']);
    Route::get('/regions/cities/{provinceId}', [DemographicApiController::class, 'getCitiesByProvince']);

    // 5. Push Notifications Broadcast Feed — Public read
    Route::get('/notifications', [NotificationApiController::class, 'getNotifications']);

    // ─── AUTHENTICATED ENDPOINTS (auth:sanctum required) ────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // 1. Sponsor & Product — Write operations
        Route::post('/sponsors', [SponsorApiController::class, 'store']);
        Route::put('/sponsors/{sponsor}', [SponsorApiController::class, 'update']);
        Route::delete('/sponsors/{sponsor}', [SponsorApiController::class, 'destroy']);
        Route::post('/sponsors/{sponsor}/logo', [SponsorApiController::class, 'uploadLogo']);
        Route::post('/sponsors/{sponsor}/co-branding', [SponsorApiController::class, 'uploadCoBranding']);

        Route::post('/products', [SponsorProductApiController::class, 'store']);
        Route::put('/products/{product}', [SponsorProductApiController::class, 'update']);
        Route::delete('/products/{product}', [SponsorProductApiController::class, 'destroy']);
        Route::post('/products/{product}/image', [SponsorProductApiController::class, 'uploadImage']);

        // 3. Learning Material — Write operations
        Route::post('/learning-materials', [LearningMaterialApiController::class, 'store']);
        Route::put('/learning-materials/{material}', [LearningMaterialApiController::class, 'update']);
        Route::delete('/learning-materials/{material}', [LearningMaterialApiController::class, 'destroy']);
        Route::post('/learning-materials/{material}/thumbnail', [LearningMaterialApiController::class, 'uploadThumbnail']);
        Route::post('/learning-materials/{material}/pdf', [LearningMaterialApiController::class, 'uploadPdf']);
        Route::post('/learning-materials/{material}/progress', [LearningMaterialApiController::class, 'recordProgress']);
        Route::get('/learning-materials/{material}/analytics', [LearningMaterialApiController::class, 'analytics']);

        // 3a. Course & Lesson — Write operations
        Route::post('/courses', [CourseApiController::class, 'store']);
        Route::put('/courses/{id}', [CourseApiController::class, 'update']);
        Route::post('/courses/{id}/archive', [CourseApiController::class, 'archive']);
        Route::delete('/courses/{id}', [CourseApiController::class, 'destroy']);
        Route::post('/lessons', [LessonApiController::class, 'store']);
        Route::put('/lessons/{id}', [LessonApiController::class, 'update']);
        Route::delete('/lessons/{id}', [LessonApiController::class, 'destroy']);

        // 3b. Quiz — Write operations & attempts
        Route::post('/quizzes', [QuizApiController::class, 'store']);
        Route::put('/quizzes/{id}', [QuizApiController::class, 'update']);
        Route::delete('/quizzes/{id}', [QuizApiController::class, 'destroy']);
        Route::post('/quizzes/{id}/questions', [QuizApiController::class, 'addQuestion']);
        Route::put('/quiz-questions/{id}', [QuizApiController::class, 'updateQuestion']);
        Route::delete('/quiz-questions/{id}', [QuizApiController::class, 'deleteQuestion']);
        Route::post('/quizzes/{id}/start-attempt', [QuizApiController::class, 'startAttempt']);
        Route::post('/quizzes/{id}/submit-attempt', [QuizApiController::class, 'submitAttempt']);

        // 3c. Bookmarks — Learning bookmarks (separate from wishlist)
        Route::get('/bookmarks', [BookmarkApiController::class, 'index']);
        Route::post('/bookmarks/toggle', [BookmarkApiController::class, 'toggle']);
        Route::post('/bookmarks/check', [BookmarkApiController::class, 'check']);

        // 3d. Entitlements — Access gating & certificates
        Route::get('/entitlements', [EntitlementApiController::class, 'index']);
        Route::get('/entitlements/courses/{courseId}/access', [EntitlementApiController::class, 'checkAccess']);
        Route::get('/entitlements/courses/{courseId}/manifest', [EntitlementApiController::class, 'downloadManifest']);
        Route::post('/entitlements/courses/{courseId}/kta-certificate', [EntitlementApiController::class, 'issueKtaCertificate']);

        // 4. Payment — Transactions (auth required)
        Route::post('/payment/transactions', [PaymentApiController::class, 'createTransaction']);
        Route::get('/payment/transactions', [PaymentApiController::class, 'transactions']);
        Route::get('/payment/transactions/{id}', [PaymentApiController::class, 'transactionDetail']);

        // 5. Media Authorization — Protected media serve
        Route::get('/media/serve/{type}/{id}', [MediaServeController::class, 'serve'])->name('api.media.serve');
        Route::post('/media/access-check', [MediaServeController::class, 'checkAccess']);

        // 3b. Bulk Import (REQ-ADM-02)
        Route::post('/learning-materials/bulk-import', [LearningMaterialApiController::class, 'bulkImport']);
        Route::get('/learning-materials/bulk-import/template', [LearningMaterialApiController::class, 'bulkImportTemplate']);

        // 4. Placement — Write operations
        Route::post('/placements/impression', [PlacementApiController::class, 'logImpression']);
        Route::post('/placements/click', [PlacementApiController::class, 'logClick']);

        // 3. Log Tracker (Impression, Click, Wishlist)
        Route::post('/track', [TrackerApiController::class, 'logInteraction']);
        Route::post('/wishlist/toggle', [TrackerApiController::class, 'toggleWishlist']);

        // 4. Demografi Profil Pengguna
        Route::post('/user/demographics', [DemographicApiController::class, 'updateDemographics']);

        // 9. User Notification API (Phase 5 — REQ-ANA-02)
        Route::get('/user/notifications', [NotificationApiController::class, 'listUserNotifications']);
        Route::post('/user/notifications/{id}/read', [NotificationApiController::class, 'markUserNotificationRead']);
        Route::post('/user/notifications/read-all', [NotificationApiController::class, 'markAllUserNotificationsRead']);
    });

    // ─── ADMIN ENDPOINTS (auth:sanctum + role:super_admin) ─────────────
    Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('admin')->group(function () {
        // Notifications
        Route::get('/notifications', [AdminApiController::class, 'notifications']);
        Route::get('/notifications/unread-count', [AdminApiController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [AdminApiController::class, 'markNotificationRead']);
        Route::post('/notifications/{id}/read-all', [AdminApiController::class, 'markAllNotificationsRead']);

        // Sponsor management
        Route::post('/sponsors/{sponsor}/benefit-overrides', [AdminApiController::class, 'overrideBenefit']);
        Route::put('/sponsors/{sponsor}/tier', [AdminApiController::class, 'changeSponsorTier']);

        // Campaign management
        Route::put('/campaigns/{campaign}', [AdminApiController::class, 'overrideCampaign']);
        Route::delete('/products/{product}', [AdminApiController::class, 'deleteProduct']);

        // Best deals
        Route::get('/best-deals', [AdminApiController::class, 'listBestDeals']);
        Route::post('/best-deals', [AdminApiController::class, 'createBestDeal']);
        Route::delete('/best-deals/{bestDeal}', [AdminApiController::class, 'deleteBestDeal']);

        // Placement configs
        Route::get('/placement-configs', [PlacementApiController::class, 'listConfigs']);
        Route::post('/placement-configs', [PlacementApiController::class, 'storeConfig']);
        Route::put('/placement-configs/{config}', [PlacementApiController::class, 'updateConfig']);
        Route::delete('/placement-configs/{config}', [PlacementApiController::class, 'destroyConfig']);

        // Feed configs
        Route::get('/feed-configs', [AdminApiController::class, 'listFeedConfigs']);
        Route::post('/feed-configs', [AdminApiController::class, 'storeFeedConfig']);
        Route::put('/feed-configs/{config}', [AdminApiController::class, 'updateFeedConfig']);
        Route::delete('/feed-configs/{config}', [AdminApiController::class, 'destroyFeedConfig']);

        // Benefit categories
        Route::get('/benefit-categories', [AdminApiController::class, 'listBenefitCategories']);
        Route::post('/benefit-categories', [AdminApiController::class, 'storeBenefitCategory']);
        Route::put('/benefit-categories/{category}', [AdminApiController::class, 'updateBenefitCategory']);
        Route::delete('/benefit-categories/{category}', [AdminApiController::class, 'destroyBenefitCategory']);

        // Audit logs
        Route::get('/audit-logs', [AdminApiController::class, 'auditLogs']);

        // Analytics
        Route::get('/analytics/dashboard', [AdminApiController::class, 'analyticsDashboard']);
        Route::get('/analytics/export', [AdminApiController::class, 'analyticsExport']);

        // Push Broadcast (M2)
        Route::post('/push-broadcast', [AdminApiController::class, 'createPushBroadcast']);
    });

    // 5. Webhooks (public, no auth)
    Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans']);

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
