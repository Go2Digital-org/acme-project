<?php

declare(strict_types=1);

use Modules\Tenancy\Domain\ValueObject\TenantDatabase;
use Modules\Tenancy\Domain\ValueObject\TenantId;
use Ramsey\Uuid\Uuid;

describe('TenantDatabase Value Object', function (): void {
    describe('Constructor', function (): void {
        it('creates tenant database with valid name', function (): void {
            $database = new TenantDatabase('tenant_acme_corp');

            expect($database->getValue())->toBe('tenant_acme_corp')
                ->and($database->toString())->toBe('tenant_acme_corp');
        });

        it('throws exception for empty name', function (): void {
            expect(fn () => new TenantDatabase(''))
                ->toThrow(InvalidArgumentException::class, 'Database name cannot be empty');
        });

        it('throws exception for zero string name', function (): void {
            expect(fn () => new TenantDatabase('0'))
                ->toThrow(InvalidArgumentException::class, 'Database name cannot be empty');
        });

        it('throws exception for name too long', function (): void {
            $longName = str_repeat('a', 65);
            expect(fn () => new TenantDatabase($longName))
                ->toThrow(InvalidArgumentException::class, 'Database name cannot exceed 64 characters');
        });

        it('throws exception for invalid characters', function (): void {
            expect(fn () => new TenantDatabase('tenant-name'))
                ->toThrow(InvalidArgumentException::class, 'Database name can only contain alphanumeric characters and underscores')
                ->and(fn () => new TenantDatabase('tenant.name'))
                ->toThrow(InvalidArgumentException::class, 'Database name can only contain alphanumeric characters and underscores')
                ->and(fn () => new TenantDatabase('tenant name'))
                ->toThrow(InvalidArgumentException::class, 'Database name can only contain alphanumeric characters and underscores');
        });

        it('throws exception for name starting with number', function (): void {
            expect(fn () => new TenantDatabase('1tenant_name'))
                ->toThrow(InvalidArgumentException::class, 'Database name cannot start with a number');
        });
    });

    describe('Valid Names', function (): void {
        it('accepts names with underscores', function (): void {
            $database = new TenantDatabase('tenant_organization_name');
            expect($database->getValue())->toBe('tenant_organization_name');
        });

        it('accepts names with numbers (not at start)', function (): void {
            $database = new TenantDatabase('tenant_org123');
            expect($database->getValue())->toBe('tenant_org123');
        });

        it('accepts simple names', function (): void {
            $database = new TenantDatabase('acmecorp');
            expect($database->getValue())->toBe('acmecorp');
        });

        it('accepts maximum length names', function (): void {
            $maxName = 'tenant_' . str_repeat('a', 56); // 64 chars total
            $database = new TenantDatabase($maxName);
            expect($database->getValue())->toBe($maxName);
        });
    });

    describe('Factory Methods', function (): void {
        it('creates from tenant ID', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId = new TenantId($uuid);
            $database = TenantDatabase::fromTenantId($tenantId);

            $expectedName = 'tenant_' . str_replace('-', '_', $uuid);
            expect($database->getValue())->toBe($expectedName);
        });

        it('creates from string', function (): void {
            $database = TenantDatabase::fromString('test_tenant_db');

            expect($database->getValue())->toBe('test_tenant_db');
        });

        it('fromString validates the name', function (): void {
            expect(fn () => TenantDatabase::fromString('invalid-name'))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('String Conversion', function (): void {
        it('implements Stringable interface', function (): void {
            $database = new TenantDatabase('test_tenant');

            expect($database)->toBeInstanceOf(Stringable::class);
        });

        it('converts to string correctly', function (): void {
            $database = new TenantDatabase('tenant_example');

            expect((string) $database)->toBe('tenant_example')
                ->and($database->__toString())->toBe('tenant_example')
                ->and($database->toString())->toBe('tenant_example');
        });

        it('all string methods return same value', function (): void {
            $database = new TenantDatabase('test_db');

            expect($database->getValue())->toBe($database->toString())
                ->and($database->toString())->toBe($database->__toString());
        });
    });

    describe('UUID Handling in fromTenantId', function (): void {
        it('replaces hyphens with underscores', function (): void {
            $uuid = '123e4567-e89b-12d3-a456-426614174000';
            $tenantId = new TenantId($uuid);
            $database = TenantDatabase::fromTenantId($tenantId);

            expect($database->getValue())->toBe('tenant_123e4567_e89b_12d3_a456_426614174000');
        });

        it('handles different UUID formats', function (): void {
            $uuid1 = Uuid::uuid4()->toString();
            $uuid2 = strtoupper($uuid1);

            $tenantId1 = new TenantId($uuid1);
            $tenantId2 = new TenantId($uuid2);

            $db1 = TenantDatabase::fromTenantId($tenantId1);
            $db2 = TenantDatabase::fromTenantId($tenantId2);

            // Both should result in the same database name (lowercase)
            expect($db1->getValue())->toBe($db2->getValue());
        });
    });

    describe('Edge Cases', function (): void {
        it('accepts single character names', function (): void {
            $database = new TenantDatabase('a');
            expect($database->getValue())->toBe('a');
        });

        it('accepts names with mixed case', function (): void {
            $database = new TenantDatabase('TenantDatabase123');
            expect($database->getValue())->toBe('TenantDatabase123');
        });
    });
});
