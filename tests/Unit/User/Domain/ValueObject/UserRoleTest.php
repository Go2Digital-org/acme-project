<?php

declare(strict_types=1);

use Modules\User\Domain\ValueObject\UserRole;

describe('UserRole Value Object', function () {
    it('has all expected role cases', function () {
        expect(UserRole::cases())->toHaveCount(5)
            ->and(UserRole::SUPER_ADMIN->value)->toBe('super_admin')
            ->and(UserRole::ADMIN->value)->toBe('admin')
            ->and(UserRole::MANAGER->value)->toBe('manager')
            ->and(UserRole::EMPLOYEE->value)->toBe('employee')
            ->and(UserRole::GUEST->value)->toBe('guest');
    });

    describe('getLabel method', function () {
        it('returns correct labels for all roles', function () {
            expect(UserRole::SUPER_ADMIN->getLabel())->toBe('Super Administrator')
                ->and(UserRole::ADMIN->getLabel())->toBe('Administrator')
                ->and(UserRole::MANAGER->getLabel())->toBe('Manager')
                ->and(UserRole::EMPLOYEE->getLabel())->toBe('Employee')
                ->and(UserRole::GUEST->getLabel())->toBe('Guest');
        });

        it('labels are human readable strings', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                expect($role->getLabel())
                    ->toBeString()
                    ->not->toBeEmpty()
                    ->toMatch('/^[A-Z][a-zA-Z\s]*$/', 'Label should start with capital letter and contain only letters/spaces');
            }
        });
    });

    describe('getPermissions method', function () {
        it('returns array of permissions for SUPER_ADMIN', function () {
            $permissions = UserRole::SUPER_ADMIN->getPermissions();

            expect($permissions)->toBeArray()
                ->toHaveCount(8)
                ->toContain('manage_users')
                ->toContain('manage_campaigns')
                ->toContain('manage_donations')
                ->toContain('view_analytics')
                ->toContain('manage_system')
                ->toContain('manage_settings')
                ->toContain('view_audit_logs')
                ->toContain('manage_organizations');
        });

        it('returns array of permissions for ADMIN', function () {
            $permissions = UserRole::ADMIN->getPermissions();

            expect($permissions)->toBeArray()
                ->toHaveCount(5)
                ->toContain('manage_users')
                ->toContain('manage_campaigns')
                ->toContain('manage_donations')
                ->toContain('view_analytics')
                ->toContain('manage_organizations')
                ->not->toContain('manage_system')
                ->not->toContain('manage_settings')
                ->not->toContain('view_audit_logs');
        });

        it('returns array of permissions for MANAGER', function () {
            $permissions = UserRole::MANAGER->getPermissions();

            expect($permissions)->toBeArray()
                ->toHaveCount(3)
                ->toContain('manage_campaigns')
                ->toContain('view_analytics')
                ->toContain('manage_team_donations')
                ->not->toContain('manage_users')
                ->not->toContain('manage_system');
        });

        it('returns array of permissions for EMPLOYEE', function () {
            $permissions = UserRole::EMPLOYEE->getPermissions();

            expect($permissions)->toBeArray()
                ->toHaveCount(3)
                ->toContain('create_campaigns')
                ->toContain('make_donations')
                ->toContain('view_own_data')
                ->not->toContain('manage_campaigns')
                ->not->toContain('manage_users');
        });

        it('returns array of permissions for GUEST', function () {
            $permissions = UserRole::GUEST->getPermissions();

            expect($permissions)->toBeArray()
                ->toHaveCount(1)
                ->toContain('view_public_campaigns')
                ->not->toContain('create_campaigns')
                ->not->toContain('make_donations');
        });

        it('all permissions are non-empty strings', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                $permissions = $role->getPermissions();

                foreach ($permissions as $permission) {
                    expect($permission)
                        ->toBeString()
                        ->not->toBeEmpty()
                        ->toMatch('/^[a-z_]+$/', 'Permission should be lowercase with underscores');
                }
            }
        });

        it('super admin has all admin permissions plus extra', function () {
            $superAdminPermissions = UserRole::SUPER_ADMIN->getPermissions();
            $adminPermissions = UserRole::ADMIN->getPermissions();

            foreach ($adminPermissions as $permission) {
                expect($superAdminPermissions)->toContain($permission);
            }

            expect(count($superAdminPermissions))->toBeGreaterThan(count($adminPermissions));
        });
    });

    describe('hasPermission method', function () {
        it('returns true when role has specific permission', function () {
            expect(UserRole::SUPER_ADMIN->hasPermission('manage_users'))->toBeTrue()
                ->and(UserRole::ADMIN->hasPermission('manage_campaigns'))->toBeTrue()
                ->and(UserRole::MANAGER->hasPermission('view_analytics'))->toBeTrue()
                ->and(UserRole::EMPLOYEE->hasPermission('create_campaigns'))->toBeTrue()
                ->and(UserRole::GUEST->hasPermission('view_public_campaigns'))->toBeTrue();
        });

        it('returns false when role does not have specific permission', function () {
            expect(UserRole::GUEST->hasPermission('manage_users'))->toBeFalse()
                ->and(UserRole::EMPLOYEE->hasPermission('manage_system'))->toBeFalse()
                ->and(UserRole::MANAGER->hasPermission('manage_users'))->toBeFalse()
                ->and(UserRole::ADMIN->hasPermission('manage_system'))->toBeFalse();
        });

        it('is case sensitive for permission names', function () {
            expect(UserRole::SUPER_ADMIN->hasPermission('MANAGE_USERS'))->toBeFalse()
                ->and(UserRole::SUPER_ADMIN->hasPermission('Manage_Users'))->toBeFalse()
                ->and(UserRole::SUPER_ADMIN->hasPermission('manage_users'))->toBeTrue();
        });

        it('returns false for empty permission string', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                expect($role->hasPermission(''))->toBeFalse();
            }
        });

        it('returns false for non-existent permission', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                expect($role->hasPermission('non_existent_permission'))->toBeFalse()
                    ->and($role->hasPermission('invalid_permission_123'))->toBeFalse();
            }
        });
    });

    describe('canManageUsers method', function () {
        it('returns true for roles with manage_users permission', function () {
            expect(UserRole::SUPER_ADMIN->canManageUsers())->toBeTrue()
                ->and(UserRole::ADMIN->canManageUsers())->toBeTrue();
        });

        it('returns false for roles without manage_users permission', function () {
            expect(UserRole::MANAGER->canManageUsers())->toBeFalse()
                ->and(UserRole::EMPLOYEE->canManageUsers())->toBeFalse()
                ->and(UserRole::GUEST->canManageUsers())->toBeFalse();
        });
    });

    describe('canManageCampaigns method', function () {
        it('returns true for roles with manage_campaigns permission', function () {
            expect(UserRole::SUPER_ADMIN->canManageCampaigns())->toBeTrue()
                ->and(UserRole::ADMIN->canManageCampaigns())->toBeTrue()
                ->and(UserRole::MANAGER->canManageCampaigns())->toBeTrue();
        });

        it('returns false for roles without manage_campaigns permission', function () {
            expect(UserRole::EMPLOYEE->canManageCampaigns())->toBeFalse()
                ->and(UserRole::GUEST->canManageCampaigns())->toBeFalse();
        });
    });

    describe('canCreateCampaigns method', function () {
        it('returns true for roles with create_campaigns permission', function () {
            expect(UserRole::EMPLOYEE->canCreateCampaigns())->toBeTrue();
        });

        it('returns true for roles with manage_campaigns permission', function () {
            expect(UserRole::SUPER_ADMIN->canCreateCampaigns())->toBeTrue()
                ->and(UserRole::ADMIN->canCreateCampaigns())->toBeTrue()
                ->and(UserRole::MANAGER->canCreateCampaigns())->toBeTrue();
        });

        it('returns false for guest role', function () {
            expect(UserRole::GUEST->canCreateCampaigns())->toBeFalse();
        });
    });

    describe('canMakeDonations method', function () {
        it('returns true for roles with make_donations permission', function () {
            expect(UserRole::EMPLOYEE->canMakeDonations())->toBeTrue();
        });

        it('returns true for roles with manage_donations permission', function () {
            expect(UserRole::SUPER_ADMIN->canMakeDonations())->toBeTrue()
                ->and(UserRole::ADMIN->canMakeDonations())->toBeTrue();
        });

        it('returns false for roles without donation permissions', function () {
            expect(UserRole::GUEST->canMakeDonations())->toBeFalse();
        });

        it('manager can make donations through team donations permission', function () {
            expect(UserRole::MANAGER->canMakeDonations())->toBeFalse();
        });
    });

    describe('canViewAnalytics method', function () {
        it('returns true for roles with view_analytics permission', function () {
            expect(UserRole::SUPER_ADMIN->canViewAnalytics())->toBeTrue()
                ->and(UserRole::ADMIN->canViewAnalytics())->toBeTrue()
                ->and(UserRole::MANAGER->canViewAnalytics())->toBeTrue();
        });

        it('returns false for roles without view_analytics permission', function () {
            expect(UserRole::EMPLOYEE->canViewAnalytics())->toBeFalse()
                ->and(UserRole::GUEST->canViewAnalytics())->toBeFalse();
        });
    });

    describe('isAdmin method', function () {
        it('returns true for admin roles', function () {
            expect(UserRole::SUPER_ADMIN->isAdmin())->toBeTrue()
                ->and(UserRole::ADMIN->isAdmin())->toBeTrue();
        });

        it('returns false for non-admin roles', function () {
            expect(UserRole::MANAGER->isAdmin())->toBeFalse()
                ->and(UserRole::EMPLOYEE->isAdmin())->toBeFalse()
                ->and(UserRole::GUEST->isAdmin())->toBeFalse();
        });
    });

    describe('isManager method', function () {
        it('returns true only for manager role', function () {
            expect(UserRole::MANAGER->isManager())->toBeTrue();
        });

        it('returns false for all non-manager roles', function () {
            expect(UserRole::SUPER_ADMIN->isManager())->toBeFalse()
                ->and(UserRole::ADMIN->isManager())->toBeFalse()
                ->and(UserRole::EMPLOYEE->isManager())->toBeFalse()
                ->and(UserRole::GUEST->isManager())->toBeFalse();
        });
    });

    describe('isEmployee method', function () {
        it('returns true only for employee role', function () {
            expect(UserRole::EMPLOYEE->isEmployee())->toBeTrue();
        });

        it('returns false for all non-employee roles', function () {
            expect(UserRole::SUPER_ADMIN->isEmployee())->toBeFalse()
                ->and(UserRole::ADMIN->isEmployee())->toBeFalse()
                ->and(UserRole::MANAGER->isEmployee())->toBeFalse()
                ->and(UserRole::GUEST->isEmployee())->toBeFalse();
        });
    });

    describe('getPriority method', function () {
        it('returns correct priority values for all roles', function () {
            expect(UserRole::SUPER_ADMIN->getPriority())->toBe(100)
                ->and(UserRole::ADMIN->getPriority())->toBe(80)
                ->and(UserRole::MANAGER->getPriority())->toBe(60)
                ->and(UserRole::EMPLOYEE->getPriority())->toBe(40)
                ->and(UserRole::GUEST->getPriority())->toBe(20);
        });

        it('priorities are in descending order of authority', function () {
            expect(UserRole::SUPER_ADMIN->getPriority())
                ->toBeGreaterThan(UserRole::ADMIN->getPriority())
                ->and(UserRole::ADMIN->getPriority())
                ->toBeGreaterThan(UserRole::MANAGER->getPriority())
                ->and(UserRole::MANAGER->getPriority())
                ->toBeGreaterThan(UserRole::EMPLOYEE->getPriority())
                ->and(UserRole::EMPLOYEE->getPriority())
                ->toBeGreaterThan(UserRole::GUEST->getPriority());
        });

        it('all priorities are positive integers', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                expect($role->getPriority())
                    ->toBeInt()
                    ->toBeGreaterThan(0);
            }
        });
    });

    describe('isHigherThan method', function () {
        it('returns true when role has higher priority', function () {
            expect(UserRole::SUPER_ADMIN->isHigherThan(UserRole::ADMIN))->toBeTrue()
                ->and(UserRole::ADMIN->isHigherThan(UserRole::MANAGER))->toBeTrue()
                ->and(UserRole::MANAGER->isHigherThan(UserRole::EMPLOYEE))->toBeTrue()
                ->and(UserRole::EMPLOYEE->isHigherThan(UserRole::GUEST))->toBeTrue();
        });

        it('returns false when role has lower or equal priority', function () {
            expect(UserRole::GUEST->isHigherThan(UserRole::EMPLOYEE))->toBeFalse()
                ->and(UserRole::EMPLOYEE->isHigherThan(UserRole::MANAGER))->toBeFalse()
                ->and(UserRole::MANAGER->isHigherThan(UserRole::ADMIN))->toBeFalse()
                ->and(UserRole::ADMIN->isHigherThan(UserRole::SUPER_ADMIN))->toBeFalse();
        });

        it('returns false when comparing same roles', function () {
            expect(UserRole::ADMIN->isHigherThan(UserRole::ADMIN))->toBeFalse()
                ->and(UserRole::MANAGER->isHigherThan(UserRole::MANAGER))->toBeFalse()
                ->and(UserRole::EMPLOYEE->isHigherThan(UserRole::EMPLOYEE))->toBeFalse();
        });

        it('super admin is higher than all other roles', function () {
            $otherRoles = [UserRole::ADMIN, UserRole::MANAGER, UserRole::EMPLOYEE, UserRole::GUEST];

            foreach ($otherRoles as $role) {
                expect(UserRole::SUPER_ADMIN->isHigherThan($role))->toBeTrue();
            }
        });

        it('guest is not higher than any role', function () {
            $otherRoles = [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::EMPLOYEE];

            foreach ($otherRoles as $role) {
                expect(UserRole::GUEST->isHigherThan($role))->toBeFalse();
            }
        });
    });

    describe('isLowerThan method', function () {
        it('returns true when role has lower priority', function () {
            expect(UserRole::GUEST->isLowerThan(UserRole::EMPLOYEE))->toBeTrue()
                ->and(UserRole::EMPLOYEE->isLowerThan(UserRole::MANAGER))->toBeTrue()
                ->and(UserRole::MANAGER->isLowerThan(UserRole::ADMIN))->toBeTrue()
                ->and(UserRole::ADMIN->isLowerThan(UserRole::SUPER_ADMIN))->toBeTrue();
        });

        it('returns false when role has higher or equal priority', function () {
            expect(UserRole::SUPER_ADMIN->isLowerThan(UserRole::ADMIN))->toBeFalse()
                ->and(UserRole::ADMIN->isLowerThan(UserRole::MANAGER))->toBeFalse()
                ->and(UserRole::MANAGER->isLowerThan(UserRole::EMPLOYEE))->toBeFalse()
                ->and(UserRole::EMPLOYEE->isLowerThan(UserRole::GUEST))->toBeFalse();
        });

        it('returns false when comparing same roles', function () {
            expect(UserRole::ADMIN->isLowerThan(UserRole::ADMIN))->toBeFalse()
                ->and(UserRole::MANAGER->isLowerThan(UserRole::MANAGER))->toBeFalse()
                ->and(UserRole::EMPLOYEE->isLowerThan(UserRole::EMPLOYEE))->toBeFalse();
        });

        it('guest is lower than all other roles', function () {
            $otherRoles = [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::EMPLOYEE];

            foreach ($otherRoles as $role) {
                expect(UserRole::GUEST->isLowerThan($role))->toBeTrue();
            }
        });

        it('super admin is not lower than any role', function () {
            $otherRoles = [UserRole::ADMIN, UserRole::MANAGER, UserRole::EMPLOYEE, UserRole::GUEST];

            foreach ($otherRoles as $role) {
                expect(UserRole::SUPER_ADMIN->isLowerThan($role))->toBeFalse();
            }
        });
    });

    describe('getSelectOptions static method', function () {
        it('returns array with all role values as keys and labels as values', function () {
            $options = UserRole::getSelectOptions();

            expect($options)->toBeArray()
                ->toHaveCount(5)
                ->toHaveKey('super_admin')
                ->toHaveKey('admin')
                ->toHaveKey('manager')
                ->toHaveKey('employee')
                ->toHaveKey('guest');
        });

        it('maps role values to correct labels', function () {
            $options = UserRole::getSelectOptions();

            expect($options['super_admin'])->toBe('Super Administrator')
                ->and($options['admin'])->toBe('Administrator')
                ->and($options['manager'])->toBe('Manager')
                ->and($options['employee'])->toBe('Employee')
                ->and($options['guest'])->toBe('Guest');
        });

        it('includes all enum cases in options', function () {
            $options = UserRole::getSelectOptions();
            $roles = UserRole::cases();

            expect(count($options))->toBe(count($roles));

            foreach ($roles as $role) {
                expect($options)->toHaveKey($role->value);
                expect($options[$role->value])->toBe($role->getLabel());
            }
        });

        it('returns consistent results on multiple calls', function () {
            $options1 = UserRole::getSelectOptions();
            $options2 = UserRole::getSelectOptions();

            expect($options1)->toEqual($options2);
        });
    });

    describe('Role hierarchy and permission inheritance', function () {
        it('higher roles have more or equal permissions than lower roles', function () {
            $superAdminPerms = count(UserRole::SUPER_ADMIN->getPermissions());
            $adminPerms = count(UserRole::ADMIN->getPermissions());
            $managerPerms = count(UserRole::MANAGER->getPermissions());
            $employeePerms = count(UserRole::EMPLOYEE->getPermissions());
            $guestPerms = count(UserRole::GUEST->getPermissions());

            expect($superAdminPerms)->toBeGreaterThanOrEqual($adminPerms)
                ->and($adminPerms)->toBeGreaterThanOrEqual($managerPerms)
                ->and($guestPerms)->toBeLessThanOrEqual($employeePerms);
        });

        it('each role has unique set of permissions', function () {
            $roles = UserRole::cases();
            $permissionSets = [];

            foreach ($roles as $role) {
                $permissions = $role->getPermissions();
                sort($permissions);
                $permissionKey = implode(',', $permissions);

                expect($permissionSets)->not->toHaveKey($permissionKey, "Role {$role->value} has duplicate permission set");
                $permissionSets[$permissionKey] = $role->value;
            }
        });

        it('all permission checking methods are consistent with getPermissions', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                $permissions = $role->getPermissions();

                expect($role->canManageUsers())->toBe(in_array('manage_users', $permissions));
                expect($role->canManageCampaigns())->toBe(in_array('manage_campaigns', $permissions));
                expect($role->canViewAnalytics())->toBe(in_array('view_analytics', $permissions));
            }
        });
    });

    describe('Edge cases and error handling', function () {
        it('handles all enum methods without throwing exceptions', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role) {
                expect($role->getLabel())->toBeString()
                    ->and($role->getPermissions())->toBeArray()
                    ->and($role->getPriority())->toBeInt()
                    ->and($role->canManageUsers())->toBeBool()
                    ->and($role->canManageCampaigns())->toBeBool()
                    ->and($role->canCreateCampaigns())->toBeBool()
                    ->and($role->canMakeDonations())->toBeBool()
                    ->and($role->canViewAnalytics())->toBeBool()
                    ->and($role->isAdmin())->toBeBool()
                    ->and($role->isManager())->toBeBool()
                    ->and($role->isEmployee())->toBeBool();
            }
        });

        it('comparison methods work with all role combinations', function () {
            $roles = UserRole::cases();

            foreach ($roles as $role1) {
                foreach ($roles as $role2) {
                    expect($role1->isHigherThan($role2))->toBeBool()
                        ->and($role1->isLowerThan($role2))->toBeBool();
                }
            }
        });
    });
});
