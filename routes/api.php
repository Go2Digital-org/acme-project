<?php

declare(strict_types=1);

/**
 * API Platform Routes
 * ===================.
 *
 * This project uses API Platform for all API endpoints.
 * Routes are defined via ApiResource attributes on resource classes.
 *
 * API Platform automatically generates endpoints based on:
 * - modules/star/Infrastructure/ApiPlatform/Resource/starResource.php
 * - Operations defined in ApiResource attributes (GET, POST, PUT, DELETE, PATCH)
 * - Processors handle business logic in modules/star/Infrastructure/ApiPlatform/Handler/Processor/
 *
 * Available API Endpoints:
 * - Authentication: /api/auth/* (via AuthenticationResource)
 * - Campaign: /api/campaigns (via CampaignResource)
 * - Donation: /api/donations (via DonationResource)
 * - Organization: /api/organizations (via OrganizationResource)
 * - User: /api/users (via UserResource)
 * - Bookmarks: /api/campaigns/bookmark (via BookmarkResource)
 * - Dashboard: /api/dashboard/* (via DashboardResource)
 * - Exports: /api/exports (via ExportResource)
 * - Cache Status: /api/cache-status (via CacheStatusResource)
 *
 * Authentication:
 * - Bearer token via Sanctum
 * - Include header: Authorization: Bearer {token}
 * - Middleware configured in ApiResource attributes
 *
 * Additional API Routes:
 * - Search: /api/v1/search/* (via Shared module traditional routing)
 * - Notifications: /api/notifications/* (via Notification module traditional routing)
 * - Currencies: /api/v1/currencies/* (via Currency module traditional routing)
 */

use Illuminate\Support\Facades\Route;
use Modules\Auth\Infrastructure\Laravel\Controllers\Api\AuthController;

/**
 * Authentication Routes (Override API Platform for better middleware control)
 */
Route::prefix('auth')->group(function () {
    // Public authentication routes (no auth required)
    Route::middleware(['api.locale', 'throttle:api', 'api.performance'])->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    });

    // Protected authentication routes (auth required)
    Route::middleware(['auth:sanctum', 'api.locale', 'throttle:api', 'api.performance'])->group(function () {
        Route::get('user', [AuthController::class, 'user'])->name('api.auth.user');
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });
});

// All other API endpoints are handled by:
// 1. API Platform - See modules/*/Infrastructure/ApiPlatform/Resource/*Resource.php
// 2. Module-specific routes - See modules/*/Infrastructure/Laravel/routes/api.php
