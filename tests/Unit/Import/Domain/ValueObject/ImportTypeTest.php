<?php

declare(strict_types=1);

use Modules\Import\Domain\ValueObject\ImportType;

describe('ImportType', function () {
    describe('static constructors', function () {
        it('creates campaigns type', function () {
            $type = ImportType::campaigns();

            expect($type->value())->toBe('campaigns')
                ->and($type->isCampaigns())->toBeTrue()
                ->and((string) $type)->toBe('campaigns');
        });

        it('creates donations type', function () {
            $type = ImportType::donations();

            expect($type->value())->toBe('donations')
                ->and($type->isDonations())->toBeTrue()
                ->and((string) $type)->toBe('donations');
        });

        it('creates organizations type', function () {
            $type = ImportType::organizations();

            expect($type->value())->toBe('organizations')
                ->and($type->isOrganizations())->toBeTrue()
                ->and((string) $type)->toBe('organizations');
        });

        it('creates users type', function () {
            $type = ImportType::users();

            expect($type->value())->toBe('users')
                ->and($type->isUsers())->toBeTrue()
                ->and((string) $type)->toBe('users');
        });

        it('creates employees type', function () {
            $type = ImportType::employees();

            expect($type->value())->toBe('employees')
                ->and($type->isEmployees())->toBeTrue()
                ->and((string) $type)->toBe('employees');
        });
    });

    describe('constructor with valid types', function () {
        it('accepts campaigns type', function () {
            $type = new ImportType('campaigns');

            expect($type->value())->toBe('campaigns')
                ->and($type->isCampaigns())->toBeTrue();
        });

        it('accepts donations type', function () {
            $type = new ImportType('donations');

            expect($type->value())->toBe('donations')
                ->and($type->isDonations())->toBeTrue();
        });

        it('accepts organizations type', function () {
            $type = new ImportType('organizations');

            expect($type->value())->toBe('organizations')
                ->and($type->isOrganizations())->toBeTrue();
        });

        it('accepts users type', function () {
            $type = new ImportType('users');

            expect($type->value())->toBe('users')
                ->and($type->isUsers())->toBeTrue();
        });

        it('accepts employees type', function () {
            $type = new ImportType('employees');

            expect($type->value())->toBe('employees')
                ->and($type->isEmployees())->toBeTrue();
        });
    });

    describe('constructor validation', function () {
        it('throws exception for invalid type', function () {
            expect(fn () => new ImportType('invalid'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new ImportType('invalid'))
                ->toThrow('Invalid import type "invalid"');
        });

        it('throws exception for empty string', function () {
            expect(fn () => new ImportType(''))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws exception for case-sensitive mismatch', function () {
            expect(fn () => new ImportType('Campaigns'))
                ->toThrow(InvalidArgumentException::class)
                ->and(fn () => new ImportType('CAMPAIGNS'))
                ->toThrow(InvalidArgumentException::class);
        });

        it('includes allowed types in error message', function () {
            try {
                new ImportType('invalid');
            } catch (InvalidArgumentException $e) {
                expect($e->getMessage())
                    ->toContain('campaigns')
                    ->and($e->getMessage())->toContain('donations')
                    ->and($e->getMessage())->toContain('organizations')
                    ->and($e->getMessage())->toContain('users')
                    ->and($e->getMessage())->toContain('employees');
            }
        });
    });

    describe('type checking methods', function () {
        it('correctly identifies campaigns', function () {
            $campaigns = ImportType::campaigns();

            expect($campaigns->isCampaigns())->toBeTrue()
                ->and($campaigns->isDonations())->toBeFalse()
                ->and($campaigns->isOrganizations())->toBeFalse()
                ->and($campaigns->isUsers())->toBeFalse()
                ->and($campaigns->isEmployees())->toBeFalse();
        });

        it('correctly identifies donations', function () {
            $donations = ImportType::donations();

            expect($donations->isCampaigns())->toBeFalse()
                ->and($donations->isDonations())->toBeTrue()
                ->and($donations->isOrganizations())->toBeFalse()
                ->and($donations->isUsers())->toBeFalse()
                ->and($donations->isEmployees())->toBeFalse();
        });

        it('correctly identifies organizations', function () {
            $organizations = ImportType::organizations();

            expect($organizations->isCampaigns())->toBeFalse()
                ->and($organizations->isDonations())->toBeFalse()
                ->and($organizations->isOrganizations())->toBeTrue()
                ->and($organizations->isUsers())->toBeFalse()
                ->and($organizations->isEmployees())->toBeFalse();
        });

        it('correctly identifies users', function () {
            $users = ImportType::users();

            expect($users->isCampaigns())->toBeFalse()
                ->and($users->isDonations())->toBeFalse()
                ->and($users->isOrganizations())->toBeFalse()
                ->and($users->isUsers())->toBeTrue()
                ->and($users->isEmployees())->toBeFalse();
        });

        it('correctly identifies employees', function () {
            $employees = ImportType::employees();

            expect($employees->isCampaigns())->toBeFalse()
                ->and($employees->isDonations())->toBeFalse()
                ->and($employees->isOrganizations())->toBeFalse()
                ->and($employees->isUsers())->toBeFalse()
                ->and($employees->isEmployees())->toBeTrue();
        });
    });

    describe('equals method', function () {
        it('returns true for same types', function () {
            $type1 = ImportType::campaigns();
            $type2 = ImportType::campaigns();

            expect($type1->equals($type2))->toBeTrue()
                ->and($type2->equals($type1))->toBeTrue();
        });

        it('returns false for different types', function () {
            $campaigns = ImportType::campaigns();
            $donations = ImportType::donations();

            expect($campaigns->equals($donations))->toBeFalse()
                ->and($donations->equals($campaigns))->toBeFalse();
        });

        it('works with constructed instances', function () {
            $type1 = new ImportType('users');
            $type2 = ImportType::users();

            expect($type1->equals($type2))->toBeTrue()
                ->and($type2->equals($type1))->toBeTrue();
        });
    });

    describe('string conversion', function () {
        it('implements Stringable interface', function () {
            $type = ImportType::campaigns();

            expect($type)->toBeInstanceOf(Stringable::class);
        });

        it('converts to string correctly', function () {
            expect((string) ImportType::campaigns())->toBe('campaigns')
                ->and((string) ImportType::donations())->toBe('donations')
                ->and((string) ImportType::organizations())->toBe('organizations')
                ->and((string) ImportType::users())->toBe('users')
                ->and((string) ImportType::employees())->toBe('employees');
        });

        it('value method returns same as __toString', function () {
            $type = ImportType::campaigns();

            expect($type->value())->toBe((string) $type);
        });
    });

    describe('readonly behavior', function () {
        it('is readonly class', function () {
            $reflection = new ReflectionClass(ImportType::class);

            expect($reflection->isReadOnly())->toBeTrue();
        });

        it('maintains value integrity', function () {
            $type = ImportType::campaigns();

            expect($type->value())->toBe('campaigns')
                ->and($type->value())->toBe('campaigns'); // Second call should return same value
        });
    });
});
