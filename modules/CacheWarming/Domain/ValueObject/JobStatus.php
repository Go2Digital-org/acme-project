<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\ValueObject;

enum JobStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isRunning(): bool
    {
        return $this === self::RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isFinished(): bool
    {
        if ($this->isCompleted()) {
            return true;
        }
        if ($this->isFailed()) {
            return true;
        }

        return $this->isCancelled();
    }

    public function canTransitionTo(JobStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::RUNNING, self::FAILED, self::CANCELLED]),
            self::RUNNING => in_array($newStatus, [self::COMPLETED, self::FAILED, self::CANCELLED]),
            self::COMPLETED, self::FAILED, self::CANCELLED => false,
        };
    }
}
