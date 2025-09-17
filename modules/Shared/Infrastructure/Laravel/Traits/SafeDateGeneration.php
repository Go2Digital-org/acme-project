<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Traits;

use DateTime;

/**
 * Trait SafeDateGeneration
 *
 * Provides DST-safe date generation methods for factories and seeders.
 * Prevents invalid dates during daylight saving time transitions.
 */
trait SafeDateGeneration
{
    /**
     * Generate a random DateTime between two dates, avoiding DST transition hours.
     * Sets time to noon (12:00) to avoid DST transition hours (typically 2-3 AM).
     *
     * @param  DateTime|string  $startDate  The start date (default: '-30 years')
     * @param  DateTime|string  $endDate  The end date (default: 'now')
     * @param  string|null  $timezone  The timezone (default: null uses app timezone)
     */
    protected function safeDateTimeBetween(
        DateTime|string $startDate = '-30 years',
        DateTime|string $endDate = 'now',
        ?string $timezone = null
    ): DateTime {
        $dateTime = fake()->dateTimeBetween($startDate, $endDate, $timezone);

        // Set to noon (12:00) to avoid DST transition hours
        $dateTime->setTime(12, 0, 0);

        return $dateTime;
    }

    /**
     * Generate a safe date for the created_at field.
     */
    protected function safeCreatedAt(
        DateTime|string $startDate = '-1 year',
        DateTime|string $endDate = 'now'
    ): DateTime {
        return $this->safeDateTimeBetween($startDate, $endDate);
    }

    /**
     * Generate a safe date for the updated_at field.
     * Must be after or equal to created_at.
     */
    protected function safeUpdatedAt(
        DateTime $createdAt,
        DateTime|string $endDate = 'now'
    ): DateTime {
        return $this->safeDateTimeBetween($createdAt, $endDate);
    }

    /**
     * Generate a safe date in the past.
     *
     * @param  string  $maxAge  How far back (default: '-1 year')
     * @param  string  $minAge  How recent (default: '-1 day')
     */
    protected function safePastDate(
        string $maxAge = '-1 year',
        string $minAge = '-1 day'
    ): DateTime {
        return $this->safeDateTimeBetween($maxAge, $minAge);
    }

    /**
     * Generate a safe date in the future.
     *
     * @param  string  $minFuture  How soon (default: '+1 day')
     * @param  string  $maxFuture  How far (default: '+1 year')
     */
    protected function safeFutureDate(
        string $minFuture = '+1 day',
        string $maxFuture = '+1 year'
    ): DateTime {
        return $this->safeDateTimeBetween($minFuture, $maxFuture);
    }

    /**
     * Generate a safe date range (start and end dates).
     *
     * @param  int  $minDurationDays  Minimum duration in days
     * @param  int  $maxDurationDays  Maximum duration in days
     * @return array{start: DateTime, end: DateTime}
     */
    protected function safeDateRange(
        DateTime|string $earliestStart = '-6 months',
        DateTime|string $latestEnd = '+6 months',
        int $minDurationDays = 7,
        int $maxDurationDays = 90
    ): array {
        $start = $this->safeDateTimeBetween($earliestStart, $latestEnd);

        $durationDays = fake()->numberBetween($minDurationDays, $maxDurationDays);
        $end = clone $start;
        $end->modify("+{$durationDays} days");
        $end->setTime(12, 0, 0); // Ensure end date is also DST-safe

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Check if a DateTime falls within DST transition hours.
     * Useful for validation and debugging.
     */
    protected function isDstTransitionHour(DateTime $dateTime): bool
    {
        $hour = (int) $dateTime->format('H');

        // DST transitions typically occur between 2 AM and 3 AM
        return $hour >= 2 && $hour <= 3;
    }
}
