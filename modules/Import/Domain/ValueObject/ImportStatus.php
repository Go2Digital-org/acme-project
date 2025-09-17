<?php

declare(strict_types=1);

namespace Modules\Import\Domain\ValueObject;

enum ImportStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public static function pending(): self
    {
        return self::PENDING;
    }

    public static function processing(): self
    {
        return self::PROCESSING;
    }

    public static function completed(): self
    {
        return self::COMPLETED;
    }

    public static function failed(): self
    {
        return self::FAILED;
    }

    public static function cancelled(): self
    {
        return self::CANCELLED;
    }

    public function isActive(): bool
    {
        return $this === self::PROCESSING;
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }
}
