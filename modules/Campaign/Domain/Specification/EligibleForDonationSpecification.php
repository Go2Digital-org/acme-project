<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Specification;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a campaign is eligible for donations.
 *
 * A campaign is eligible for donations when:
 * - It has an active status that can accept donations
 * - The end date is in the future (not expired)
 * - The current amount is less than the goal amount (not completed)
 * - The campaign start date has passed (campaign has started)
 */
class EligibleForDonationSpecification extends CompositeSpecification
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        // Check if it's a Campaign model instance
        if (! $candidate instanceof Campaign) {
            return false;
        }

        // Campaign status must allow donations
        if (! $candidate->status->canAcceptDonations()) {
            return false;
        }

        // Campaign must not be expired
        if ($candidate->end_date === null || ! $candidate->end_date->isFuture()) {
            return false;
        }

        // Campaign must not have reached its goal
        if ($candidate->current_amount >= $candidate->goal_amount) {
            return false;
        }

        // Campaign must have started (start date has passed)
        if ($candidate->start_date === null || $candidate->start_date->isFuture()) {
            return false;
        }

        // Additional business rule: goal amount must be positive
        return $candidate->goal_amount > 0;
    }
}
