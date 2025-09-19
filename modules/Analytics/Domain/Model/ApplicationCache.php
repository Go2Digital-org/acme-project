<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Model;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Modules\Shared\Infrastructure\Laravel\Traits\HasTenantAwareCache;

/**
 * @property string $cache_key
 * @property array<string, mixed> $stats_data
 * @property Carbon|null $calculated_at
 * @property int|null $calculation_time_ms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ApplicationCache extends Model
{
    use HasTenantAwareCache;

    protected $table = 'application_cache';

    protected $primaryKey = 'cache_key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'cache_key',
        'stats_data',
        'calculated_at',
        'calculation_time_ms',
    ];

    /**
     * Get stats for a specific widget
     *
     * @return array<string, mixed>
     */
    public static function getStats(string $widgetKey): array
    {
        try {
            // Try to get from Redis first with tenant-aware key (without widget prefix for now)
            $redisKey = self::formatCacheKey($widgetKey);
            $cached = Redis::get($redisKey);

            if ($cached) {
                $data = json_decode($cached, true);
                if (is_array($data)) {
                    return $data;
                }
            }

            // Fallback to database if Redis doesn't have the data
            $stats = self::find($widgetKey);

            return $stats && is_array($stats->stats_data) ? $stats->stats_data : [];
        } catch (Exception) {
            // Fallback to database on any error
            $stats = self::find($widgetKey);

            return $stats && is_array($stats->stats_data) ? $stats->stats_data : [];
        }
    }

    /**
     * Update stats for a widget
     *
     * @param  array<string, mixed>  $data
     */
    public static function updateStats(string $widgetKey, array $data, int $calculationTimeMs = 0): void
    {
        self::updateOrCreate(
            ['cache_key' => $widgetKey],
            [
                'stats_data' => $data,
                'calculated_at' => now(),
                'calculation_time_ms' => $calculationTimeMs,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stats_data' => 'array',
            'calculated_at' => 'datetime',
            'calculation_time_ms' => 'integer',
        ];
    }
}
