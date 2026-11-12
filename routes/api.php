<?php

use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BookmarkApiController;
use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\DemographicApiController;
use App\Http\Controllers\Api\EntitlementApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\FeedApiController;
use App\Http\Controllers\Api\GamificationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\InfoContentController;
use App\Http\Controllers\Api\LearningMaterialApiController;
use App\Http\Controllers\Api\LegalController;
use App\Http\Controllers\Api\LessonApiController;
use App\Http\Controllers\Api\LmsDocumentController;
use App\Http\Controllers\Api\LmsGradeController;
use App\Http\Controllers\Api\LmsImportController;
use App\Http\Controllers\Api\LmsOrderController;
use App\Http\Controllers\Api\LmsOutcomeController;
use App\Http\Controllers\Api\MediaServeController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\PlacementApiController;
use App\Http\Controllers\Api\PlaybackController;
use App\Http\Controllers\Api\PublicMediaController;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\SponsorApiController;
use App\Http\Controllers\Api\SponsorProductApiController;
use App\Http\Controllers\Api\StudentCourseController;
use App\Http\Controllers\Api\StudentQuizController;
use App\Http\Controllers\Api\TrackerApiController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\SanitizeLmsCatalog;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 0. Health Check Endpoint
    Route::get('/health', [HealthController::class, 'index']);

    // Auth (Phase 6 — TASK-BE-06)
    Route::post('/auth/register', [AuthApiController::class, 'register']);
    Route::post('/auth/login', [AuthApiController::class, 'login'])->middleware('throttle:10,1');

    // OAuth Social Login (Phase G — AUTH-BE-01)
    Route::post('/auth/oauth/callback', [OAuthController::class, 'callback']);

    // Profile mutations use the authenticated account only.
    Route::post('/user/demographics', [DemographicApiController::class, 'updateDemographics'])->middleware('auth:sanctum');

    // Client activation cannot create grants; purchases require server verification.
    Route::post('/membership/activate', [MembershipController::class, 'activate'])->middleware('auth:sanctum');

    // Log Tracker & Wishlist (Mendukung auth:sanctum maupun email/user_id fallback / anonymous)
    Route::post('/track', [TrackerApiController::class, 'logInteraction']);
    Route::post('/wishlist/toggle', [TrackerApiController::class, 'toggleWishlist']);
    Route::get('/wishlist/user', [TrackerApiController::class, 'getUserWishlist']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
        Route::get('/lms/dashboard', [LmsOutcomeController::class, 'dashboard']);
        Route::get('/lms/history', [LmsOutcomeController::class, 'history']);
        Route::get('/lms/courses/{id}/outcome', [LmsOutcomeController::class, 'course'])->whereNumber('id');
        Route::get('/lms/credentials', [LmsOutcomeController::class, 'credentials']);
        Route::post('/lms/courses/{id}/certificate', [LmsOutcomeController::class, 'issue'])->whereNumber('id');
        Route::post('/lms/credentials/{id}/visibility', [LmsOutcomeController::class, 'visibility'])->whereNumber('id');
        Route::get('/lms/credentials/{id}/document', [LmsOutcomeController::class, 'document'])->whereNumber('id');
        Route::post('/admin/lms/credentials/{id}/revoke', [LmsOutcomeController::class, 'revoke'])->whereNumber('id')->middleware('role:super_admin');
        Route::get('/admin/lms/reviews', [LmsGradeController::class, 'index'])->middleware('role:super_admin');
        Route::get('/admin/lms/reviews/{id}', [LmsGradeController::class, 'show'])->whereNumber('id')->middleware('role:super_admin');
        Route::post('/admin/lms/reviews/{id}/draft', [LmsGradeController::class, 'draft'])->whereNumber('id')->middleware('role:super_admin');
        Route::post('/admin/lms/reviews/{id}/publish', [LmsGradeController::class, 'publish'])->whereNumber('id')->middleware('role:super_admin');
        Route::get('/lms/imports/template', [LmsImportController::class, 'template'])->middleware('role:super_admin');
        Route::post('/lms/imports', [LmsImportController::class, 'preview'])->middleware(['role:super_admin', 'throttle:lms-import']);
        Route::get('/lms/imports/{id}', [LmsImportController::class, 'show'])->whereUuid('id')->middleware('role:super_admin');
        Route::post('/lms/imports/{id}/apply', [LmsImportController::class, 'apply'])->whereUuid('id')->middleware('role:super_admin');
        Route::get('/lms/imports/{id}/errors', [LmsImportController::class, 'errors'])->whereUuid('id')->middleware('role:super_admin');
        Route::post('/lms/uploads', [LmsDocumentController::class, 'upload'])->middleware(['role:super_admin', 'throttle:lms-upload']);
        Route::get('/lms/uploads/{id}', [LmsDocumentController::class, 'status'])->whereUuid('id')->middleware('role:super_admin');
        Route::post('/lms/materials/{id}/reading', [LmsDocumentController::class, 'start'])->whereNumber('id')->middleware('throttle:lms-reading');
        Route::post('/lms/reading/{id}/events', [LmsDocumentController::class, 'event'])->whereUuid('id')->middleware('throttle:lms-reading');
        Route::get('/lms/quizzes/{id}', [StudentQuizController::class, 'show'])->whereNumber('id')->middleware('throttle:lms-quiz-read');
        Route::post('/lms/quizzes/{id}/attempts', [StudentQuizController::class, 'start'])->whereNumber('id')->middleware('throttle:lms-quiz-write');
        Route::get('/lms/attempts/{id}', [StudentQuizController::class, 'read'])->whereNumber('id')->middleware('throttle:lms-quiz-read');
        Route::post('/lms/attempts/{id}/answers', [StudentQuizController::class, 'save'])->whereNumber('id')->middleware('throttle:lms-quiz-save');
        Route::post('/lms/attempts/{id}/submit', [StudentQuizController::class, 'submit'])->whereNumber('id')->middleware('throttle:lms-quiz-write');
        Route::get('/lms/courses', [StudentCourseController::class, 'index']);
        Route::post('/lms/materials/{id}/playback', [PlaybackController::class, 'start'])->whereNumber('id');
        Route::post('/lms/playback/{session}/events', [PlaybackController::class, 'event'])->whereUuid('session');
        Route::get('/lms/courses/{id}', [StudentCourseController::class, 'show'])->whereNumber('id');
        Route::get('/auth/me', [AuthApiController::class, 'me']);
        Route::get('/auth/profile/status', [OAuthController::class, 'checkProfileStatus']);

        // Membership & KTA (Phase G — MEM-BE-01)
        Route::get('/membership', [MembershipController::class, 'show']);

        // Certificates & QR (Phase G — CERT-BE-01)
        Route::get('/certificates', [CertificateController::class, 'index']);
        Route::get('/certificates/{certificate}', [CertificateController::class, 'show']);

        // Gamification: Badge, Achievement, Streak (Phase G — GAM-BE-01)
        Route::get('/achievements', [GamificationController::class, 'getAchievements']);
        Route::get('/streaks', [GamificationController::class, 'getStreaks']);
    });

    // Public membership verification
    Route::get('/membership/verify/{membershipNumber}', [MembershipController::class, 'verify']);

    Route::get('/lms/credentials/verify/{token}', [LmsOutcomeController::class, 'verify'])->whereUuid('token');

    // Public certificate verification
    Route::get('/certificates/verify/{certificateNumber}', [CertificateController::class, 'verify']);

    // Public badges list
    Route::get('/badges', [GamificationController::class, 'getAllBadges']);

    // Info & WhatsApp CTA (Phase G — INFO-BE-01)
    Route::get('/info', [InfoContentController::class, 'index']);
    Route::get('/info/{slug}', [InfoContentController::class, 'show']);

    // Legal content (Phase G — LEGAL-BE-01)
    Route::get('/legal/terms', [LegalController::class, 'getTerms']);
    Route::get('/legal/privacy', [LegalController::class, 'getPrivacy']);
    Route::get('/legal/about', [LegalController::class, 'getAbout']);
    Route::get('/legal/consent', [LegalController::class, 'getConsent']);

    // 1. Feed Dinamis & Infinite Scroll (Phase 7 — REQ-SF-01)
    Route::get('/feed/shop', [FeedApiController::class, 'shop']);
    Route::get('/feed/home', [FeedApiController::class, 'home']);

    // ─── PUBLIC READ-ONLY ENDPOINTS (no auth required) ──────────────────

    // 1. Sponsor & Product — Public read
    Route::get('/sponsors/tiers', [SponsorApiController::class, 'listTiers']);
    Route::get('/sponsors/partners', [SponsorApiController::class, 'showcase']);
    Route::get('/sponsors', [SponsorApiController::class, 'index']);
    Route::get('/sponsors/{sponsor}', [SponsorApiController::class, 'show']);
    Route::get('/sponsors/{sponsor}/storefront', [SponsorApiController::class, 'storefront']);
    Route::get('/products', [SponsorProductApiController::class, 'index']);
    Route::get('/products/{product}', [SponsorProductApiController::class, 'show']);

    // 3. Learning Material — Public read
    Route::get('/learning-materials', [LearningMaterialApiController::class, 'index'])->middleware(SanitizeLmsCatalog::class);
    Route::get('/learning-materials/{material}', [LearningMaterialApiController::class, 'show'])->middleware(SanitizeLmsCatalog::class);
    Route::post('/learning-materials/validate-youtube', [LearningMaterialApiController::class, 'validateYouTube'])->middleware(['auth:sanctum', 'role:super_admin']);

    // 3a. Course & Lesson — Public read
    Route::get('/courses', [CourseApiController::class, 'index'])->middleware(SanitizeLmsCatalog::class);
    Route::get('/courses/free-class', [CourseApiController::class, 'freeClass'])->middleware(SanitizeLmsCatalog::class);
    Route::get('/courses/{id}', [CourseApiController::class, 'show'])->middleware(SanitizeLmsCatalog::class);
    Route::get('/lessons', [LessonApiController::class, 'index'])->middleware(SanitizeLmsCatalog::class);
    Route::get('/lessons/{id}', [LessonApiController::class, 'show'])->middleware(SanitizeLmsCatalog::class);

    // 3b. Quiz — Public read
    Route::get('/quizzes', [QuizApiController::class, 'index'])->middleware(SanitizeLmsCatalog::class);
    Route::get('/quizzes/{id}', [QuizApiController::class, 'show'])->middleware(SanitizeLmsCatalog::class);

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
    Route::get('/shop/events', [EventApiController::class, 'getActiveEvent']);
    Route::get('/shop/events/active', [EventApiController::class, 'getActiveEvent']);
    Route::get('/shop/events/{event}', [EventApiController::class, 'show']);

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
        Route::post('/products/{product}/best-deal', [SponsorProductApiController::class, 'submitToBestDeal']);
        Route::delete('/products/{product}/best-deal', [SponsorProductApiController::class, 'removeFromBestDeal']);

        // 3. Learning Material — Write operations
        Route::post('/learning-materials', [LearningMaterialApiController::class, 'store'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::put('/learning-materials/{material}', [LearningMaterialApiController::class, 'update'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::delete('/learning-materials/{material}', [LearningMaterialApiController::class, 'destroy'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/learning-materials/{material}/thumbnail', [LearningMaterialApiController::class, 'uploadThumbnail'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/learning-materials/{material}/pdf', [LearningMaterialApiController::class, 'uploadPdf'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/learning-materials/{material}/progress', [LearningMaterialApiController::class, 'recordProgress']);
        Route::get('/learning-materials/{material}/analytics', [LearningMaterialApiController::class, 'analytics'])->middleware(['auth:sanctum', 'role:super_admin']);

        // 3a. Course & Lesson — Write operations
        Route::post('/courses', [CourseApiController::class, 'store'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::put('/courses/{id}', [CourseApiController::class, 'update'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/courses/{id}/archive', [CourseApiController::class, 'archive'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::delete('/courses/{id}', [CourseApiController::class, 'destroy'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/lessons', [LessonApiController::class, 'store'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::put('/lessons/{id}', [LessonApiController::class, 'update'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::delete('/lessons/{id}', [LessonApiController::class, 'destroy'])->middleware(['auth:sanctum', 'role:super_admin']);

        // 3b. Quiz — Write operations & attempts
        Route::post('/quizzes', [QuizApiController::class, 'store'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::put('/quizzes/{id}', [QuizApiController::class, 'update'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::delete('/quizzes/{id}', [QuizApiController::class, 'destroy'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/quizzes/{id}/questions', [QuizApiController::class, 'addQuestion'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::put('/quiz-questions/{id}', [QuizApiController::class, 'updateQuestion'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::delete('/quiz-questions/{id}', [QuizApiController::class, 'deleteQuestion'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::post('/quizzes/{id}/start-attempt', [QuizApiController::class, 'startAttempt']);
        Route::post('/quizzes/{id}/submit-attempt', [QuizApiController::class, 'submitAttempt']);

        // 3c. Bookmarks — Learning bookmarks (separate from wishlist)
        Route::get('/bookmarks', [BookmarkApiController::class, 'index'])->middleware(SanitizeLmsCatalog::class);
        Route::post('/bookmarks/toggle', [BookmarkApiController::class, 'toggle'])->middleware(SanitizeLmsCatalog::class);
        Route::post('/bookmarks/check', [BookmarkApiController::class, 'check'])->middleware(SanitizeLmsCatalog::class);

        Route::put('/lms-admin/{kind}/{id}/order', [LmsOrderController::class, 'update'])->where('kind', 'courses|lessons')->middleware('role:super_admin');

        // 3d. Entitlements — Access gating & certificates
        Route::get('/entitlements/access', [EntitlementApiController::class, 'snapshot']);
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
        Route::post('/learning-materials/bulk-import', [LearningMaterialApiController::class, 'bulkImport'])->middleware(['auth:sanctum', 'role:super_admin']);
        Route::get('/learning-materials/bulk-import/template', [LearningMaterialApiController::class, 'bulkImportTemplate'])->middleware(['auth:sanctum', 'role:super_admin']);

        // 4. Placement — Write operations
        Route::post('/placements/impression', [PlacementApiController::class, 'logImpression']);
        Route::post('/placements/click', [PlacementApiController::class, 'logClick']);

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

    Route::get('/media-file', [PublicMediaController::class, 'query']);
    Route::get('/storage/{path}', [PublicMediaController::class, 'serve'])->where('path', '.*');
});
