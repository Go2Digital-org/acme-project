<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Specification;

use DB;
use Illuminate\Support\Carbon;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a user can create a campaign.
 *
 * A user can create a campaign when:
 * - User is active and not locked
 * - User has campaign creation permission
 * - User belongs to a verified and active organization
 * - User's email is verified
 * - Organization hasn't exceeded campaign limits
 * - User hasn't exceeded personal campaign limits
 */
final class CanCreateCampaignSpecification extends CompositeSpecification
{
    private const MAX_ACTIVE_CAMPAIGNS_PER_USER = 5;

    private const MAX_ACTIVE_CAMPAIGNS_PER_ORGANIZATION = 50;

    private string $reason = '';

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof UserInterface) {
            $this->reason = 'Invalid user provided.';

            return false;
        }

        // User must be active
        if (! $candidate->isActive()) {
            $this->reason = 'User account is not active.';

            return false;
        }

        // User account must not be locked
        if ($candidate->isAccountLocked()) {
            $this->reason = 'User account is locked.';

            return false;
        }

        // User must have verified email
        if (! $candidate->getEmailVerifiedAt() instanceof Carbon) {
            $this->reason = 'User email address must be verified.';

            return false;
        }

        // User must have campaign creation permission
        if (! $candidate->hasPermission('campaigns.create')) {
            $this->reason = 'User does not have permission to create campaigns.';

            return false;
        }

        // User must belong to an organization
        if (! $candidate->getOrganizationId()) {
            $this->reason = 'User must belong to an organization to create campaigns.';

            return false;
        }

        $organization = $candidate->getOrganization();
        if (! $organization) {
            $this->reason = 'User organization not found.';

            return false;
        }

        // Organization must be active and verified
        if (! $organization->is_active) {
            $this->reason = 'Organization is not active.';

            return false;
        }

        if (! $organization->is_verified) {
            $this->reason = 'Organization is not verified.';

            return false;
        }

        // Check user's active campaign limits
        $userActiveCampaigns = $this->getUserActiveCampaignCount($candidate);
        if ($userActiveCampaigns >= self::MAX_ACTIVE_CAMPAIGNS_PER_USER) {
            $this->reason = sprintf(
                'User has reached the maximum limit of %d active campaigns.',
                self::MAX_ACTIVE_CAMPAIGNS_PER_USER
            );

            return false;
        }

        // Check organization's active campaign limits
        $organizationActiveCampaigns = $this->getOrganizationActiveCampaignCount($organization);
        if ($organizationActiveCampaigns >= self::MAX_ACTIVE_CAMPAIGNS_PER_ORGANIZATION) {
            $this->reason = sprintf(
                'Organization has reached the maximum limit of %d active campaigns.',
                self::MAX_ACTIVE_CAMPAIGNS_PER_ORGANIZATION
            );

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
     * Get the count of active campaigns for a user.
     */
    private function getUserActiveCampaignCount(UserInterface $user): int
    {
        // This would typically be injected as a repository dependency
        // For now, using a simple query approach
        return DB::table('campaigns')
            ->where('user_id', $user->getId())
            ->whereIn('status', ['active', 'pending_approval'])
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get the count of active campaigns for an organization.
     */
    private function getOrganizationActiveCampaignCount(mixed $organization): int
    {
        // This would typically be injected as a repository dependency
        // For now, using a simple query approach
        return DB::table('campaigns')
            ->where('organization_id', $organization->id)
            ->whereIn('status', ['active', 'pending_approval'])
            ->whereNull('deleted_at')
            ->count();
    }
}
