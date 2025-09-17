<?php

declare(strict_types=1);

namespace Modules\User\Domain\Specification;

use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\CompositeSpecification;

/**
 * Specification to determine if a user can perform a specific action.
 *
 * A user can perform an action when:
 * - The user account is active (not locked, not restricted)
 * - The user belongs to an active organization (if required)
 * - The user has the required role/permission for the action
 * - The user's account is not expired or suspended
 * - The user has completed any required verification steps
 */
class UserPermissionSpecification extends CompositeSpecification
{
    public function __construct(
        private readonly string $action,
        private readonly bool $requiresActiveOrganization = true
    ) {}

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

        // User data processing must not be restricted
        if ($candidate->isDataProcessingRestricted()) {
            return false;
        }

        // User account must not be anonymized
        if ($candidate->isPersonalDataAnonymized()) {
            return false;
        }

        // Check organization requirements
        if ($this->requiresActiveOrganization) {
            if ($candidate->getOrganizationId() === null) {
                return false;
            }

            // Check if organization is available
            if ($candidate->getOrganization() === null) {
                return false;
            }

            $organization = $candidate->getOrganization();
            // Organization must be active and verified
            if (! $organization->is_active || ! $organization->is_verified) {
                return false;
            }
        }

        // Check specific permissions using Spatie Permission package
        return $this->hasPermissionForAction($candidate);
    }

    /**
     * Check if user has permission for the specific action.
     */
    private function hasPermissionForAction(UserInterface $user): bool
    {
        // Define action-to-permission mapping
        $permissionMap = [
            'create_campaign' => 'campaigns.create',
            'edit_campaign' => 'campaigns.edit',
            'delete_campaign' => 'campaigns.delete',
            'approve_campaign' => 'campaigns.approve',
            'reject_campaign' => 'campaigns.reject',
            'make_donation' => 'donations.create',
            'view_donations' => 'donations.view',
            'manage_organization' => 'organizations.manage',
            'verify_organization' => 'organizations.verify',
            'view_analytics' => 'analytics.view',
            'manage_users' => 'users.manage',
            'export_data' => 'data.export',
            'view_audit_logs' => 'audit.view',
        ];

        $permission = $permissionMap[$this->action] ?? $this->action;

        // Check if user has the specific permission
        if ($user->hasPermission($permission)) {
            return true;
        }

        // Check role-based permissions for common actions
        return $this->checkRoleBasedPermissions($user);
    }

    /**
     * Check role-based permissions for common actions.
     */
    private function checkRoleBasedPermissions(UserInterface $user): bool
    {
        return match ($this->action) {
            'create_campaign', 'edit_own_campaign', 'make_donation' => $user->hasAnyRole(['employee', 'manager', 'admin', 'super_admin']),

            'approve_campaign', 'reject_campaign' => $user->hasAnyRole(['manager', 'admin', 'super_admin']),

            'manage_organization', 'verify_organization' => $user->hasAnyRole(['admin', 'super_admin']),

            'view_all_campaigns', 'view_analytics' => $user->hasAnyRole(['manager', 'admin', 'super_admin']),

            'manage_users', 'view_audit_logs' => $user->hasAnyRole(['admin', 'super_admin']),

            'export_data', 'manage_system_settings' => $user->hasRole('super_admin'),

            default => false,
        };
    }

    /**
     * Create a specification for campaign creation permissions.
     */
    public static function canCreateCampaign(): self
    {
        return new self('create_campaign', true);
    }

    /**
     * Create a specification for campaign approval permissions.
     */
    public static function canApproveCampaign(): self
    {
        return new self('approve_campaign', true);
    }

    /**
     * Create a specification for donation permissions.
     */
    public static function canMakeDonation(): self
    {
        return new self('make_donation', true);
    }

    /**
     * Create a specification for organization management permissions.
     */
    public static function canManageOrganization(): self
    {
        return new self('manage_organization', false);
    }

    /**
     * Create a specification for data export permissions.
     */
    public static function canExportData(): self
    {
        return new self('export_data', true);
    }
}
