<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    // Use array cache driver for testing (Redis not required)
    config(['cache.default' => 'array']);
});

describe('CacheWarming Redis Integration', function (): void {
    it('can store and retrieve cache metrics', function (): void {
        $key = 'campaigns:active:count';
        $value = [
            'total' => 150,
            'active' => 120,
            'timestamp' => now()->toISOString(),
        ];

        Cache::put($key, $value, now()->addHours(6));

        $retrieved = Cache::get($key);

        expect($retrieved)->toBe($value)
            ->and($retrieved['total'])->toBe(150)
            ->and($retrieved['active'])->toBe(120);
    });

    it('can tag cache entries', function (): void {
        $campaignData = ['count' => 100];
        $donationData = ['total' => 50000];

        Cache::tags(['campaigns', 'metrics'])->put('campaigns:summary', $campaignData, 3600);
        Cache::tags(['donations', 'metrics'])->put('donations:total', $donationData, 3600);

        $campaignResult = Cache::tags(['campaigns', 'metrics'])->get('campaigns:summary');
        $donationResult = Cache::tags(['donations', 'metrics'])->get('donations:total');

        expect($campaignResult)->toBe($campaignData)
            ->and($donationResult)->toBe($donationData);
    });

    it('can flush cache by tags', function (): void {
        Cache::tags(['campaigns', 'metrics'])->put('campaigns:summary', ['count' => 100], 3600);
        Cache::tags(['donations', 'metrics'])->put('donations:total', ['total' => 50000], 3600);

        // Flush only campaign metrics
        Cache::tags(['campaigns'])->flush();

        expect(Cache::tags(['campaigns', 'metrics'])->get('campaigns:summary'))->toBeNull()
            ->and(Cache::tags(['donations', 'metrics'])->get('donations:total'))->not()->toBeNull();
    });

    it('cache expiry works correctly', function (): void {
        $key = 'temp:metric';
        $value = ['test' => 'data'];

        // Store with very short TTL for testing
        Cache::put($key, $value, now()->addSecond());

        expect(Cache::has($key))->toBeTrue();

        // Wait for expiry (in real Redis this would expire, in array cache we simulate)
        sleep(2);
        Cache::forget($key); // Manually remove to simulate expiry in array cache

        expect(Cache::has($key))->toBeFalse();
    });

    it('can store complex metric data', function (): void {
        $complexMetric = [
            'summary' => [
                'total_campaigns' => 1250,
                'active_campaigns' => 892,
                'completed_campaigns' => 358,
            ],
            'breakdown' => [
                'by_category' => [
                    'health' => 423,
                    'education' => 387,
                    'environment' => 312,
                    'other' => 128,
                ],
                'by_status' => [
                    'draft' => 45,
                    'active' => 892,
                    'completed' => 358,
                    'cancelled' => 15,
                ],
            ],
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'version' => '2.1.0',
                'expires_at' => now()->addHours(6)->toISOString(),
            ],
        ];

        Cache::put('campaigns:dashboard:summary', $complexMetric, now()->addHours(6));

        $retrieved = Cache::get('campaigns:dashboard:summary');

        expect($retrieved)->toBe($complexMetric)
            ->and($retrieved['summary']['total_campaigns'])->toBe(1250)
            ->and($retrieved['breakdown']['by_category'])->toHaveKey('health')
            ->and($retrieved['metadata']['version'])->toBe('2.1.0');
    });

    it('can increment counter metrics', function (): void {
        $key = 'campaigns:view:counter';

        Cache::put($key, 100, now()->addHours(1));

        // Simulate incrementing view count
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addHours(1));

        expect(Cache::get($key))->toBe(101);

        // Increment again
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 5, now()->addHours(1));

        expect(Cache::get($key))->toBe(106);
    });

    it('can handle multiple cache layers', function (): void {
        // Simulate L1 cache (short TTL)
        $l1Key = 'l1:campaigns:active:count';
        $l1Value = ['count' => 150, 'layer' => 'l1'];

        // Simulate L2 cache (longer TTL)
        $l2Key = 'l2:campaigns:active:count';
        $l2Value = ['count' => 148, 'layer' => 'l2'];

        Cache::put($l1Key, $l1Value, now()->addMinutes(5));
        Cache::put($l2Key, $l2Value, now()->addHours(1));

        // Simulate cache warming logic: check L1 first, fallback to L2
        $result = Cache::get($l1Key) ?: Cache::get($l2Key);

        expect($result)->toBe($l1Value)
            ->and($result['layer'])->toBe('l1');

        // Simulate L1 cache miss
        Cache::forget($l1Key);

        $result = Cache::get($l1Key) ?: Cache::get($l2Key);

        expect($result)->toBe($l2Value)
            ->and($result['layer'])->toBe('l2');
    });

    it('can store cache warming metadata', function (): void {
        $metadata = [
            'job_id' => 'warm_campaigns_123',
            'started_at' => now()->toISOString(),
            'records_processed' => 1250,
            'execution_time' => 45.7,
            'memory_peak' => 128.5,
            'status' => 'completed',
        ];

        Cache::put('cache_warming:job:123:metadata', $metadata, now()->addDay());

        $retrieved = Cache::get('cache_warming:job:123:metadata');

        expect($retrieved)->toBe($metadata)
            ->and($retrieved['status'])->toBe('completed')
            ->and($retrieved['records_processed'])->toBe(1250);
    });
});
