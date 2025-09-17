<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Repository;

use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Repository\CurrencyQueryRepositoryInterface;
use Modules\Currency\Domain\Service\CurrencyCacheInterface;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;
use stdClass;

/**
 * Cache-first currency repository with fallback chain:
 * 1. Request-level cache (fastest)
 * 2. Redis cache (cross-request)
 * 3. Database cache table
 * 4. Direct database query (fallback)
 */
final readonly class CurrencyQueryRepository implements CurrencyQueryRepositoryInterface
{
    use HasTenantAwareCache;

    public function __construct(
        private CurrencyCacheInterface $requestCache
    ) {}

    public function getActiveCurrencies(): Collection
    {
        // Check request-level cache first
        if ($this->requestCache->has()) {
            $cached = $this->requestCache->get();
            if ($cached instanceof Collection && $cached->isNotEmpty()) {
                return $cached->map(fn ($item) => $this->stdClassToCurrency($item));
            }
        }

        // Try cache chain
        $cacheData = $this->getFromCacheChain('system:active_currencies');
        if ($cacheData !== null) {
            $currencies = $this->processCachedData($cacheData);
            if ($currencies->isNotEmpty()) {
                // Store in request cache as stdClass for view compatibility
                $this->requestCache->set($currencies->map(fn ($currency) => $this->currencyToStdClass($currency)));

                return $currencies;
            }
        }

        // Fallback to direct database query with optimization
        return $this->getActiveCurrenciesFromDatabase();
    }

    public function findByCode(string $code): ?Currency
    {
        $currencies = $this->getActiveCurrencies();

        return $currencies->firstWhere('code', strtoupper($code));
    }

    public function getDefaultCurrency(): ?Currency
    {
        // Check static cache first (request-level)
        static $defaultCurrency = null;
        if ($defaultCurrency instanceof Currency) {
            return $defaultCurrency;
        }

        // Try cache chain
        $cacheData = $this->getFromCacheChain('system:active_currencies');
        if ($cacheData !== null) {
            $currencies = $this->processCachedData($cacheData);
            $defaultCurrency = $currencies->firstWhere('is_default', true);
            if ($defaultCurrency instanceof Currency) {
                return $defaultCurrency;
            }
        }

        // Fallback to direct query
        $defaultCurrency = Currency::default()->first();

        return $defaultCurrency;
    }

    public function getCurrenciesForView(): Collection
    {
        $currencies = $this->getActiveCurrencies();

        // Transform to optimized view format
        return $currencies->map(fn (Currency $currency): stdClass => $this->currencyToStdClass($currency))->values();
    }

    public function clearCache(): void
    {
        try {
            // Clear request cache
            $this->requestCache->clear();

            // Clear Redis cache if available
            $redisKey = $this->getTenantAwareCacheKey('system:active_currencies');
            if ($this->isRedisAvailable()) {
                Redis::del($redisKey);
            }

            // Clear database cache
            DB::table('application_cache')
                ->where('cache_key', 'system:active_currencies')
                ->delete();

            Log::info('Currency cache cleared successfully');
        } catch (Exception $e) {
            Log::error('Failed to clear currency cache', ['error' => $e->getMessage()]);
        }
    }

    public function warmCache(): void
    {
        try {
            // Force refresh from database
            $currencies = $this->getActiveCurrenciesFromDatabase();

            if ($currencies->isEmpty()) {
                Log::warning('No active currencies found for cache warming');

                return;
            }

            // Prepare cache data
            $cacheData = [
                'currencies' => $currencies->map(fn (Currency $currency) => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'flag' => $currency->flag,
                    'is_default' => $currency->is_default,
                    'is_active' => $currency->is_active,
                    'exchange_rate' => $currency->exchange_rate,
                    'sort_order' => $currency->sort_order,
                ])->toArray(),
                'cached_at' => now()->toISOString(),
                'version' => 1,
            ];

            // Store in Redis if available
            if ($this->isRedisAvailable()) {
                $redisKey = $this->getTenantAwareCacheKey('system:active_currencies');
                Redis::setex($redisKey, 3600, json_encode($cacheData)); // 1 hour
            }

            // Store in database cache
            DB::table('application_cache')->updateOrInsert(
                ['cache_key' => 'system:active_currencies'],
                [
                    'stats_data' => json_encode($cacheData),
                    'cache_status' => 'ready',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // Store in request cache
            $this->requestCache->set(
                collect($cacheData['currencies'])->map(fn (array $item) => (object) $item)
            );

            Log::info('Currency cache warmed successfully', ['count' => $currencies->count()]);
        } catch (Exception $e) {
            Log::error('Failed to warm currency cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get data from cache chain (Redis -> DB cache).
     *
     * @return array<string, mixed>|null
     */
    private function getFromCacheChain(string $cacheKey): ?array
    {
        // Try Redis first
        if ($this->isRedisAvailable()) {
            try {
                $redisKey = $this->getTenantAwareCacheKey($cacheKey);
                $cached = Redis::get($redisKey);
                if ($cached) {
                    $data = json_decode($cached, true);
                    if (is_array($data) && isset($data['currencies'])) {
                        return $data;
                    }
                }
            } catch (Exception) {
                // Redis failed, continue to next fallback
            }
        }

        // Fallback to database cache
        try {
            $cached = DB::table('application_cache')
                ->where('cache_key', $cacheKey)
                ->where('cache_status', 'ready')
                ->first();

            if ($cached && isset($cached->stats_data)) {
                $data = json_decode((string) $cached->stats_data, true);
                if (is_array($data) && isset($data['currencies'])) {
                    return $data;
                }
            }
        } catch (Exception) {
            // Database cache failed
        }

        return null;
    }

    /**
     * Process cached data and convert to Currency collection.
     *
     * @param  array<string, mixed>  $cacheData
     * @return Collection<int, Currency>
     */
    private function processCachedData(array $cacheData): Collection
    {
        if (! isset($cacheData['currencies']) || ! is_array($cacheData['currencies'])) {
            return collect();
        }

        return collect($cacheData['currencies'])
            ->map(function (array $item): Currency {
                $currency = new Currency;
                $currency->id = $item['id'] ?? null;
                $currency->code = $item['code'];
                $currency->name = $item['name'];
                $currency->symbol = $item['symbol'];
                $currency->flag = $item['flag'] ?? '💱';
                $currency->is_default = (bool) ($item['is_default'] ?? false);
                $currency->is_active = (bool) ($item['is_active'] ?? true);
                $currency->exchange_rate = (float) ($item['exchange_rate'] ?? 1.0);
                $currency->sort_order = (int) ($item['sort_order'] ?? 0);
                $currency->exists = true;

                return $currency;
            });
    }

    /**
     * Get active currencies directly from database with N+1 prevention.
     *
     * @return Collection<int, Currency>
     */
    private function getActiveCurrenciesFromDatabase(): Collection
    {
        try {
            /** @var EloquentCollection<int, Currency> $currencies */
            $currencies = Currency::active()
                ->select(['id', 'code', 'name', 'symbol', 'flag', 'is_default', 'is_active', 'exchange_rate', 'sort_order'])
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();

            // Auto-warm cache after successful database query
            if ($currencies->isNotEmpty()) {
                $this->warmCache();
            }

            return $currencies->collect();
        } catch (Exception $e) {
            Log::error('Failed to fetch currencies from database', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * Convert Currency model to stdClass for view compatibility.
     */
    private function currencyToStdClass(Currency $currency): stdClass
    {
        return (object) [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
            'flag' => $currency->flag,
            'is_default' => $currency->is_default,
            'is_active' => $currency->is_active,
            'exchange_rate' => $currency->exchange_rate,
            'sort_order' => $currency->sort_order,
        ];
    }

    /**
     * Convert stdClass back to Currency model.
     */
    private function stdClassToCurrency(stdClass $item): Currency
    {
        $currency = new Currency;
        $currency->id = $item->id ?? null;
        $currency->code = $item->code;
        $currency->name = $item->name;
        $currency->symbol = $item->symbol;
        $currency->flag = $item->flag ?? '💱';
        $currency->is_default = (bool) ($item->is_default ?? false);
        $currency->is_active = (bool) ($item->is_active ?? true);
        $currency->exchange_rate = (float) ($item->exchange_rate ?? 1.0);
        $currency->sort_order = (int) ($item->sort_order ?? 0);
        $currency->exists = true;

        return $currency;
    }

    /**
     * Check if Redis is available and configured.
     */
    private function isRedisAvailable(): bool
    {
        try {
            return config('database.redis.default.host') &&
                   app()->environment() !== 'testing' &&
                   Redis::ping();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get tenant-aware cache key.
     */
    private function getTenantAwareCacheKey(string $baseKey): string
    {
        return self::formatCacheKey($baseKey);
    }
}
