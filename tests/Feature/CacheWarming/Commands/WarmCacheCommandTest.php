<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
});

describe('Warm Cache Command Basic Tests', function (): void {
    it('validates cache functionality', function (): void {
        Cache::put('test_key', 'test_value', 60);
        expect(Cache::get('test_key'))->toBe('test_value');

        Cache::flush();
        expect(Cache::get('test_key'))->toBeNull();
    });

    it('can cache complex data structures', function (): void {
        $appData = [
            'version' => '1.0.0',
            'features' => ['campaigns', 'donations'],
            'statistics' => [
                'total_campaigns' => 150,
                'total_donations' => 75000.50,
            ],
        ];

        Cache::put('app_config', $appData, 3600);
        $retrieved = Cache::get('app_config');

        expect($retrieved)->toEqual($appData);
        expect($retrieved['statistics']['total_campaigns'])->toBe(150);
    });

    it('handles cache TTL settings', function (): void {
        Cache::put('short_ttl_test', 'short_value', 60);
        Cache::put('long_ttl_test', 'long_value', 3600);

        expect(Cache::has('short_ttl_test'))->toBeTrue();
        expect(Cache::has('long_ttl_test'))->toBeTrue();
        expect(Cache::get('short_ttl_test'))->toBe('short_value');
        expect(Cache::get('long_ttl_test'))->toBe('long_value');
    });

    it('provides fallback when cache keys are missing', function (): void {
        $fallbackValue = 'fallback_data';
        $cachedValue = Cache::get('non_existent_key', $fallbackValue);

        expect($cachedValue)->toBe($fallbackValue);
    });
});
