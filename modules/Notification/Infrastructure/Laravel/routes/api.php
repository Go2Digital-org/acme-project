<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Notification\Infrastructure\Laravel\Controllers\ClearAllNotificationsController;
use Modules\Notification\Infrastructure\Laravel\Controllers\ListNotificationsController;
use Modules\Notification\Infrastructure\Laravel\Controllers\MarkNotificationAsReadController;

/*
|--------------------------------------------------------------------------
| Notification API Routes
|--------------------------------------------------------------------------
|
| These routes handle notification-related API endpoints for the ACME Corp
| CSR platform, following hexagonal architecture patterns.
|
*/

Route::middleware(['auth:sanctum'])->prefix('api')->group(function (): void {
    Route::prefix('notifications')->group(function (): void {
        // Get user notifications with pagination and filters
        Route::get('/', ListNotificationsController::class)
            ->name('api.notifications.index');

        // Mark specific notification as read
        Route::patch('/{id}/read', MarkNotificationAsReadController::class)
            ->name('api.notifications.read')
            ->where('id', '[0-9a-f-]+');

        // Mark all notifications as read
        Route::patch('/mark-all-read', ClearAllNotificationsController::class)
            ->name('api.notifications.clear-all');
    });
});

// Real-time notification endpoints (for admin users)
Route::middleware(['auth:sanctum', 'role:super_admin,csr_admin,finance_admin'])->prefix('api/admin')->group(function (): void {
    Route::prefix('notifications')->group(function (): void {
        // Get notification analytics and metrics
        Route::get('/metrics', fn () =>
            // This would be handled by a dedicated controller
            response()->json(['message' => 'Metrics endpoint - to be implemented']))->name('api.admin.notifications.metrics');

        // Send custom notification to users
        Route::post('/send', fn () =>
            // This would be handled by a dedicated controller
            response()->json(['message' => 'Send notification endpoint - to be implemented']))->name('api.admin.notifications.send');
    });
});
