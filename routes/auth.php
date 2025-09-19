<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;
use Modules\Auth\Infrastructure\Laravel\Controllers\GoogleCallbackController;
use Modules\Auth\Infrastructure\Laravel\Controllers\GoogleRedirectController;

// Authentication Routes
Route::middleware('guest')->group(function (): void {
    // Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Registration
    if (Features::enabled(Features::registration())) {
        Route::get('/register', [RegisteredUserController::class, 'create'])
            ->name('register');
        Route::post('/register', [RegisteredUserController::class, 'store']);
    }

    // Password Reset
    if (Features::enabled(Features::resetPasswords())) {
        Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');
        Route::post('/reset-password', [NewPasswordController::class, 'store'])
            ->name('password.update');
    }

    // Google OAuth
    Route::get('/auth/google', GoogleRedirectController::class)->name('auth.google');
    Route::get('/auth/google/callback', GoogleCallbackController::class);
});

// Authenticated Routes
Route::middleware('auth')->group(function (): void {
    // Email Verification
    if (Features::enabled(Features::emailVerification())) {
        Route::get('/email/verify', EmailVerificationPromptController::class)
            ->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');
    }

    // Password Confirmation
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm.custom');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->name('password.confirm.store');

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

// Two-Factor Authentication
if (Features::enabled(Features::twoFactorAuthentication())) {
    Route::get('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
        ->middleware('guest')
        ->name('two-factor.login');
    Route::post('/two-factor-challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->middleware(['guest', 'throttle:two-factor']);
}
