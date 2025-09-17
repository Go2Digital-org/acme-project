<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\ValueObject;

use Carbon\Carbon;
use InvalidArgumentException;
use Stringable;

class TimeRange implements Stringable
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public string $label,
    ) {
        if ($this->start->isAfter($this->end)) {
            throw new InvalidArgumentException('Start date cannot be after end date');
        }
    }

    public static function today(): self
    {
        return new self(
            Carbon::today(),
            Carbon::today()->endOfDay(),
            'Today',
        );
    }

    public static function yesterday(): self
    {
        return new self(
            Carbon::yesterday(),
            Carbon::yesterday()->endOfDay(),
            'Yesterday',
        );
    }

    public static function thisWeek(): self
    {
        return new self(
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
            'This Week',
        );
    }

    public static function lastWeek(): self
    {
        return new self(
            Carbon::now()->subWeek()->startOfWeek(),
            Carbon::now()->subWeek()->endOfWeek(),
            'Last Week',
        );
    }

    public static function thisMonth(): self
    {
        return new self(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
            'This Month',
        );
    }

    public static function lastMonth(): self
    {
        return new self(
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
            'Last Month',
        );
    }

    public static function thisQuarter(): self
    {
        return new self(
            Carbon::now()->startOfQuarter(),
            Carbon::now()->endOfQuarter(),
            'This Quarter',
        );
    }

    public static function thisYear(): self
    {
        return new self(
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear(),
            'This Year',
        );
    }

    public static function lastYear(): self
    {
        return new self(
            Carbon::now()->subYear()->startOfYear(),
            Carbon::now()->subYear()->endOfYear(),
            'Last Year',
        );
    }

    public static function last30Days(): self
    {
        return new self(
            Carbon::now()->subDays(30),
            Carbon::now(),
            'Last 30 Days',
        );
    }

    public static function last90Days(): self
    {
        return new self(
            Carbon::now()->subDays(90),
            Carbon::now(),
            'Last 90 Days',
        );
    }

    public static function custom(Carbon $start, Carbon $end, string $label = 'Custom Range'): self
    {
        return new self($start, $end, $label);
    }

    public function getDurationInDays(): int
    {
        return (int) ($this->start->diffInDays($this->end) + 1);
    }

    public function contains(Carbon $date): bool
    {
        return $date->between($this->start, $this->end);
    }

    public function getCacheKey(): string
    {
        return sprintf(
            'range_%s_%s',
            $this->start->format('Y-m-d'),
            $this->end->format('Y-m-d'),
        );
    }

    public function __toString(): string
    {
        return sprintf(
            '%s (%s to %s)',
            $this->label,
            $this->start->format('M j, Y'),
            $this->end->format('M j, Y'),
        );
    }
}
