<?php

declare(strict_types=1);

namespace Modules\User\Domain\Specification;

use Illuminate\Support\Carbon;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a user can approve campaigns.
 *
 * A user can approve campaigns when:
 * - User is active and not locked
 * - User has campaign approval permission or admin role
 * - User's email is verified
 * - User belongs to an active organization (for organization-level approvers)
 */
class CanApproveCampaignSpecification extends CompositeSpecification
{
    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $candidate instanceof UserInterface) {
            return false;
        }

        // User must be active
        if (! $candidate->isActive()) {
            return false;
        }

        // User account must not be locked
        if ($candidate->isAccountLocked()) {
            return false;
        }

        // User must have verified email
        if (! $candidate->getEmailVerifiedAt() instanceof Carbon) {
            return false;
        }

        // User must have campaign approval permission or be super admin
        if (! $candidate->hasPermission('campaigns.approve') && ! $candidate->hasRole('super_admin')) {
            return false;
        }

        // If user belongs to an organization, it must be active
        if ($candidate->getOrganizationId()) {
            $organization = $candidate->getOrganization();

            if (! $organization || ! $organization->is_active) {
                return false;
            }
        }

        return true;
    }
}
