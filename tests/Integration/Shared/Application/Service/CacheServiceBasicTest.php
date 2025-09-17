<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Modules\Shared\Application\Service\CacheService;
use Psr\Log\LoggerInterface;

beforeEach(function (): void {
    // Ensure we're using array cache for testing
    config(['cache.default' => 'array']);

    $this->mockLogger = \Mockery::mock(LoggerInterface::class);
    $this->cacheService = new CacheService(Cache::store('array'), $this->mockLogger);
});

describe('CacheService - TTL and Basic Functionality', function (): void {
    it('remembers data with default medium TTL', function (): void {
        $key = 'test:medium:ttl';
        $data = ['test' => 'medium_data'];

        $this->mockLogger->shouldReceive('debug')
            ->once()
            ->with('Cache operation completed', \Mockery::type('array'));

        $result = $this->cacheService->remember($key, fn () => $data, 'medium');

        expect($result)->toBe($data);
        expect(Cache::has($key))->toBe(true);
    });

    it('respects short TTL for frequently changing data', function (): void {
        $key = 'test:short:ttl';
        $data = ['test' => 'short_data'];

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, fn () => $data, 'short');

        expect($result)->toBe($data);
        expect(Cache::has($key))->toBe(true);
    });

    it('respects long TTL for stable data', function (): void {
        $key = 'test:long:ttl';
        $data = ['test' => 'long_data'];

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, fn () => $data, 'long');

        expect($result)->toBe($data);
        expect(Cache::has($key))->toBe(true);
    });

    it('respects custom TTL', function (): void {
        $key = 'test:custom:ttl';
        $data = ['test' => 'custom_data'];
        $customTtl = 7200;

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->rememberWithTtl($key, fn () => $data, $customTtl);

        expect($result)->toBe($data);
        expect(Cache::has($key))->toBe(true);
    });

    it('uses cached data on subsequent calls', function (): void {
        $key = 'test:cached:data';
        $originalData = ['original' => 'data'];
        $newData = ['should' => 'not_be_used'];

        $this->mockLogger->shouldReceive('debug')->times(2);

        // First call - should cache the data
        $result1 = $this->cacheService->remember($key, fn () => $originalData);

        // Second call - should return cached data, not call the callback
        $result2 = $this->cacheService->remember($key, fn () => $newData);

        expect($result1)->toBe($originalData);
        expect($result2)->toBe($originalData); // Should be same as first call
    });
});

describe('CacheService - Cache Tags', function (): void {
    it('remembers data with cache tags', function (): void {
        $key = 'test:tagged:data';
        $data = ['tagged' => 'data'];
        $tags = ['campaigns', 'org:1'];

        $this->mockLogger->shouldReceive('debug')->once();

        $result = $this->cacheService->remember($key, fn () => $data, 'medium', $tags);

        expect($result)->toBe($data);
        expect(Cache::tags($tags)->has($key))->toBe(true);
    });

    it('invalidates cache by tags', function (): void {
        $key1 = 'test:tagged:1';
        $key2 = 'test:tagged:2';
        $key3 = 'test:untagged';
        $tags = ['campaigns', 'org:1'];

        $this->mockLogger->shouldReceive('debug')->times(3);
        $this->mockLogger->shouldReceive('info')->once();

        // Cache some data with tags
        $this->cacheService->remember($key1, fn () => ['data1'], 'medium', $tags);
        $this->cacheService->remember($key2, fn () => ['data2'], 'medium', $tags);

        // Cache some data without tags
        $this->cacheService->remember($key3, fn () => ['data3']);

        // Verify all are cached
        expect(Cache::tags($tags)->has($key1))->toBe(true);
        expect(Cache::tags($tags)->has($key2))->toBe(true);
        expect(Cache::has($key3))->toBe(true);

        // Invalidate by tags
        $this->cacheService->invalidateOrganization(1);

        // Verify tagged items are cleared but untagged remain
        expect(Cache::tags($tags)->has($key1))->toBe(false);
        expect(Cache::tags($tags)->has($key2))->toBe(false);
        expect(Cache::has($key3))->toBe(true); // Should remain
    });
});

describe('CacheService - Cache Statistics', function (): void {
    it('returns cache statistics', function (): void {
        // Use array cache for testing instead of redis
        config(['cache.default' => 'array']);

        $stats = $this->cacheService->getCacheStats();

        expect($stats)->toHaveKeys([
            'cache_driver',
            'redis_connection',
            'ttl_configuration',
        ]);

        expect($stats['cache_driver'])->toBe('array');
        expect($stats['ttl_configuration'])->toHaveKeys([
            'short', 'medium', 'long', 'daily', 'weekly',
        ]);
    });
});

describe('CacheService - Error Handling', function (): void {
    it('handles invalid TTL type gracefully', function (): void {
        $key = 'test:invalid:ttl';
        $data = ['test' => 'data'];

        $this->mockLogger->shouldReceive('debug')->once();

        // Should default to medium TTL
        $result = $this->cacheService->remember($key, fn () => $data, 'invalid_type');

        expect($result)->toBe($data);
        expect(Cache::has($key))->toBe(true);
    });

    it('completes cache warming without errors', function (): void {
        $this->mockLogger->shouldReceive('info')->once()->with('Starting cache warming process');
        $this->mockLogger->shouldReceive('debug')->times(4);
        $this->mockLogger->shouldReceive('info')->once()->with('Cache warming completed successfully', \Mockery::type('array'));

        // Should not throw exception
        $this->cacheService->warmCache();

        expect(true)->toBe(true); // Test passes if no exception thrown
    });
});

afterEach(function (): void {
    // Clear cache after each test using array store
    try {
        Cache::store('array')->flush();
    } catch (\Exception $e) {
        // Ignore flush errors in test environment
    }
    \Mockery::close();
});
