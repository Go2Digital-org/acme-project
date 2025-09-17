<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Auth\Infrastructure\Laravel\Controllers\DeleteAccountController;
use Modules\Auth\Infrastructure\Laravel\Controllers\DeleteProfilePhotoController;
use Modules\Auth\Infrastructure\Laravel\Controllers\DestroyOtherSessionsController;
use Modules\Auth\Infrastructure\Laravel\Controllers\DestroySessionController;
use Modules\Auth\Infrastructure\Laravel\Controllers\EditProfileController;
use Modules\Auth\Infrastructure\Laravel\Controllers\EnableTwoFactorController;
use Modules\Auth\Infrastructure\Laravel\Controllers\ShowProfileController;
use Modules\Auth\Infrastructure\Laravel\Controllers\ShowSecurityController;
use Modules\Auth\Infrastructure\Laravel\Controllers\ShowSessionsController;
use Modules\Auth\Infrastructure\Laravel\Controllers\UpdateAvatarController;
use Modules\Auth\Infrastructure\Laravel\Controllers\UpdatePasswordController;
use Modules\Auth\Infrastructure\Laravel\Controllers\UpdateProfileController;
use Modules\Auth\Infrastructure\Laravel\Controllers\UpdateProfileInformationController;
use Modules\Auth\Infrastructure\Laravel\Controllers\UploadProfilePhotoController;
use Modules\Auth\Infrastructure\Laravel\Controllers\Web\DisableTwoFactorController;
use Modules\Auth\Infrastructure\Laravel\Controllers\Web\GetRecoveryCodesController;
use Modules\Auth\Infrastructure\Laravel\Controllers\Web\RegenerateRecoveryCodesController;
use Modules\CacheWarming\Infrastructure\Laravel\Controller\CacheWarmingController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Admin\AdminForceDeleteCampaignController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\CreateCampaignWebController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\DeleteCampaignController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\ListCampaignsWebController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\MyCampaignsWebController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\RestoreCampaignController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\ShowCampaignWebController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\StoreCampaignController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\UpdateCampaignController;
use Modules\Campaign\Infrastructure\Laravel\Controllers\Web\UpdateCampaignWebController;
use Modules\Currency\Infrastructure\Laravel\Controllers\Web\ChangeCurrencyController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Api\DonationStatusApiController;
use Modules\Donation\Infrastructure\Laravel\Controllers\MollieWebhookController;
use Modules\Donation\Infrastructure\Laravel\Controllers\StripeWebhookController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\CancelDonationController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\CreateDonationWebController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\DonationCancelController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\DonationFailedController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\DonationProcessingController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\DonationSuccessController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\ListDonationsWebController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\ProcessDonationWebController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\ShowDonationWebController;
use Modules\Donation\Infrastructure\Laravel\Controllers\Web\StoreDonationController;
use Modules\Export\Infrastructure\Laravel\Controllers\CancelExportController;
use Modules\Export\Infrastructure\Laravel\Controllers\DeleteExportController;
use Modules\Export\Infrastructure\Laravel\Controllers\DownloadExportController;
use Modules\Export\Infrastructure\Laravel\Controllers\GetExportStatusController;
use Modules\Export\Infrastructure\Laravel\Controllers\ListUserExportsController;
use Modules\Export\Infrastructure\Laravel\Controllers\ManageExportsPageController;
use Modules\Export\Infrastructure\Laravel\Controllers\RequestDonationExportController;
use Modules\Export\Infrastructure\Laravel\Controllers\RetryExportController;
use Modules\Localization\Infrastructure\Laravel\Controllers\Web\LocaleSwitchController;
use Modules\Organization\Infrastructure\Laravel\Controllers\ImpersonateUserController;
use Modules\Shared\Infrastructure\Laravel\Controllers\ShowPageController;
use Modules\Shared\Infrastructure\Laravel\Controllers\Web\ClearAllNotificationsController;
use Modules\Shared\Infrastructure\Laravel\Controllers\Web\ListNotificationsController;
use Modules\Shared\Infrastructure\Laravel\Controllers\Web\MarkNotificationAsReadController as WebMarkNotificationAsReadController;
use Modules\Shared\Infrastructure\Laravel\Controllers\Web\ShowStyleGuideController;
use Modules\Shared\Infrastructure\Laravel\Controllers\Web\ShowWelcomePageController;
use Modules\User\Infrastructure\Laravel\Controllers\ShowUserProfileController;

// Cache warming route (outside localization)
Route::get('/cache-warming', CacheWarmingController::class)->name('cache.warming');

// Currency and Locale switching (outside localization)
Route::post('/currency/change', ChangeCurrencyController::class)->name('currency.change');

Route::get('/locale/{locale}', LocaleSwitchController::class)->name('locale.switch');

// User impersonation route (outside localization to avoid session path issues)
// Uses hexagonal architecture with proper controller/command pattern
Route::get('/impersonate/{token}', ImpersonateUserController::class)
    ->name('tenant.impersonate');

// Payment Webhooks (outside localization)
Route::post('/webhooks/mollie', MollieWebhookController::class)->name('webhooks.mollie');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

// Routes with localization
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function (): void {
    // Include Fortify authentication routes (now localized)
    require __DIR__ . '/auth.php';

    // Homepage route - works with /en/, /nl/, /fr/ prefixes
    Route::get('/', ShowWelcomePageController::class)->name('welcome');

    // Style Guide
    Route::get('/style-guide', ShowStyleGuideController::class)->name('style-guide');

    // Dashboard Route (requires authentication)
    Route::get('/dashboard', ShowUserProfileController::class)->name('dashboard')->middleware('auth');

    // Notification Routes (requires authentication)
    Route::middleware('auth')->group(function (): void {
        Route::get('/notifications', ListNotificationsController::class)->name('notifications.index');
        Route::post('/notifications/{id}/read', WebMarkNotificationAsReadController::class)->name('notifications.read');
        Route::post('/notifications/clear', ClearAllNotificationsController::class)->name('notifications.clear');
    });

    // Campaign Routes (web views)
    Route::get('/campaigns', ListCampaignsWebController::class)->name('campaigns.index');
    Route::get('/campaigns/my-campaigns', MyCampaignsWebController::class)->name('campaigns.my-campaigns')->middleware('auth');
    Route::get('/campaigns/create', CreateCampaignWebController::class)->name('campaigns.create')->middleware('auth');
    Route::post('/campaigns', StoreCampaignController::class)->name('campaigns.store')->middleware('auth');
    Route::get('/campaigns/search', ListCampaignsWebController::class)->name('campaigns.search');
    Route::get('/campaigns/{campaign}', ShowCampaignWebController::class)->name('campaigns.show');
    Route::get('/campaigns/{campaign}/donate', CreateDonationWebController::class)->name('campaigns.donate')->middleware('auth');
    Route::post('/campaigns/{campaign}/donations', ProcessDonationWebController::class)->name('campaigns.donations.store')->middleware('auth');
    Route::get('/campaigns/{campaign}/edit', UpdateCampaignWebController::class)->name('campaigns.edit')->middleware('auth');
    Route::put('/campaigns/{campaign}', UpdateCampaignController::class)->name('campaigns.update')->middleware('auth');
    Route::delete('/campaigns/{campaign}', DeleteCampaignController::class)->name('campaigns.destroy')->middleware('auth');
    Route::patch('/campaigns/{campaign}/restore', RestoreCampaignController::class)->name('campaigns.restore')->middleware('auth');

    // Admin Routes (requires super_admin role)
    Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function (): void {
        Route::delete('/campaigns/{campaign}/force-delete', AdminForceDeleteCampaignController::class)->name('campaigns.force-delete');
    });

    // Donation Routes (web views)
    Route::get('/donations', ListDonationsWebController::class)->name('donations.index')->middleware('auth');
    Route::get('/donations/create', CreateDonationWebController::class)->name('donations.create')->middleware('auth');
    Route::post('/donations', StoreDonationController::class)->name('donations.store')->middleware('auth');
    Route::get('/donations/{donation}', ShowDonationWebController::class)->name('donations.show')->middleware('auth');
    Route::post('/donations/{donation}/cancel', CancelDonationController::class)->name('donations.cancel')->middleware('auth');
    Route::get('/donations/{donation}/status', DonationStatusApiController::class)->name('donations.status')->middleware('auth');

    // Export Routes (Async with progress tracking)
    Route::middleware(['auth'])->group(function (): void {
        Route::post('/donations/export/request', RequestDonationExportController::class)->name('exports.request');
        Route::get('/exports/status/{exportId}', GetExportStatusController::class)->name('exports.status');
        Route::get('/exports/download/{exportId}', DownloadExportController::class)->name('exports.download');
        Route::post('/exports/cancel/{exportId}', CancelExportController::class)->name('exports.cancel');
        Route::post('/exports/retry/{exportId}', RetryExportController::class)->name('exports.retry');
        Route::delete('/exports/{exportId}', DeleteExportController::class)->name('exports.delete');
        Route::get('/exports/manage', ManageExportsPageController::class)->name('exports.manage');
        Route::get('/exports', ListUserExportsController::class)->name('exports.list');
    });

    // Payment Flow Routes
    Route::get('/donations/{donation}/success', DonationSuccessController::class)->name('donations.success');
    Route::get('/donations/{donation}/processing', DonationProcessingController::class)->name('donations.processing');
    Route::get('/donations/{donation}/cancelled', DonationCancelController::class)->name('donations.cancelled');
    Route::get('/donations/{donation}/failed', DonationFailedController::class)->name('donations.failed');

    // User Profile Routes
    Route::middleware('auth')->group(function (): void {
        Route::get('/profile', ShowProfileController::class)->name('profile.show');
        Route::get('/profile/edit', EditProfileController::class)->name('profile.edit');
        Route::patch('/profile', UpdateProfileController::class)->name('profile.update');
        Route::put('/profile/information', UpdateProfileInformationController::class)->name('profile.information.update');

        // Security Routes (integrated into profile tabs)
        Route::put('/profile/password', UpdatePasswordController::class)->name('profile.password.update');
        Route::post('/profile/two-factor/enable', EnableTwoFactorController::class)->name('profile.two-factor.enable');
        Route::delete('/profile/two-factor/disable', DisableTwoFactorController::class)->name('profile.two-factor.disable');
        Route::delete('/profile', DeleteAccountController::class)->name('profile.delete');

        // Session Routes
        Route::get('/profile/sessions', ShowSessionsController::class)->name('profile.sessions');
        Route::delete('/profile/sessions/{sessionId}', DestroySessionController::class)->name('profile.sessions.logout');
        Route::delete('/profile/sessions', DestroyOtherSessionsController::class)->name('profile.sessions.logout-others');

        // Profile Security Page
        Route::get('/profile/security', ShowSecurityController::class)->name('profile.security');

        // Profile Photo Management
        Route::post('/profile/avatar', UpdateAvatarController::class)->name('profile.avatar');
        Route::post('/profile/photo', UploadProfilePhotoController::class)->name('profile.upload-photo');
        Route::delete('/profile/photo', DeleteProfilePhotoController::class)->name('profile.remove-photo');

        // Two-factor recovery codes
        Route::get('/profile/two-factor/recovery-codes', GetRecoveryCodesController::class)->name('profile.two-factor.recovery-codes');

        Route::post('/profile/two-factor/recovery-codes', RegenerateRecoveryCodesController::class)->name('profile.two-factor.recovery-codes.regenerate');
    });

    // Dynamic page route - must be last to avoid conflicts
    Route::get('/page/{slug}', ShowPageController::class)->name('page')
        ->where('slug', '[a-zA-Z0-9\-_]+');
});
