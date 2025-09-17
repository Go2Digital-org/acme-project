<?php

declare(strict_types=1);

use Modules\Tenancy\Domain\ValueObject\TenantId;
use Ramsey\Uuid\Uuid;

describe('TenantId Value Object', function (): void {
    describe('Constructor', function (): void {
        it('creates tenant ID with valid UUID', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId = new TenantId($uuid);

            expect($tenantId->toString())->toBe($uuid);
        });

        it('throws exception for invalid UUID format', function (): void {
            expect(fn () => new TenantId('invalid-uuid'))
                ->toThrow(InvalidArgumentException::class, 'Invalid UUID format: invalid-uuid');
        });

        it('throws exception for empty string', function (): void {
            expect(fn () => new TenantId(''))
                ->toThrow(InvalidArgumentException::class, 'Invalid UUID format: ');
        });

        it('accepts valid UUID with different formats', function (): void {
            $uuid1 = Uuid::uuid4()->toString();
            $uuid2 = strtoupper($uuid1); // Uppercase

            $tenantId1 = new TenantId($uuid1);
            $tenantId2 = new TenantId($uuid2);

            expect($tenantId1->toString())->toBe(strtolower($uuid1))
                ->and($tenantId2->toString())->toBe(strtolower($uuid2));
        });
    });

    describe('Factory Methods', function (): void {
        it('generates new tenant ID', function (): void {
            $tenantId = TenantId::generate();

            expect($tenantId->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
        });

        it('creates from string', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId = TenantId::fromString($uuid);

            expect($tenantId->toString())->toBe($uuid);
        });

        it('generates unique IDs', function (): void {
            $tenantId1 = TenantId::generate();
            $tenantId2 = TenantId::generate();

            expect($tenantId1->toString())->not->toBe($tenantId2->toString());
        });
    });

    describe('String Conversion', function (): void {
        it('implements Stringable interface', function (): void {
            $tenantId = TenantId::generate();

            expect($tenantId)->toBeInstanceOf(Stringable::class);
        });

        it('converts to string correctly', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId = new TenantId($uuid);

            expect((string) $tenantId)->toBe($uuid)
                ->and($tenantId->__toString())->toBe($uuid)
                ->and($tenantId->toString())->toBe($uuid);
        });

        it('returns short string without dashes', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId = new TenantId($uuid);
            $expectedShort = str_replace('-', '', $uuid);

            expect($tenantId->toShortString())->toBe($expectedShort)
                ->and(strlen($tenantId->toShortString()))->toBe(32)
                ->and($tenantId->toShortString())->not->toContain('-');
        });
    });

    describe('Equality', function (): void {
        it('compares tenant IDs correctly', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId1 = new TenantId($uuid);
            $tenantId2 = new TenantId($uuid);
            $tenantId3 = TenantId::generate();

            expect($tenantId1->equals($tenantId2))->toBeTrue()
                ->and($tenantId1->equals($tenantId3))->toBeFalse();
        });

        it('handles case insensitive comparison', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $tenantId1 = new TenantId($uuid);
            $tenantId2 = new TenantId(strtoupper($uuid));

            expect($tenantId1->equals($tenantId2))->toBeTrue();
        });
    });

    describe('UUID Validation', function (): void {
        it('validates UUID v4 format', function (): void {
            $tenantId = TenantId::generate();
            $uuidString = $tenantId->toString();

            // UUID v4 has '4' in the 15th character (version)
            expect($uuidString[14])->toBe('4');

            // UUID v4 has variant bits in the 20th character
            expect(in_array($uuidString[19], ['8', '9', 'a', 'b']))->toBeTrue();
        });

        it('maintains UUID format', function (): void {
            $tenantId = TenantId::generate();
            $uuidString = $tenantId->toString();

            expect(Uuid::isValid($uuidString))->toBeTrue()
                ->and(strlen($uuidString))->toBe(36)
                ->and(substr_count($uuidString, '-'))->toBe(4);
        });
    });
});
