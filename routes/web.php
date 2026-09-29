<?php

use App\Livewire\ContentCms;
use App\Livewire\SponsorManager;
use App\Livewire\FeedManager;
use App\Livewire\BulkUpload;
use App\Livewire\QuizManager;
use App\Livewire\RuleConfigManager;
use App\Livewire\AnalyticsDashboard;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.auth.login')->name('home');

Route::get('/home', function () {
    $user = auth()->user();
    if ($user && $user->isSponsor()) {
        return redirect()->route('sponsor.dashboard');
    }
    return redirect()->route('dashboard');
})->middleware('auth')->name('home.redirect');

Route::middleware(['auth'])->group(function () {
    // 1. Dashboard & Modul Admin/Owner (Hanya Super Admin & Owner)
    Route::middleware(['role:super_admin,owner'])->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');

        Route::view('admin/sponsors', 'pages.admin.sponsors')->name('admin.sponsors');
        Route::view('admin/products', 'pages.admin.products')->name('admin.products');
        Route::view('admin/campaigns', 'pages.admin.campaigns')->name('admin.campaigns');
        Route::view('admin/events', 'pages.admin.events')->name('admin.events');
        Route::view('admin/best-deals', 'pages.admin.best-deals')->name('admin.best-deals');
        Route::view('admin/bulk-upload', 'pages.admin.bulk-upload')->name('admin.bulk-upload');
        Route::view('admin/notifications', 'pages.admin.notifications')->name('admin.notifications');
        Route::view('admin/users', 'pages.admin.users')->name('admin.users');
        Route::get('admin/content', ContentCms::class)->name('admin.content');
        Route::get('admin/feed', FeedManager::class)->name('admin.feed');
        Route::get('admin/quizzes', QuizManager::class)->name('admin.quizzes');
        Route::get('admin/rules', RuleConfigManager::class)->name('admin.rules');
        Route::get('admin/analytics', AnalyticsDashboard::class)->name('admin.analytics');
    });

    // 2. Modul Sponsor (Mitra Sponsor, Super Admin, & Owner)
    Route::middleware(['role:sponsor,super_admin,owner'])->group(function () {
        Route::view('sponsor/dashboard', 'pages.sponsor.dashboard')->name('sponsor.dashboard');
        Route::view('sponsor/products', 'pages.sponsor.products')->name('sponsor.products');
        Route::view('sponsor/campaigns/hero', 'pages.sponsor.hero-slider')->name('sponsor.campaigns.hero');
        Route::view('sponsor/campaigns/horizontal', 'pages.sponsor.horizontal-slider')->name('sponsor.campaigns.horizontal');
        Route::view('sponsor/campaigns/card', 'pages.sponsor.card-slider')->name('sponsor.campaigns.card');
        Route::view('sponsor/campaigns/popup', 'pages.sponsor.popup-ad')->name('sponsor.campaigns.popup');
        Route::view('sponsor/notifications', 'pages.sponsor.notifications')->name('sponsor.notifications');

        // Backward compatibility
        Route::get('sponsor/campaigns', function () {
            return redirect()->route('sponsor.campaigns.hero');
        })->name('sponsor.campaigns');
    });
});

require __DIR__.'/settings.php';
