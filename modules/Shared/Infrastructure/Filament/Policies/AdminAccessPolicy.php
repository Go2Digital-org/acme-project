<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Filament\Policies;

use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Central policy for admin access control across all Filament resources.
 *
 * This policy implements a role-based access control system for enterprise
 * security compliance, ensuring proper segregation of duties and audit trails.
 */
final readonly class AdminAccessPolicy
{
    /**
     * Define role hierarchies for access control.
     *
     * @var array<string, mixed>
     */
    private const ROLE_PERMISSIONS = [
        'super_admin' => [
            'view_any',
            'manage_organizations',
            'verify_organizations',
            'view_analytics',
            'export_reports',
            'view_audit_logs',
            'manage_users',
            'system_admin',
        ],
        'csr_admin' => [
            'view_any',
            'manage_organizations',
            'verify_organizations',
            'view_analytics',
            'export_reports',
            'view_audit_logs',
        ],
        'finance_admin' => [
            'view_any',
            'view_analytics',
            'export_reports',
            'manage_financial_data',
        ],
        'org_manager' => [
            'view_any',
            'view_analytics',
        ],
        'employee' => [
            'view_any',
        ],
    ];

    /**
     * Determine if user can view any resources.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'view_any');
    }

    /**
     * Determine if user can manage organizations.
     */
    public function manageOrganizations(User $user): bool
    {
        return $this->hasPermission($user, 'manage_organizations');
    }

    /**
     * Determine if user can verify organizations.
     */
    public function verifyOrganizations(User $user): bool
    {
        return $this->hasPermission($user, 'verify_organizations');
    }

    /**
     * Determine if user can view analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $this->hasPermission($user, 'view_analytics');
    }

    /**
     * Determine if user can export reports.
     */
    public function exportReports(User $user): bool
    {
        return $this->hasPermission($user, 'export_reports');
    }

    /**
     * Determine if user can view audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return $this->hasPermission($user, 'view_audit_logs');
    }

    /**
     * Determine if user can manage users.
     */
    public function manageUsers(User $user): bool
    {
        return $this->hasPermission($user, 'manage_users');
    }

    /**
     * Check if user has a specific permission based on their role.
     */
    private function hasPermission(User $user, string $permission): bool
    {
        $userRole = $user->role;

        if (! isset(self::ROLE_PERMISSIONS[$userRole])) {
            return false;
        }

        return in_array($permission, self::ROLE_PERMISSIONS[$userRole], true);
    }
}
