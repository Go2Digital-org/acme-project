<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\ValueObject;

enum WarmingStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isFinished(): bool
    {
        if ($this->isCompleted()) {
            return true;
        }

        return $this->isFailed();
    }

    public function canTransitionTo(WarmingStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::IN_PROGRESS, self::FAILED]),
            self::IN_PROGRESS => in_array($newStatus, [self::COMPLETED, self::FAILED]),
            self::COMPLETED, self::FAILED => false,
        };
    }
}
