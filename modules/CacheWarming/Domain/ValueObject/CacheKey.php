<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\ValueObject;

use InvalidArgumentException;

final readonly class CacheKey
{
    private const WIDGET_KEYS = [
        // Analytics widgets - 13 working implementations with meaningful data
        'average_donation',
        'campaign_performance',
        'comparative_metrics',
        'conversion_funnel',
        'donation_methods',
        'employee_participation',
        'goal_completion',
        'optimized_campaign_stats',
        'payment_analytics',
        'realtime_stats',
        'revenue_summary',
        'success_rate',
        'total_donations',
    ];

    private const SYSTEM_KEYS = [
        // Only include keys that have working implementations
        'page:home',
        'system:active_currencies',
        'system:footer_pages',
        'system:campaigns_list',
        // Campaign pagination pages - warm first 5 pages
        'campaigns:page:1',
        'campaigns:page:2',
        'campaigns:page:3',
        'campaigns:page:4',
        'campaigns:page:5',
    ];

    private const USER_KEYS = [
        'user:{userId}:statistics',
        'user:{userId}:activity_feed',
        'user:{userId}:impact_metrics',
        'user:{userId}:ranking',
        'user:{userId}:leaderboard',
    ];

    public function __construct(
        public string $key
    ) {
        if ($key === '' || $key === '0') {
            throw new InvalidArgumentException('Cache key cannot be empty');
        }

        if (! $this->isValidKey($key)) {
            throw new InvalidArgumentException("Invalid cache key: {$key}");
        }
    }

    public function isWidgetKey(): bool
    {
        return in_array($this->key, self::WIDGET_KEYS);
    }

    public function isSystemKey(): bool
    {
        return in_array($this->key, self::SYSTEM_KEYS);
    }

    public function isUserKey(): bool
    {
        return $this->matchesUserKeyPattern();
    }

    public function getType(): string
    {
        if ($this->isWidgetKey()) {
            return 'widget';
        }

        if ($this->isSystemKey()) {
            return 'system';
        }

        if ($this->isUserKey()) {
            return 'user';
        }

        return 'unknown';
    }

    public function getUserId(): ?int
    {
        if (! $this->isUserKey()) {
            return null;
        }

        if (preg_match('/^user:(\d+):/', $this->key, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function toString(): string
    {
        return $this->key;
    }

    public function equals(CacheKey $other): bool
    {
        return $this->key === $other->key;
    }

    /**
     * @return array<string>
     */
    public static function getAllValidKeys(): array
    {
        return array_merge(self::WIDGET_KEYS, self::SYSTEM_KEYS, self::getUserKeyPatterns());
    }

    /**
     * @return array<string>
     */
    public static function getWidgetKeys(): array
    {
        return self::WIDGET_KEYS;
    }

    /**
     * @return array<string>
     */
    public static function getSystemKeys(): array
    {
        return self::SYSTEM_KEYS;
    }

    /**
     * @return array<string>
     */
    public static function getUserKeyPatterns(): array
    {
        return self::USER_KEYS;
    }

    private function isValidKey(string $key): bool
    {
        // Check if it's a widget or system key
        if (in_array($key, self::WIDGET_KEYS) || in_array($key, self::SYSTEM_KEYS)) {
            return true;
        }

        // Check if it matches user key pattern
        return $this->matchesUserKeyPattern();
    }

    private function matchesUserKeyPattern(): bool
    {
        foreach (self::USER_KEYS as $pattern) {
            $regex = str_replace('{userId}', '\d+', preg_quote($pattern, '/'));
            if (preg_match("/^{$regex}$/", $this->key)) {
                return true;
            }
        }

        return false;
    }
}
