<?php

use App\Http\Controllers\Api\CampaignApiController;
use App\Http\Controllers\Api\DemographicApiController;
use App\Http\Controllers\Api\EventApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\TrackerApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. Banners & Promosi Sponsor
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
