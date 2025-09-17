<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Warm dashboard cache every 5 minutes
Schedule::command('dashboard:warm-cache')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/dashboard-cache.log'));

// Refresh materialized views every 15 minutes
Schedule::command('dashboard:warm-cache --refresh-views')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/dashboard-cache.log'));

// Full cache refresh daily at 3 AM
Schedule::command('dashboard:warm-cache --clear --refresh-views')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/dashboard-cache.log'));

// Calculate all widget stats every 30 seconds for near real-time data
Schedule::command('widgets:calculate-stats')
    ->everyThirtySeconds()
    ->withoutOverlapping()
    ->runInBackground();

// Update currency exchange rates every hour
Schedule::command('currency:update-rates')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function (): void {
        Log::error('Failed to update exchange rates via scheduled task');
    })
    ->appendOutputTo(storage_path('logs/exchange-rates.log'));
