<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Model;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Currency\Infrastructure\Laravel\Factory\CurrencyFactory;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

/**
 * Currency Eloquent Model.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string $flag
 * @property int $decimal_places
 * @property string $decimal_separator
 * @property string $thousands_separator
 * @property string $symbol_position
 * @property bool $is_active
 * @property bool $is_default
 * @property float $exchange_rate
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Currency active()
 * @method static Builder|Currency default()
 */
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    use HasTenantAwareCache;
    use SoftDeletes;

    protected $table = 'currencies';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'flag',
        'decimal_places',
        'decimal_separator',
        'thousands_separator',
        'symbol_position',
        'is_active',
        'is_default',
        'exchange_rate',
        'sort_order',
    ];

    /**
     * Scope a query to only include active currencies.
     *
     * @param  Builder<Currency>  $query
     * @return Builder<Currency>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to get the default currency.
     *
     * @param  Builder<Currency>  $query
     * @return Builder<Currency>
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Format an amount in this currency.
     */
    public function formatAmount(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->decimal_places,
            $this->decimal_separator,
            $this->thousands_separator,
        );

        if ($this->symbol_position === 'before') {
            return $this->symbol . $formatted;
        }

        return $formatted . ' ' . $this->symbol;
    }

    /**
     * Convert amount from this currency to another.
     */
    public function convertTo(float $amount, self $targetCurrency): float
    {
        // Convert to base currency (EUR) first
        $baseAmount = $amount / $this->exchange_rate;

        // Then convert to target currency
        return $baseAmount * $targetCurrency->exchange_rate;
    }

    /**
     * Get display name with flag.
     */
    public function getDisplayName(): string
    {
        return $this->flag . ' ' . $this->code . ' - ' . $this->name;
    }

    /**
     * Check if this is the default currency.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Check if this currency is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Convert the model to a Value Object for compatibility.
     */
    public function toValueObject(): \Modules\Currency\Domain\ValueObject\Currency
    {
        return \Modules\Currency\Domain\ValueObject\Currency::fromString($this->code);
    }

    /**
     * Get all active currencies ordered by sort order.
     */
    /**
     * @return Collection<int, Currency>
     */
    public static function getActiveCurrencies(): Collection
    {
        return static::active()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    /**
     * Cache for request-level caching of default currency
     */
    private static ?self $defaultCurrencyCache = null;

    /**
     * Get the default currency with caching.
     */
    public static function getDefaultCurrency(): ?self
    {
        // Return cached instance if already loaded in this request
        if (self::$defaultCurrencyCache instanceof \Modules\Currency\Domain\Model\Currency) {
            return self::$defaultCurrencyCache;
        }

        try {
            // Try to get from Redis first with tenant-aware key, but only if Redis is available
            $redisKey = self::formatCacheKey('system:active_currencies');
            $cached = null;

            // Check if Redis is configured and available
            if (config('database.redis.default.host') && app()->environment() !== 'testing') {
                try {
                    $cached = Redis::get($redisKey);
                } catch (Exception) {
                    // Redis connection failed, continue to database fallback
                    $cached = null;
                }
            }

            if ($cached) {
                $data = json_decode($cached, true);
                // Extract currencies from the nested structure
                $currencies = $data['currencies'] ?? $data;
                if (is_array($currencies)) {
                    $defaultData = collect($currencies)->firstWhere('is_default', true);

                    if ($defaultData) {
                        // Create a Currency instance from cached data
                        $currency = new self;
                        $currency->id = $defaultData['id'] ?? null;
                        $currency->code = $defaultData['code'];
                        $currency->name = $defaultData['name'];
                        $currency->symbol = $defaultData['symbol'];
                        $currency->is_default = $defaultData['is_default'];
                        $currency->exchange_rate = $defaultData['rate'] ?? 1.0;
                        $currency->exists = true;

                        // Cache for this request
                        self::$defaultCurrencyCache = $currency;

                        return self::$defaultCurrencyCache;
                    }
                }
            }

            // Fallback to database cache table
            $cached = DB::table('application_cache')
                ->where('cache_key', 'system:active_currencies')
                ->where('cache_status', 'ready')
                ->first();

            if ($cached && isset($cached->stats_data)) {
                $data = json_decode((string) $cached->stats_data, true);
                // Extract currencies from the nested structure
                $currencies = $data['currencies'] ?? $data;
                /** @var array<string, mixed> $currenciesArray */
                $currenciesArray = is_array($currencies) ? $currencies : [];
                $defaultData = collect($currenciesArray)->firstWhere('is_default', true);

                if ($defaultData) {
                    // Create a Currency instance from cached data
                    $currency = new self;
                    $currency->id = $defaultData['id'] ?? null;
                    $currency->code = $defaultData['code'];
                    $currency->name = $defaultData['name'];
                    $currency->symbol = $defaultData['symbol'];
                    $currency->is_default = $defaultData['is_default'];
                    $currency->exchange_rate = $defaultData['rate'] ?? 1.0;
                    $currency->exists = true;

                    // Cache for this request
                    self::$defaultCurrencyCache = $currency;

                    return self::$defaultCurrencyCache;
                }
            }

            // Final fallback to direct query
            self::$defaultCurrencyCache = static::default()->first();

            return self::$defaultCurrencyCache;
        } catch (Exception) {
            // Fallback to direct query on any error
            self::$defaultCurrencyCache = static::default()->first();

            return self::$defaultCurrencyCache;
        }
    }

    /**
     * Find currency by code.
     */
    public static function findByCode(string $code): ?self
    {
        $upperCode = strtoupper($code);

        return static::where('code', $upperCode)->first();
    }

    /**
     * Get payment gateways that support this currency.
     *
     * @return BelongsToMany<PaymentGateway, $this>
     */
    public function paymentGateways(): BelongsToMany
    {
        return $this->belongsToMany(
            PaymentGateway::class,
            'currency_payment_gateway',
            'currency_id',
            'payment_gateway_id'
        )->withTimestamps()
            ->withPivot(['min_amount', 'max_amount', 'transaction_fee', 'is_active']);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'decimal_places' => 'integer',
            'exchange_rate' => 'float',
            'sort_order' => 'integer',
        ];
    }
}
