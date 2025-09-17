<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Specification;

use Carbon\Carbon;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a campaign is within its active period.
 *
 * A campaign is within its active period when:
 * - Start date has passed (campaign has started)
 * - End date has not been reached (campaign has not ended)
 * - Dates are valid and logically consistent
 * - Campaign is not in a paused or suspended state
 */
final class IsWithinCampaignPeriodSpecification extends CompositeSpecification
{
    private string $reason = '';

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof Campaign) {
            $this->reason = 'Invalid campaign provided.';

            return false;
        }

        // Campaign must have valid start and end dates
        if (! $this->hasValidDates($candidate)) {
            return false;
        }

        $now = now();

        // Campaign must have started
        if ($candidate->start_date && $candidate->start_date->isFuture()) {
            $this->reason = sprintf(
                'Campaign has not started yet. Start date: %s (in %s).',
                $candidate->start_date->format('Y-m-d H:i:s'),
                $candidate->start_date->diffForHumans($now)
            );

            return false;
        }

        // Campaign must not have ended
        if ($candidate->end_date && $candidate->end_date->isPast()) {
            $this->reason = sprintf(
                'Campaign has ended. End date: %s (%s).',
                $candidate->end_date->format('Y-m-d H:i:s'),
                $candidate->end_date->diffForHumans($now)
            );

            return false;
        }

        // Additional business rule: campaign must be actively running
        if (! $this->isActivelyRunning($candidate)) {
            return false;
        }

        $this->reason = '';

        return true;
    }

    /**
     * Get the reason why the specification was not satisfied.
     */
    public function reason(): string
    {
        return $this->reason;
    }

    /**
     * Check if campaign has valid start and end dates.
     */
    private function hasValidDates(Campaign $campaign): bool
    {
        // Both start and end dates are required
        if (! $campaign->start_date || ! $campaign->end_date) {
            $this->reason = 'Campaign must have both start and end dates.';

            return false;
        }

        // Start date must be before end date
        if ($campaign->start_date->isAfter($campaign->end_date)) {
            $this->reason = sprintf(
                'Campaign start date (%s) must be before end date (%s).',
                $campaign->start_date->format('Y-m-d H:i:s'),
                $campaign->end_date->format('Y-m-d H:i:s')
            );

            return false;
        }

        // Campaign duration must be reasonable (at least 1 hour, max 2 years)
        $durationHours = $campaign->start_date->diffInHours($campaign->end_date);

        if ($durationHours < 1) {
            $this->reason = 'Campaign duration must be at least 1 hour.';

            return false;
        }

        if ($durationHours > (2 * 365 * 24)) { // 2 years in hours
            $this->reason = 'Campaign duration cannot exceed 2 years.';

            return false;
        }

        // Start date cannot be too far in the past (e.g., more than 1 year ago)
        if ($campaign->start_date->isBefore(now()->subYear())) {
            $this->reason = 'Campaign start date cannot be more than 1 year in the past.';

            return false;
        }

        // End date cannot be too far in the future (e.g., more than 2 years from now)
        if ($campaign->end_date->isAfter(now()->addYears(2))) {
            $this->reason = 'Campaign end date cannot be more than 2 years in the future.';

            return false;
        }

        return true;
    }

    /**
     * Check if campaign is actively running (not paused or suspended).
     */
    private function isActivelyRunning(Campaign $campaign): bool
    {
        // Check if campaign status allows it to be active
        if (! $campaign->status->isActive()) {
            $this->reason = sprintf(
                'Campaign is not in an active status. Current status: %s.',
                $campaign->status->value
            );

            return false;
        }

        // Check for any suspension or pause metadata
        $metadata = $campaign->metadata ?? [];

        if (isset($metadata['is_paused']) && $metadata['is_paused'] === true) {
            $pauseReason = $metadata['pause_reason'] ?? 'No reason provided';
            $this->reason = sprintf('Campaign is paused. Reason: %s.', $pauseReason);

            return false;
        }

        if (isset($metadata['is_suspended']) && $metadata['is_suspended'] === true) {
            $suspensionReason = $metadata['suspension_reason'] ?? 'No reason provided';
            $this->reason = sprintf('Campaign is suspended. Reason: %s.', $suspensionReason);

            return false;
        }

        // Check if campaign is temporarily blocked
        if (isset($metadata['blocked_until']) && $metadata['blocked_until']) {
            $blockedUntil = Carbon::parse($metadata['blocked_until']);
            if ($blockedUntil->isFuture()) {
                $this->reason = sprintf(
                    'Campaign is temporarily blocked until %s.',
                    $blockedUntil->format('Y-m-d H:i:s')
                );

                return false;
            }
        }

        return true;
    }

    /**
     * Get the remaining time for the campaign.
     */
    public function getRemainingTime(Campaign $campaign): ?string
    {
        if (! $this->isSatisfiedBy($campaign) || ! $campaign->end_date) {
            return null;
        }

        return $campaign->end_date->diffForHumans(now(), ['syntax' => 1]);
    }

    /**
     * Get the time since campaign started.
     */
    public function getTimeRunning(Campaign $campaign): ?string
    {
        if (! $campaign->start_date || $campaign->start_date->isFuture()) {
            return null;
        }

        return $campaign->start_date->diffForHumans(now(), ['syntax' => 1]);
    }

    /**
     * Get campaign progress as percentage of time elapsed.
     */
    public function getTimeProgress(Campaign $campaign): float
    {
        if (! $campaign->start_date || ! $campaign->end_date) {
            return 0.0;
        }

        $now = now();
        $totalDuration = $campaign->start_date->diffInSeconds($campaign->end_date);
        $elapsed = $campaign->start_date->diffInSeconds($now);

        if ($totalDuration <= 0) {
            return 0.0;
        }

        $progress = ($elapsed / $totalDuration) * 100;

        return max(0.0, min(100.0, $progress));
    }
}
