<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\ValueObject;

enum MetricType: string
{
    case HIT = 'hit';
    case MISS = 'miss';
    case EVICTION = 'eviction';
    case SET = 'set';
    case DELETE = 'delete';
    case EXPIRE = 'expire';
    case SIZE = 'size';
    case PERFORMANCE = 'performance';
    case WARMING = 'warming';

    public function isHit(): bool
    {
        return $this === self::HIT;
    }

    public function isMiss(): bool
    {
        return $this === self::MISS;
    }

    public function isEviction(): bool
    {
        return $this === self::EVICTION;
    }

    public function isSet(): bool
    {
        return $this === self::SET;
    }

    public function isDelete(): bool
    {
        return $this === self::DELETE;
    }

    public function isExpire(): bool
    {
        return $this === self::EXPIRE;
    }

    public function isSize(): bool
    {
        return $this === self::SIZE;
    }

    public function isPerformance(): bool
    {
        return $this === self::PERFORMANCE;
    }

    public function isWarming(): bool
    {
        return $this === self::WARMING;
    }

    public function isRead(): bool
    {
        if ($this->isHit()) {
            return true;
        }

        return $this->isMiss();
    }

    public function isWrite(): bool
    {
        if ($this->isSet()) {
            return true;
        }

        return $this->isDelete();
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::HIT => 'Cache Hit',
            self::MISS => 'Cache Miss',
            self::EVICTION => 'Cache Eviction',
            self::SET => 'Cache Set',
            self::DELETE => 'Cache Delete',
            self::EXPIRE => 'Cache Expire',
            self::SIZE => 'Cache Size',
            self::PERFORMANCE => 'Performance Metric',
            self::WARMING => 'Cache Warming',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::HIT => 'Successful cache retrieval',
            self::MISS => 'Cache key not found, fallback to data source',
            self::EVICTION => 'Cache entry removed due to memory constraints',
            self::SET => 'Cache entry stored or updated',
            self::DELETE => 'Cache entry manually removed',
            self::EXPIRE => 'Cache entry removed due to TTL expiration',
            self::SIZE => 'Cache size and memory usage metrics',
            self::PERFORMANCE => 'Cache operation performance metrics',
            self::WARMING => 'Proactive cache population metrics',
        };
    }
}
