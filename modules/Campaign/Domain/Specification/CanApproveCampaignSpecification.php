<?php

declare(strict_types=1);

namespace Modules\Campaign\Domain\Specification;

use Illuminate\Support\Carbon;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a user can approve a campaign.
 *
 * A user can approve a campaign when:
 * - User has campaign approval permission or admin role
 * - User is active and not locked
 * - User's email is verified
 * - Campaign is in pending approval status
 * - Campaign has all required fields filled
 * - User is not the campaign creator (separation of duties)
 * - Campaign organization is active and verified
 */
final class CanApproveCampaignSpecification extends CompositeSpecification
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

        // User account must not be locked
        if ($user->isAccountLocked()) {
            $this->reason = 'User account is locked.';

            return false;
        }

        // User must have verified email
        if (! $user->getEmailVerifiedAt() instanceof Carbon) {
            $this->reason = 'User email address must be verified.';

            return false;
        }

        // User must have campaign approval permission or be super admin
        if (! $user->hasPermission('campaigns.approve') && ! $user->hasRole('super_admin')) {
            $this->reason = 'User does not have permission to approve campaigns.';

            return false;
        }

        // Campaign must be in pending approval status
        if ($campaign->status !== CampaignStatus::PENDING_APPROVAL) {
            $this->reason = sprintf(
                'Campaign is not in pending approval status. Current status: %s.',
                $campaign->status->value
            );

            return false;
        }

        // Campaign must not have been previously approved or rejected
        if ($campaign->approved_at) {
            $this->reason = 'Campaign has already been approved.';

            return false;
        }

        if ($campaign->rejected_at) {
            $this->reason = 'Campaign has been rejected and cannot be approved without resubmission.';

            return false;
        }

        // User cannot approve their own campaign (separation of duties)
        if ($campaign->user_id === $user->getId()) {
            $this->reason = 'Users cannot approve their own campaigns.';

            return false;
        }

        // Campaign must have all required fields
        if (! $this->hasRequiredFields($campaign)) {
            $this->reason = 'Campaign is missing required fields for approval.';

            return false;
        }

        // Campaign organization must be active and verified
        $organization = $campaign->organization;
        if (! $organization) {
            $this->reason = 'Campaign organization not found.';

            return false;
        }

        if (! $organization->is_active) {
            $this->reason = 'Campaign organization is not active.';

            return false;
        }

        if (! $organization->is_verified) {
            $this->reason = 'Campaign organization is not verified.';

            return false;
        }

        // Check if user has permission to approve campaigns for this organization
        if (! $this->canApproveForOrganization($user, $organization)) {
            $this->reason = 'User does not have permission to approve campaigns for this organization.';

            return false;
        }

        // Validate campaign dates
        if (! $this->hasValidDates($campaign)) {
            $this->reason = 'Campaign has invalid or conflicting dates.';

            return false;
        }

        // Validate campaign goal amount
        if ($campaign->goal_amount <= 0) {
            $this->reason = 'Campaign must have a positive goal amount.';

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
     * Check if campaign has all required fields for approval.
     */
    private function hasRequiredFields(Campaign $campaign): bool
    {
        // Required fields for campaign approval
        $requiredFields = [
            'title' => $campaign->getTitle(),
            'description' => $campaign->getDescription(),
            'goal_amount' => $campaign->goal_amount,
            'start_date' => $campaign->start_date,
            'end_date' => $campaign->end_date,
            'organization_id' => $campaign->organization_id,
            'user_id' => $campaign->user_id,
        ];

        foreach ($requiredFields as $value) {
            if (empty($value)) {
                return false;
            }
        }

        // Title and description must not be empty strings
        return trim($campaign->getTitle()) !== '' && trim((string) $campaign->getDescription()) !== '';
    }

    /**
     * Check if user can approve campaigns for the given organization.
     */
    private function canApproveForOrganization(UserInterface $user, mixed $organization): bool
    {
        // Super admins can approve for any organization
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Organization admins can approve for their own organization
        if ($user->hasRole('organization_admin') && $user->getOrganizationId() === $organization->id) {
            return true;
        }

        // Platform admins can approve for any organization
        return $user->hasRole('platform_admin');
    }

    /**
     * Validate campaign dates.
     */
    private function hasValidDates(Campaign $campaign): bool
    {
        if (! $campaign->start_date || ! $campaign->end_date) {
            return false;
        }

        // Start date must be before end date
        if ($campaign->start_date->isAfter($campaign->end_date)) {
            return false;
        }

        // End date must be in the future (at least 24 hours from now)
        if ($campaign->end_date->isBefore(now()->addHours(24))) {
            return false;
        }

        // Campaign duration must be at least 7 days
        return $campaign->start_date->diffInDays($campaign->end_date) >= 7;
    }
}
