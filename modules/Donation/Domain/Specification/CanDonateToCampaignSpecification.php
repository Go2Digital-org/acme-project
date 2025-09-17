<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Specification;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a user can donate to a campaign.
 *
 * A user can donate to a campaign when:
 * - Campaign is active and accepting donations
 * - Campaign is within its active period (start date passed, end date not reached)
 * - Campaign has not reached its goal amount
 * - User has permission to make donations
 * - User is not the campaign creator (optional business rule)
 * - Campaign organization is active
 */
final class CanDonateToCampaignSpecification extends CompositeSpecification
{
    private string $reason = '';

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! is_array($candidate) || ! isset($candidate['user'], $candidate['campaign'])) {
            $this->reason = 'Invalid input. Expected array with user and campaign.';

            return false;
        }

        $user = $candidate['user'];
        $campaign = $candidate['campaign'];

        if (! $user instanceof UserInterface) {
            $this->reason = 'Invalid user provided.';

            return false;
        }

        if (! $campaign instanceof Campaign) {
            $this->reason = 'Invalid campaign provided.';

            return false;
        }

        // User must be active
        if (! $user->isActive()) {
            $this->reason = 'User account is not active.';

            return false;
        }

        // User must have donation permission
        if (! $user->hasPermission('donations.create')) {
            $this->reason = 'User does not have permission to make donations.';

            return false;
        }

        // Campaign must be in a status that allows donations
        if (! $campaign->status->canAcceptDonations()) {
            $this->reason = sprintf(
                'Campaign is not accepting donations. Current status: %s.',
                $campaign->status->value
            );

            return false;
        }

        // Campaign must be within its active period
        now();

        if ($campaign->start_date && $campaign->start_date->isFuture()) {
            $this->reason = sprintf(
                'Campaign has not started yet. Start date: %s.',
                $campaign->start_date->format('Y-m-d H:i:s')
            );

            return false;
        }

        if ($campaign->end_date && $campaign->end_date->isPast()) {
            $this->reason = sprintf(
                'Campaign has ended. End date: %s.',
                $campaign->end_date->format('Y-m-d H:i:s')
            );

            return false;
        }

        // Campaign must not have reached its goal
        if ($campaign->current_amount >= $campaign->goal_amount) {
            $this->reason = 'Campaign has already reached its funding goal.';

            return false;
        }

        // Campaign goal amount must be valid
        if ($campaign->goal_amount <= 0) {
            $this->reason = 'Campaign has an invalid goal amount.';

            return false;
        }

        // User cannot donate to their own campaign (business rule)
        if ($campaign->user_id === $user->getId()) {
            $this->reason = 'Users cannot donate to their own campaigns.';

            return false;
        }

        // Campaign organization must be active
        $organization = $campaign->organization;
        if (! $organization || ! $organization->is_active) {
            $this->reason = 'Campaign organization is not active.';

            return false;
        }

        // Check if user's organization allows external donations (if applicable)
        $userOrganization = $user->getOrganization();
        if ($userOrganization && $campaign->organization_id !== $userOrganization->id && ! $this->canMakeExternalDonation($user)) {
            $this->reason = 'User organization policy does not allow external donations.';

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
     * Check if user can make external donations based on organization policy.
     */
    private function canMakeExternalDonation(UserInterface $user): bool
    {
        $userOrganization = $user->getOrganization();

        if (! $userOrganization) {
            return true; // External users can donate
        }

        // Check organization policy for external donations
        // This could be stored in organization metadata or settings
        $organizationSettings = $userOrganization->metadata ?? [];

        return $organizationSettings['allow_external_donations'] ?? true;
    }
}
