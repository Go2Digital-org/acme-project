<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use Modules\CacheWarming\Domain\ValueObject\WarmingStatus;

final readonly class CacheWarmingProgress
{
    public function __construct(
        public int $currentItem,
        public int $totalItems,
        public WarmingStatus $status,
        public DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $completedAt = null
    ) {
        if ($currentItem < 0) {
            throw new InvalidArgumentException('Current item cannot be negative');
        }

        if ($totalItems <= 0) {
            throw new InvalidArgumentException('Total items must be greater than zero');
        }

        if ($currentItem > $totalItems) {
            throw new InvalidArgumentException('Current item cannot exceed total items');
        }

        if ($status->isCompleted() && ! $completedAt instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('Completed status requires completedAt timestamp');
        }

        if (! $status->isFinished() && $completedAt instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('Only finished statuses can have completedAt timestamp');
        }

        if ($completedAt instanceof DateTimeImmutable && $completedAt < $startedAt) {
            throw new InvalidArgumentException('Completed time cannot be before started time');
        }
    }

    public function getPercentageComplete(): float
    {
        return ($this->currentItem / $this->totalItems) * 100;
    }

    public function isComplete(): bool
    {
        return $this->currentItem === $this->totalItems || $this->status->isCompleted();
    }

    public function getRemainingItems(): int
    {
        return max(0, $this->totalItems - $this->currentItem);
    }

    public function getDurationInSeconds(): ?int
    {
        if (! $this->completedAt instanceof DateTimeImmutable) {
            return null;
        }

        return $this->completedAt->getTimestamp() - $this->startedAt->getTimestamp();
    }

    public function getEstimatedTimeRemaining(): ?int
    {
        if ($this->currentItem === 0 || $this->isComplete()) {
            return null;
        }

        $elapsed = time() - $this->startedAt->getTimestamp();
        $avgTimePerItem = $elapsed / $this->currentItem;

        return (int) ($avgTimePerItem * $this->getRemainingItems());
    }

    public function withProgress(int $currentItem): self
    {
        $newStatus = $this->determineStatusFromProgress($currentItem);
        $completedAt = $newStatus->isCompleted() ? new DateTimeImmutable : null;

        return new self(
            currentItem: $currentItem,
            totalItems: $this->totalItems,
            status: $newStatus,
            startedAt: $this->startedAt,
            completedAt: $completedAt
        );
    }

    public function withStatus(WarmingStatus $status): self
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new InvalidArgumentException(
                "Cannot transition from {$this->status->value} to {$status->value}"
            );
        }

        $completedAt = $status->isFinished() ? new DateTimeImmutable : null;

        return new self(
            currentItem: $this->currentItem,
            totalItems: $this->totalItems,
            status: $status,
            startedAt: $this->startedAt,
            completedAt: $completedAt
        );
    }

    public function withFailure(): self
    {
        return $this->withStatus(WarmingStatus::FAILED);
    }

    public static function create(int $totalItems): self
    {
        return new self(
            currentItem: 0,
            totalItems: $totalItems,
            status: WarmingStatus::PENDING,
            startedAt: new DateTimeImmutable
        );
    }

    public static function start(int $totalItems): self
    {
        return new self(
            currentItem: 0,
            totalItems: $totalItems,
            status: WarmingStatus::IN_PROGRESS,
            startedAt: new DateTimeImmutable
        );
    }

    private function determineStatusFromProgress(int $currentItem): WarmingStatus
    {
        if ($this->status->isFailed()) {
            return WarmingStatus::FAILED;
        }

        if ($currentItem >= $this->totalItems) {
            return WarmingStatus::COMPLETED;
        }

        if ($currentItem > 0) {
            return WarmingStatus::IN_PROGRESS;
        }

        return WarmingStatus::PENDING;
    }
}
