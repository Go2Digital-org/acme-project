<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\ValueObject;

use Carbon\Carbon;

class TimeRemaining
{
    private readonly Carbon $currentDate;

    public function __construct(
        private readonly Carbon $endDate,
        ?Carbon $currentDate = null,
    ) {
        $this->currentDate = $currentDate ?? Carbon::now();
    }

    public function getDaysRemaining(): int
    {
        return (int) $this->currentDate->diffInDays($this->endDate, false);
    }

    public function getHoursRemaining(): int
    {
        return (int) $this->currentDate->diffInHours($this->endDate, false);
    }

    public function getMinutesRemaining(): int
    {
        return (int) $this->currentDate->diffInMinutes($this->endDate, false);
    }

    public function isExpired(): bool
    {
        return $this->currentDate->isAfter($this->endDate);
    }

    public function isExpiringSoon(int $daysThreshold = 7): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return $this->getDaysRemaining() <= $daysThreshold;
    }

    public function getTimeRemainingText(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        $days = $this->getDaysRemaining();

        if ($days === 0) {
            $hours = $this->getHoursRemaining();

            if ($hours === 0) {
                $minutes = $this->getMinutesRemaining();

                return $minutes . ' minute' . ($minutes !== 1 ? 's' : '') . ' remaining';
            }

            return $hours . ' hour' . ($hours !== 1 ? 's' : '') . ' remaining';
        }

        return $days . ' day' . ($days !== 1 ? 's' : '') . ' remaining';
    }

    public function getUrgencyLevel(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        $days = $this->getDaysRemaining();

        return match (true) {
            $days <= 1 => 'critical',
            $days <= 3 => 'urgent',
            $days <= 7 => 'warning',
            default => 'normal',
        };
    }

    public function getUrgencyColor(): string
    {
        return match ($this->getUrgencyLevel()) {
            'expired' => 'red',
            'critical' => 'red',
            'urgent' => 'orange',
            'warning' => 'yellow',
            'normal' => 'green',
            default => 'gray',
        };
    }

    /**
     * @param  object{end_date: Carbon|string|null}  $campaign
     */
    public static function fromCampaign(object $campaign, ?Carbon $currentDate = null): self
    {
        if ($campaign->end_date === null) {
            // Default to far future if no end date is set
            $endDate = Carbon::now()->addYears(100);

            return new self($endDate, $currentDate);
        }

        $endDate = $campaign->end_date instanceof Carbon
            ? $campaign->end_date
            : Carbon::parse($campaign->end_date);

        return new self($endDate, $currentDate);
    }
}
