<?php

declare(strict_types=1);

use Modules\Shared\Domain\Contract\UserInterface;
use Modules\Shared\Domain\Specification\SpecificationInterface;
use Modules\User\Domain\Specification\UserPermissionSpecification;

afterEach(function (): void {
    Mockery::close();
});

describe('UserPermissionSpecification', function (): void {
    it('returns false for non-user objects', function (): void {
        $specification = new UserPermissionSpecification('create_campaign');

        expect($specification->isSatisfiedBy('not a user'))->toBeFalse();
        expect($specification->isSatisfiedBy(new stdClass))->toBeFalse();
        expect($specification->isSatisfiedBy(null))->toBeFalse();
    });

    it('returns false when user is not active', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(false);

        $specification = new UserPermissionSpecification('create_campaign');

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when user account is locked', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(true);

        $specification = new UserPermissionSpecification('create_campaign');

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when user data processing is restricted', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(true);

        $specification = new UserPermissionSpecification('create_campaign');

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when user personal data is anonymized', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(true);

        $specification = new UserPermissionSpecification('create_campaign');

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when organization is required but user has no organization id', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(null);

        $specification = new UserPermissionSpecification('create_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when organization is required but user has no organization', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->once()->andReturn(null);

        $specification = new UserPermissionSpecification('create_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when organization is not active', function (): void {
        $organization = (object) [
            'is_active' => false,
            'is_verified' => true,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->twice()->andReturn($organization);

        $specification = new UserPermissionSpecification('create_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns false when organization is not verified', function (): void {
        $organization = (object) [
            'is_active' => true,
            'is_verified' => false,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->twice()->andReturn($organization);

        $specification = new UserPermissionSpecification('create_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('returns true when user has specific permission for action', function (): void {
        $organization = (object) [
            'is_active' => true,
            'is_verified' => true,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->twice()->andReturn($organization);
        $user->shouldReceive('hasPermission')->with('campaigns.create')->once()->andReturn(true);

        $specification = new UserPermissionSpecification('create_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeTrue();
    });

    it('returns true when user has role-based permission for campaign creation', function (): void {
        $organization = (object) [
            'is_active' => true,
            'is_verified' => true,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->twice()->andReturn($organization);
        $user->shouldReceive('hasPermission')->with('campaigns.create')->once()->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['employee', 'manager', 'admin', 'super_admin'])->once()->andReturn(true);

        $specification = new UserPermissionSpecification('create_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeTrue();
    });

    it('returns true when user has role-based permission for campaign approval', function (): void {
        $organization = (object) [
            'is_active' => true,
            'is_verified' => true,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->twice()->andReturn($organization);
        $user->shouldReceive('hasPermission')->with('campaigns.approve')->once()->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['manager', 'admin', 'super_admin'])->once()->andReturn(true);

        $specification = new UserPermissionSpecification('approve_campaign', true);

        expect($specification->isSatisfiedBy($user))->toBeTrue();
    });

    it('returns false when user has no permission or role for action', function (): void {
        $organization = (object) [
            'is_active' => true,
            'is_verified' => true,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->twice()->andReturn($organization);
        $user->shouldReceive('hasPermission')->with('organizations.manage')->once()->andReturn(false);
        $user->shouldReceive('hasAnyRole')->with(['admin', 'super_admin'])->once()->andReturn(false);

        $specification = new UserPermissionSpecification('manage_organization', true);

        expect($specification->isSatisfiedBy($user))->toBeFalse();
    });

    it('bypasses organization requirements when not required', function (): void {
        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('hasPermission')->with('data.export')->once()->andReturn(false);
        $user->shouldReceive('hasRole')->with('super_admin')->once()->andReturn(true);

        $specification = new UserPermissionSpecification('export_data', false);

        expect($specification->isSatisfiedBy($user))->toBeTrue();
    });

    it('can be combined with other specifications using and()', function (): void {
        $organization = (object) [
            'is_active' => true,
            'is_verified' => true,
        ];

        $user = Mockery::mock(UserInterface::class);
        $user->shouldReceive('isActive')->once()->andReturn(true);
        $user->shouldReceive('isAccountLocked')->once()->andReturn(false);
        $user->shouldReceive('isDataProcessingRestricted')->once()->andReturn(false);
        $user->shouldReceive('isPersonalDataAnonymized')->once()->andReturn(false);
        $user->shouldReceive('getOrganizationId')->once()->andReturn(1);
        $user->shouldReceive('getOrganization')->times(2)->andReturn($organization);
        $user->shouldReceive('hasPermission')->with('campaigns.create')->once()->andReturn(true);

        $userSpec = new UserPermissionSpecification('create_campaign', true);
        $mockSecondSpec = Mockery::mock(SpecificationInterface::class);
        $mockSecondSpec->shouldReceive('isSatisfiedBy')->with($user)->once()->andReturn(true);

        $combinedSpec = $userSpec->and($mockSecondSpec);

        expect($combinedSpec->isSatisfiedBy($user))->toBeTrue();
    });
});

describe('UserPermissionSpecification Factory Methods', function (): void {
    it('creates correct specifications through static factory methods', function (): void {
        $createSpec = UserPermissionSpecification::canCreateCampaign();
        $approveSpec = UserPermissionSpecification::canApproveCampaign();
        $donateSpec = UserPermissionSpecification::canMakeDonation();
        $manageSpec = UserPermissionSpecification::canManageOrganization();
        $exportSpec = UserPermissionSpecification::canExportData();

        expect($createSpec)->toBeInstanceOf(UserPermissionSpecification::class);
        expect($approveSpec)->toBeInstanceOf(UserPermissionSpecification::class);
        expect($donateSpec)->toBeInstanceOf(UserPermissionSpecification::class);
        expect($manageSpec)->toBeInstanceOf(UserPermissionSpecification::class);
        expect($exportSpec)->toBeInstanceOf(UserPermissionSpecification::class);
    });
});
