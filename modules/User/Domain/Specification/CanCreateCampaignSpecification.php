<?php

declare(strict_types=1);

namespace Modules\User\Domain\Specification;

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
 */
class CanCreateCampaignSpecification extends CompositeSpecification
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

        // User must have campaign creation permission
        if (! $candidate->hasPermission('campaigns.create')) {
            return false;
        }

        // User must belong to an organization
        if (! $candidate->getOrganizationId()) {
            return false;
        }

        $organization = $candidate->getOrganization();
        if (! $organization) {
            return false;
        }

        // Organization must be active and verified
        return $organization->is_active && $organization->is_verified;
    }
}
