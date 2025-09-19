<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Specification;

use InvalidArgumentException;
use Modules\Campaign\Domain\Specification\CampaignApprovalSpecification;
use Modules\Campaign\Domain\Specification\EligibleForDonationSpecification;
use Modules\Organization\Domain\Specification\OrganizationVerificationSpecification;
use Modules\User\Domain\Specification\UserPermissionSpecification;

/**
 * Factory for creating commonly used specification combinations.
 *
 * This factory encapsulates the creation of complex specifications
 * that combine multiple business rules using AND, OR, and NOT operations.
 */
class SpecificationFactory
{
    /**
     * Create a specification for a complete campaign publishing workflow.
     *
     * A campaign can be published when:
     * - It meets all approval requirements
     * - It is eligible to receive donations
     */
    public function createCampaignPublishingSpecification(): SpecificationInterface
    {
        $approvalSpec = new CampaignApprovalSpecification;
        $donationEligibilitySpec = new EligibleForDonationSpecification;

        return $approvalSpec->and($donationEligibilitySpec);
    }

    /**
     * Create a specification for organization admin capabilities.
     *
     * An organization admin can perform administrative actions when:
     * - The organization is verified
     * - The user has admin permissions
     */
    public function createOrganizationAdminSpecification(): SpecificationInterface
    {
        $orgVerificationSpec = new OrganizationVerificationSpecification;
        $adminPermissionSpec = UserPermissionSpecification::canManageOrganization();

        return $orgVerificationSpec->and($adminPermissionSpec);
    }

    /**
     * Create a specification for campaign lifecycle management.
     *
     * A user can fully manage campaigns when they can both:
     * - Create campaigns
     * - Approve campaigns (for admin users)
     */
    public function createCampaignManagerSpecification(): SpecificationInterface
    {
        $createSpec = UserPermissionSpecification::canCreateCampaign();
        $approveSpec = UserPermissionSpecification::canApproveCampaign();

        return $createSpec->or($approveSpec);
    }

    /**
     * Create a specification for donation processing eligibility.
     *
     * A donation can be processed when:
     * - The campaign is eligible for donations
     * - The user has permission to make donations
     */
    public function createDonationProcessingSpecification(): SpecificationInterface
    {
        $campaignEligibilitySpec = new EligibleForDonationSpecification;
        $donationPermissionSpec = UserPermissionSpecification::canMakeDonation();

        return $campaignEligibilitySpec->and($donationPermissionSpec);
    }

    /**
     * Create a specification for super admin access.
     *
     * Super admins can bypass most restrictions but still need:
     * - Active account
     * - Super admin permissions
     */
    public function createSuperAdminSpecification(): SpecificationInterface
    {
        return new UserPermissionSpecification('manage_system_settings', false);
    }

    /**
     * Create a specification for verified organization member.
     *
     * A verified organization member is a user who:
     * - Can create campaigns
     * - Belongs to a verified organization
     */
    public function createVerifiedOrganizationMemberSpecification(): SpecificationInterface
    {
        $createCampaignSpec = UserPermissionSpecification::canCreateCampaign();
        $orgVerificationSpec = new OrganizationVerificationSpecification;

        return $createCampaignSpec->and($orgVerificationSpec);
    }

    /**
     * Create a specification for campaign moderation actions.
     *
     * A user can moderate campaigns when they can either:
     * - Approve campaigns (admin/moderator)
     * - Create campaigns in their own organization
     */
    public function createCampaignModerationSpecification(): SpecificationInterface
    {
        $approveSpec = UserPermissionSpecification::canApproveCampaign();
        $createSpec = UserPermissionSpecification::canCreateCampaign();

        return $approveSpec->or($createSpec);
    }

    /**
     * Create a specification for restricted campaign access.
     *
     * Some campaigns may have restricted access and require:
     * - User can create campaigns
     * - Organization is verified
     * - User has special permissions
     */
    public function createRestrictedCampaignAccessSpecification(): SpecificationInterface
    {
        $createSpec = UserPermissionSpecification::canCreateCampaign();
        $orgVerificationSpec = new OrganizationVerificationSpecification;
        $specialPermissionSpec = new UserPermissionSpecification('campaigns.restricted', true);

        return $createSpec
            ->and($orgVerificationSpec)
            ->and($specialPermissionSpec);
    }

    /**
     * Create a specification for public campaign viewing.
     *
     * Anyone can view public campaigns, but private campaigns require:
     * - User authentication
     * - Organization membership (for organization-private campaigns)
     */
    public function createPublicCampaignViewSpecification(): SpecificationInterface
    {
        $donationEligibilitySpec = new EligibleForDonationSpecification;
        $viewPermissionSpec = new UserPermissionSpecification('campaigns.view', false);

        return $donationEligibilitySpec->or($viewPermissionSpec);
    }

    /**
     * Create a custom specification by combining existing ones.
     *
     * @param  SpecificationInterface[]  $specifications
     * @param  string  $operator  'and'|'or'
     */
    public function createCustomSpecification(array $specifications, string $operator = 'and'): SpecificationInterface
    {
        if ($specifications === []) {
            throw new InvalidArgumentException('At least one specification is required');
        }

        // Validate operator regardless of specification count
        if (! in_array($operator, ['and', 'or'], true)) {
            throw new InvalidArgumentException("Invalid operator: {$operator}. Use 'and' or 'or'.");
        }

        if (count($specifications) === 1) {
            return $specifications[0];
        }

        $result = array_shift($specifications);

        foreach ($specifications as $spec) {
            $result = match ($operator) {
                'and' => $result->and($spec),
                'or' => $result->or($spec),
            };
        }

        return $result;
    }

    /**
     * Create a negated specification.
     */
    public function createNotSpecification(SpecificationInterface $specification): SpecificationInterface
    {
        return $specification->not();
    }
}
