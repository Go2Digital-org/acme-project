<?php

declare(strict_types=1);

use Modules\Export\Domain\ValueObject\ExportId;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

describe('ExportId Value Object', function (): void {
    describe('Factory Methods', function (): void {
        it('generates new export ID', function (): void {
            $exportId = ExportId::generate();

            expect($exportId->toString())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
        });

        it('creates from string', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $exportId = ExportId::fromString($uuid);

            expect($exportId->toString())->toBe($uuid);
        });

        it('creates from UUID interface', function (): void {
            $uuid = Uuid::uuid4();
            $exportId = ExportId::fromUuid($uuid);

            expect($exportId->toString())->toBe($uuid->toString());
        });

        it('throws exception for invalid UUID string', function (): void {
            expect(fn () => ExportId::fromString('invalid-uuid'))
                ->toThrow(InvalidArgumentException::class, 'Invalid UUID format: invalid-uuid');
        });

        it('throws exception for empty string', function (): void {
            expect(fn () => ExportId::fromString(''))
                ->toThrow(InvalidArgumentException::class, 'Invalid UUID format: ');
        });

        it('generates unique IDs', function (): void {
            $exportId1 = ExportId::generate();
            $exportId2 = ExportId::generate();

            expect($exportId1->toString())->not->toBe($exportId2->toString());
        });
    });

    describe('String Conversion', function (): void {
        it('implements Stringable interface', function (): void {
            $exportId = ExportId::generate();

            expect($exportId)->toBeInstanceOf(Stringable::class);
        });

        it('converts to string correctly', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $exportId = ExportId::fromString($uuid);

            expect((string) $exportId)->toBe($uuid)
                ->and($exportId->__toString())->toBe($uuid)
                ->and($exportId->toString())->toBe($uuid);
        });

        it('toString and __toString return same value', function (): void {
            $exportId = ExportId::generate();

            expect($exportId->toString())->toBe($exportId->__toString());
        });
    });

    describe('Equality', function (): void {
        it('compares export IDs correctly', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $exportId1 = ExportId::fromString($uuid);
            $exportId2 = ExportId::fromString($uuid);
            $exportId3 = ExportId::generate();

            expect($exportId1->equals($exportId2))->toBeTrue()
                ->and($exportId1->equals($exportId3))->toBeFalse();
        });

        it('compares with UUID created differently', function (): void {
            $uuid = Uuid::uuid4();
            $exportId1 = ExportId::fromUuid($uuid);
            $exportId2 = ExportId::fromString($uuid->toString());

            expect($exportId1->equals($exportId2))->toBeTrue();
        });

        it('handles case insensitive comparison', function (): void {
            $uuid = Uuid::uuid4()->toString();
            $exportId1 = ExportId::fromString($uuid);
            $exportId2 = ExportId::fromString(strtoupper($uuid));

            expect($exportId1->equals($exportId2))->toBeTrue();
        });
    });

    describe('UUID Properties', function (): void {
        it('has access to underlying UUID value', function (): void {
            $exportId = ExportId::generate();

            expect($exportId->value)->toBeInstanceOf(UuidInterface::class);
        });

        it('validates UUID v4 format', function (): void {
            $exportId = ExportId::generate();
            $uuidString = $exportId->toString();

            // UUID v4 has '4' in the 15th character (version)
            expect($uuidString[14])->toBe('4');

            // UUID v4 has variant bits in the 20th character
            expect(in_array($uuidString[19], ['8', '9', 'a', 'b']))->toBeTrue();
        });

        it('maintains UUID format', function (): void {
            $exportId = ExportId::generate();
            $uuidString = $exportId->toString();

            expect(Uuid::isValid($uuidString))->toBeTrue()
                ->and(strlen($uuidString))->toBe(36)
                ->and(substr_count($uuidString, '-'))->toBe(4);
        });
    });

    describe('Immutability', function (): void {
        it('is readonly and immutable', function (): void {
            $exportId = ExportId::generate();
            $originalString = $exportId->toString();

            // Call methods that shouldn't modify the object
            $exportId->toString();
            $exportId->__toString();
            $exportId->equals(ExportId::generate());

            expect($exportId->toString())->toBe($originalString);
        });
    });
});
