<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Filament\Policies;

use DB;
use Modules\Organization\Domain\Model\Organization;
use Modules\Shared\Infrastructure\Filament\Policies\AdminAccessPolicy;
use Modules\User\Infrastructure\Laravel\Models\User;

final readonly class OrganizationPolicy
{
    public function __construct(
        private AdminAccessPolicy $adminAccessPolicy,
    ) {}

    /**
     * Determine whether the user can view any organizations.
     */
    public function viewAny(User $user): bool
    {
        return $this->adminAccessPolicy->viewAny($user);
    }

    /**
     * Determine whether the user can view the organization.
     */
    public function view(User $user): bool
    {
        return $this->adminAccessPolicy->viewAny($user);
    }

    /**
     * Determine whether the user can create organizations.
     */
    public function create(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can update the organization.
     */
    public function update(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can delete the organization.
     */
    public function delete(User $user, Organization $organization): bool
    {
        // Can only delete organizations with no campaigns
        if (DB::table('campaigns')->where('organization_id', $organization->id)->count() > 0) {
            return false;
        }

        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can restore the organization.
     */
    public function restore(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can permanently delete the organization.
     */
    public function forceDelete(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can verify organizations.
     */
    public function verify(User $user): bool
    {
        return $this->adminAccessPolicy->verifyOrganizations($user);
    }

    /**
     * Determine whether the user can unverify organizations.
     */
    public function unverify(User $user): bool
    {
        return $this->adminAccessPolicy->verifyOrganizations($user);
    }

    /**
     * Determine whether the user can activate organizations.
     */
    public function activate(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can deactivate organizations.
     */
    public function deactivate(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can perform compliance checks.
     */
    public function complianceCheck(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can view organization analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $this->adminAccessPolicy->viewAnalytics($user);
    }

    /**
     * Determine whether the user can export organization data.
     */
    public function export(User $user): bool
    {
        return $this->adminAccessPolicy->exportReports($user);
    }

    /**
     * Determine whether the user can bulk verify organizations.
     */
    public function bulkVerify(User $user): bool
    {
        return $this->adminAccessPolicy->verifyOrganizations($user);
    }

    /**
     * Determine whether the user can bulk activate organizations.
     */
    public function bulkActivate(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can send renewal notices.
     */
    public function sendRenewalNotices(User $user): bool
    {
        return $this->adminAccessPolicy->manageOrganizations($user);
    }

    /**
     * Determine whether the user can manage tax-exempt status.
     */
    public function manageTaxExempt(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'csr_admin', 'finance_admin'], true);
    }

    /**
     * Determine whether the user can view sensitive organization data.
     */
    public function viewSensitive(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'csr_admin'], true);
    }

    /**
     * Determine whether the user can manage organization compliance.
     */
    public function manageCompliance(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'csr_admin'], true);
    }

    /**
     * Determine whether the user can view audit trail.
     */
    public function viewAuditTrail(User $user): bool
    {
        return $this->adminAccessPolicy->viewAuditLogs($user);
    }
}
