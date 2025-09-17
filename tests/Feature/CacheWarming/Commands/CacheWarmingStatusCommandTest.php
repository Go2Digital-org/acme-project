<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

describe('Cache Warming Status Basic Tests', function (): void {
    it('can check cache status manually', function (): void {
        $cacheConfig = config('cache');

        expect($cacheConfig)->toBeArray()
            ->and($cacheConfig)->toHaveKey('default');

        Cache::put('cache_status', [
            'active' => true,
            'last_warmed' => now(),
            'items_count' => 100,
        ], 3600);

        $status = Cache::get('cache_status');
        expect($status)->toBeArray()
            ->and($status['active'])->toBeTrue()
            ->and($status['items_count'])->toBe(100);
    });

    it('can track cache performance metrics', function (): void {
        $performanceData = [
            'cache_hits' => 4750,
            'cache_misses' => 250,
        ];

        Cache::put('cache_performance', $performanceData, 3600);

        $metrics = Cache::get('cache_performance');
        expect($metrics['cache_hits'])->toBe(4750);
        expect($metrics['cache_misses'])->toBe(250);
    });

    it('handles cache operations gracefully', function (): void {
        Cache::put('test_key', 'test_value', 60);
        $value = Cache::get('test_key', 'default_value');

        expect($value)->toBe('test_value');
    });
});
