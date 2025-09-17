<?php

declare(strict_types=1);

use Modules\User\Domain\ValueObject\UserStatus;

describe('UserStatus Value Object', function (): void {
    it('has all expected status cases', function (): void {
        $expectedCases = ['active', 'inactive', 'suspended', 'pending_verification', 'blocked'];
        $actualCases = array_map(fn (UserStatus $status) => $status->value, UserStatus::cases());

        expect($actualCases)->toBe($expectedCases);
    });

    it('returns correct labels for each status', function (): void {
        expect(UserStatus::ACTIVE->getLabel())->toBe('Active')
            ->and(UserStatus::INACTIVE->getLabel())->toBe('Inactive')
            ->and(UserStatus::SUSPENDED->getLabel())->toBe('Suspended')
            ->and(UserStatus::PENDING_VERIFICATION->getLabel())->toBe('Pending Verification')
            ->and(UserStatus::BLOCKED->getLabel())->toBe('Blocked');
    });

    it('returns correct descriptions for each status', function (): void {
        expect(UserStatus::ACTIVE->getDescription())->toBe('User account is active and can access all features.')
            ->and(UserStatus::INACTIVE->getDescription())->toBe('User account is temporarily inactive.')
            ->and(UserStatus::SUSPENDED->getDescription())->toBe('User account has been suspended due to policy violations.')
            ->and(UserStatus::PENDING_VERIFICATION->getDescription())->toBe('User account is awaiting email verification.')
            ->and(UserStatus::BLOCKED->getDescription())->toBe('User account has been permanently blocked.');
    });

    it('returns correct colors for each status', function (): void {
        expect(UserStatus::ACTIVE->getColor())->toBe('green')
            ->and(UserStatus::INACTIVE->getColor())->toBe('yellow')
            ->and(UserStatus::SUSPENDED->getColor())->toBe('orange')
            ->and(UserStatus::PENDING_VERIFICATION->getColor())->toBe('blue')
            ->and(UserStatus::BLOCKED->getColor())->toBe('red');
    });

    it('determines login permissions correctly', function (): void {
        expect(UserStatus::ACTIVE->canLogin())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->canLogin())->toBeTrue()
            ->and(UserStatus::INACTIVE->canLogin())->toBeFalse()
            ->and(UserStatus::SUSPENDED->canLogin())->toBeFalse()
            ->and(UserStatus::BLOCKED->canLogin())->toBeFalse();
    });

    it('determines campaign creation permissions correctly', function (): void {
        expect(UserStatus::ACTIVE->canCreateCampaigns())->toBeTrue()
            ->and(UserStatus::INACTIVE->canCreateCampaigns())->toBeFalse()
            ->and(UserStatus::SUSPENDED->canCreateCampaigns())->toBeFalse()
            ->and(UserStatus::PENDING_VERIFICATION->canCreateCampaigns())->toBeFalse()
            ->and(UserStatus::BLOCKED->canCreateCampaigns())->toBeFalse();
    });

    it('determines donation permissions correctly', function (): void {
        expect(UserStatus::ACTIVE->canMakeDonations())->toBeTrue()
            ->and(UserStatus::INACTIVE->canMakeDonations())->toBeFalse()
            ->and(UserStatus::SUSPENDED->canMakeDonations())->toBeFalse()
            ->and(UserStatus::PENDING_VERIFICATION->canMakeDonations())->toBeFalse()
            ->and(UserStatus::BLOCKED->canMakeDonations())->toBeFalse();
    });

    it('determines platform access permissions correctly', function (): void {
        expect(UserStatus::ACTIVE->canAccessPlatform())->toBeTrue()
            ->and(UserStatus::INACTIVE->canAccessPlatform())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->canAccessPlatform())->toBeTrue()
            ->and(UserStatus::SUSPENDED->canAccessPlatform())->toBeFalse()
            ->and(UserStatus::BLOCKED->canAccessPlatform())->toBeFalse();
    });

    it('determines if status requires action', function (): void {
        expect(UserStatus::PENDING_VERIFICATION->requiresAction())->toBeTrue()
            ->and(UserStatus::SUSPENDED->requiresAction())->toBeTrue()
            ->and(UserStatus::BLOCKED->requiresAction())->toBeTrue()
            ->and(UserStatus::ACTIVE->requiresAction())->toBeFalse()
            ->and(UserStatus::INACTIVE->requiresAction())->toBeFalse();
    });

    it('determines if status is temporary', function (): void {
        expect(UserStatus::INACTIVE->isTemporary())->toBeTrue()
            ->and(UserStatus::SUSPENDED->isTemporary())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->isTemporary())->toBeTrue()
            ->and(UserStatus::ACTIVE->isTemporary())->toBeFalse()
            ->and(UserStatus::BLOCKED->isTemporary())->toBeFalse();
    });

    it('determines if status is permanent', function (): void {
        expect(UserStatus::ACTIVE->isPermanent())->toBeTrue()
            ->and(UserStatus::BLOCKED->isPermanent())->toBeTrue()
            ->and(UserStatus::INACTIVE->isPermanent())->toBeFalse()
            ->and(UserStatus::SUSPENDED->isPermanent())->toBeFalse()
            ->and(UserStatus::PENDING_VERIFICATION->isPermanent())->toBeFalse();
    });

    it('returns select options as array', function (): void {
        $options = UserStatus::getSelectOptions();

        expect($options)->toBeArray()
            ->and($options)->toHaveCount(5)
            ->and($options['active'])->toBe('Active')
            ->and($options['inactive'])->toBe('Inactive')
            ->and($options['suspended'])->toBe('Suspended')
            ->and($options['pending_verification'])->toBe('Pending Verification')
            ->and($options['blocked'])->toBe('Blocked');
    });

    it('returns active statuses', function (): void {
        $activeStatuses = UserStatus::getActiveStatuses();

        expect($activeStatuses)->toBeArray()
            ->and($activeStatuses)->toHaveCount(1)
            ->and($activeStatuses[0])->toBe(UserStatus::ACTIVE);
    });

    it('returns inactive statuses', function (): void {
        $inactiveStatuses = UserStatus::getInactiveStatuses();

        expect($inactiveStatuses)->toBeArray()
            ->and($inactiveStatuses)->toHaveCount(3)
            ->and($inactiveStatuses)->toContain(UserStatus::INACTIVE)
            ->and($inactiveStatuses)->toContain(UserStatus::SUSPENDED)
            ->and($inactiveStatuses)->toContain(UserStatus::BLOCKED);
    });

    it('returns pending statuses', function (): void {
        $pendingStatuses = UserStatus::getPendingStatuses();

        expect($pendingStatuses)->toBeArray()
            ->and($pendingStatuses)->toHaveCount(1)
            ->and($pendingStatuses[0])->toBe(UserStatus::PENDING_VERIFICATION);
    });

    it('can be created from string value', function (): void {
        expect(UserStatus::from('active'))->toBe(UserStatus::ACTIVE)
            ->and(UserStatus::from('inactive'))->toBe(UserStatus::INACTIVE)
            ->and(UserStatus::from('suspended'))->toBe(UserStatus::SUSPENDED)
            ->and(UserStatus::from('pending_verification'))->toBe(UserStatus::PENDING_VERIFICATION)
            ->and(UserStatus::from('blocked'))->toBe(UserStatus::BLOCKED);
    });

    it('throws exception for invalid string value', function (): void {
        expect(fn () => UserStatus::from('invalid_status'))
            ->toThrow(ValueError::class);
    });

    it('supports try from for safe creation', function (): void {
        expect(UserStatus::tryFrom('active'))->toBe(UserStatus::ACTIVE)
            ->and(UserStatus::tryFrom('invalid_status'))->toBeNull();
    });

    it('is serializable', function (): void {
        $status = UserStatus::ACTIVE;
        $serialized = serialize($status);
        $unserialized = unserialize($serialized);

        expect($unserialized)->toBe(UserStatus::ACTIVE)
            ->and($unserialized->value)->toBe('active');
    });

    it('validates permission consistency across all methods', function (): void {
        foreach (UserStatus::cases() as $status) {
            $canLogin = $status->canLogin();
            $canCreateCampaigns = $status->canCreateCampaigns();
            $canMakeDonations = $status->canMakeDonations();
            $canAccessPlatform = $status->canAccessPlatform();

            // Business rule: Can't create campaigns or donate without being able to login
            if ($canCreateCampaigns) {
                expect($canLogin)->toBeTrue();
            }

            if ($canMakeDonations) {
                expect($canLogin)->toBeTrue();
            }

            // Business rule: Only ACTIVE users can create campaigns or make donations
            if ($status !== UserStatus::ACTIVE) {
                expect($canCreateCampaigns)->toBeFalse();
                expect($canMakeDonations)->toBeFalse();
            }

            // All boolean methods should return booleans
            expect($canLogin)->toBeBool()
                ->and($canCreateCampaigns)->toBeBool()
                ->and($canMakeDonations)->toBeBool()
                ->and($canAccessPlatform)->toBeBool()
                ->and($status->requiresAction())->toBeBool()
                ->and($status->isTemporary())->toBeBool()
                ->and($status->isPermanent())->toBeBool();
        }
    });

    it('validates status transitions and business logic', function (): void {
        // BLOCKED users cannot access anything
        expect(UserStatus::BLOCKED->canLogin())->toBeFalse()
            ->and(UserStatus::BLOCKED->canCreateCampaigns())->toBeFalse()
            ->and(UserStatus::BLOCKED->canMakeDonations())->toBeFalse()
            ->and(UserStatus::BLOCKED->canAccessPlatform())->toBeFalse()
            ->and(UserStatus::BLOCKED->isPermanent())->toBeTrue()
            ->and(UserStatus::BLOCKED->isTemporary())->toBeFalse();

        // ACTIVE users have full permissions
        expect(UserStatus::ACTIVE->canLogin())->toBeTrue()
            ->and(UserStatus::ACTIVE->canCreateCampaigns())->toBeTrue()
            ->and(UserStatus::ACTIVE->canMakeDonations())->toBeTrue()
            ->and(UserStatus::ACTIVE->canAccessPlatform())->toBeTrue()
            ->and(UserStatus::ACTIVE->requiresAction())->toBeFalse();

        // SUSPENDED users need admin action
        expect(UserStatus::SUSPENDED->requiresAction())->toBeTrue()
            ->and(UserStatus::SUSPENDED->isTemporary())->toBeTrue()
            ->and(UserStatus::SUSPENDED->canAccessPlatform())->toBeFalse();

        // PENDING_VERIFICATION users can login but limited actions
        expect(UserStatus::PENDING_VERIFICATION->canLogin())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->canAccessPlatform())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->requiresAction())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->isTemporary())->toBeTrue();
    });

    it('validates metadata completeness for all statuses', function (): void {
        foreach (UserStatus::cases() as $status) {
            $label = $status->getLabel();
            $description = $status->getDescription();
            $color = $status->getColor();

            expect($label)->toBeString()
                ->and(strlen($label))->toBeGreaterThan(3)
                ->and($label)->not()->toBeEmpty()
                ->and($description)->toBeString()
                ->and(strlen($description))->toBeGreaterThan(10)
                ->and($description)->not()->toBeEmpty()
                ->and($color)->toBeString()
                ->and(strlen($color))->toBeGreaterThan(2)
                ->and($color)->not()->toBeEmpty();

            // Label should be title case
            expect($label[0])->toBe(strtoupper($label[0]));

            // Description should be a proper sentence
            expect($description)->toEndWith('.');
        }
    });

    it('validates status grouping methods return correct sets', function (): void {
        $allStatuses = UserStatus::cases();
        $activeStatuses = UserStatus::getActiveStatuses();
        $inactiveStatuses = UserStatus::getInactiveStatuses();
        $pendingStatuses = UserStatus::getPendingStatuses();

        // All groupings should be subsets of all statuses
        foreach ($activeStatuses as $status) {
            expect($allStatuses)->toContain($status);
        }

        foreach ($inactiveStatuses as $status) {
            expect($allStatuses)->toContain($status);
        }

        foreach ($pendingStatuses as $status) {
            expect($allStatuses)->toContain($status);
        }

        // No overlap between active and inactive
        foreach ($activeStatuses as $status) {
            expect($inactiveStatuses)->not->toContain($status);
        }

        // All statuses should be in exactly one group
        $totalGrouped = count($activeStatuses) + count($inactiveStatuses) + count($pendingStatuses);
        expect($totalGrouped)->toBe(count($allStatuses));
    });

    it('validates color scheme consistency', function (): void {
        $validColors = ['green', 'yellow', 'orange', 'blue', 'red', 'gray', 'purple', 'indigo'];
        $statusColors = [];

        foreach (UserStatus::cases() as $status) {
            $color = $status->getColor();
            expect($validColors)->toContain($color);
            $statusColors[$status->value] = $color;
        }

        // Specific color expectations based on status meaning
        expect($statusColors['active'])->toBe('green') // Green for positive
            ->and($statusColors['blocked'])->toBe('red') // Red for danger
            ->and(in_array($statusColors['suspended'], ['orange', 'red'], true))->toBeTrue() // Warning colors
            ->and(in_array($statusColors['pending_verification'], ['blue', 'yellow'], true))->toBeTrue(); // Info colors
    });

    it('handles edge cases and error conditions', function (): void {
        // Test enum creation with edge cases
        expect(fn () => UserStatus::from(''))->toThrow(ValueError::class);
        expect(fn () => UserStatus::from('ACTIVE'))->toThrow(ValueError::class); // Wrong case
        expect(fn () => UserStatus::from('invalid'))->toThrow(ValueError::class);
        expect(fn () => UserStatus::from(null))->toThrow(TypeError::class); // @phpstan-ignore-line

        // Test tryFrom with edge cases
        expect(UserStatus::tryFrom(''))->toBeNull()
            ->and(UserStatus::tryFrom('invalid'))->toBeNull()
            ->and(UserStatus::tryFrom('ACTIVE'))->toBeNull(); // Wrong case

        // Test tryFrom with null (should throw TypeError due to strict typing)
        expect(fn () => UserStatus::tryFrom(null))->toThrow(TypeError::class); // @phpstan-ignore-line

        // Test valid tryFrom cases
        expect(UserStatus::tryFrom('active'))->toBe(UserStatus::ACTIVE)
            ->and(UserStatus::tryFrom('blocked'))->toBe(UserStatus::BLOCKED);
    });

    it('validates enum immutability and consistency', function (): void {
        $status1 = UserStatus::ACTIVE;
        $status2 = UserStatus::ACTIVE;

        // Same enum case should be identical
        expect($status1)->toBe($status2);

        // Verify immutability - properties should be consistent
        $label1 = $status1->getLabel();
        $label2 = $status1->getLabel();
        expect($label1)->toBe($label2);

        $color1 = $status1->getColor();
        $color2 = $status1->getColor();
        expect($color1)->toBe($color2);

        // Verify enum values are consistent
        expect($status1->value)->toBe('active')
            ->and($status1->name)->toBe('ACTIVE');
    });

    it('validates select options completeness and ordering', function (): void {
        $options = UserStatus::getSelectOptions();
        $allCases = UserStatus::cases();

        // Should include all enum cases
        expect($options)->toHaveCount(count($allCases));

        foreach ($allCases as $case) {
            expect($options)->toHaveKey($case->value)
                ->and($options[$case->value])->toBe($case->getLabel());
        }

        // Should maintain consistent key-value mapping
        $firstCall = UserStatus::getSelectOptions();
        $secondCall = UserStatus::getSelectOptions();
        expect($firstCall)->toBe($secondCall);
    });

    it('validates business rules for user workflow states', function (): void {
        // New user workflow: PENDING_VERIFICATION -> ACTIVE
        expect(UserStatus::PENDING_VERIFICATION->canAccessPlatform())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->canLogin())->toBeTrue()
            ->and(UserStatus::PENDING_VERIFICATION->requiresAction())->toBeTrue();

        // Moderation workflow: ACTIVE -> SUSPENDED -> (ACTIVE or BLOCKED)
        expect(UserStatus::SUSPENDED->isTemporary())->toBeTrue()
            ->and(UserStatus::SUSPENDED->requiresAction())->toBeTrue()
            ->and(UserStatus::BLOCKED->isTemporary())->toBeFalse();

        // Administrative states should prevent access
        $restrictiveStatuses = [UserStatus::SUSPENDED, UserStatus::BLOCKED];

        foreach ($restrictiveStatuses as $status) {
            expect($status->canCreateCampaigns())->toBeFalse()
                ->and($status->canMakeDonations())->toBeFalse();
        }
    });

    it('validates method return types and null safety', function (): void {
        foreach (UserStatus::cases() as $status) {
            // All methods should return non-null values
            expect($status->getLabel())->not->toBeNull()
                ->and($status->getDescription())->not->toBeNull()
                ->and($status->getColor())->not->toBeNull()
                ->and($status->canLogin())->not->toBeNull()
                ->and($status->canCreateCampaigns())->not->toBeNull()
                ->and($status->canMakeDonations())->not->toBeNull()
                ->and($status->canAccessPlatform())->not->toBeNull()
                ->and($status->requiresAction())->not->toBeNull()
                ->and($status->isTemporary())->not->toBeNull()
                ->and($status->isPermanent())->not->toBeNull();
        }

        // Static methods should return arrays
        expect(UserStatus::getSelectOptions())->toBeArray()
            ->and(UserStatus::getActiveStatuses())->toBeArray()
            ->and(UserStatus::getInactiveStatuses())->toBeArray()
            ->and(UserStatus::getPendingStatuses())->toBeArray();
    });
});
