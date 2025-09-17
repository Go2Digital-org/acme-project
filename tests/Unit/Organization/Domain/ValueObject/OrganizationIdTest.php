<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObject\OrganizationId;

describe('OrganizationId', function () {
    describe('construction', function () {
        it('creates valid organization id with positive integer', function () {
            $id = new OrganizationId(1);

            expect($id->value)->toBe(1)
                ->and($id)->toBeInstanceOf(OrganizationId::class)
                ->and($id)->toBeInstanceOf(Stringable::class);
        });

        it('creates valid organization id with large positive integer', function () {
            $id = new OrganizationId(999999);

            expect($id->value)->toBe(999999);
        });

        it('creates valid organization id with medium range integer', function () {
            $id = new OrganizationId(12345);

            expect($id->value)->toBe(12345);
        });

        it('throws exception for zero value', function () {
            expect(fn () => new OrganizationId(0))
                ->toThrow(InvalidArgumentException::class, 'Organization ID must be a positive integer');
        });

        it('throws exception for negative value', function () {
            expect(fn () => new OrganizationId(-1))
                ->toThrow(InvalidArgumentException::class, 'Organization ID must be a positive integer');
        });

        it('throws exception for large negative value', function () {
            expect(fn () => new OrganizationId(-999))
                ->toThrow(InvalidArgumentException::class, 'Organization ID must be a positive integer');
        });

        it('throws exception for minimum negative integer', function () {
            expect(fn () => new OrganizationId(PHP_INT_MIN))
                ->toThrow(InvalidArgumentException::class, 'Organization ID must be a positive integer');
        });
    });

    describe('fromInt factory method', function () {
        it('creates organization id from positive integer', function () {
            $id = OrganizationId::fromInt(42);

            expect($id)->toBeInstanceOf(OrganizationId::class)
                ->and($id->value)->toBe(42);
        });

        it('creates organization id from one', function () {
            $id = OrganizationId::fromInt(1);

            expect($id->value)->toBe(1);
        });

        it('creates organization id from maximum safe integer', function () {
            $maxSafeInt = 9007199254740991; // JavaScript MAX_SAFE_INTEGER equivalent
            $id = OrganizationId::fromInt($maxSafeInt);

            expect($id->value)->toBe($maxSafeInt);
        });

        it('creates organization id from php maximum integer', function () {
            $id = OrganizationId::fromInt(PHP_INT_MAX);

            expect($id->value)->toBe(PHP_INT_MAX);
        });

        it('throws exception when creating from zero', function () {
            expect(fn () => OrganizationId::fromInt(0))
                ->toThrow(InvalidArgumentException::class, 'Organization ID must be a positive integer');
        });

        it('throws exception when creating from negative', function () {
            expect(fn () => OrganizationId::fromInt(-5))
                ->toThrow(InvalidArgumentException::class, 'Organization ID must be a positive integer');
        });
    });

    describe('equals comparison', function () {
        it('returns true for equal organization ids', function () {
            $id1 = new OrganizationId(123);
            $id2 = new OrganizationId(123);

            expect($id1->equals($id2))->toBeTrue()
                ->and($id2->equals($id1))->toBeTrue();
        });

        it('returns false for different organization ids', function () {
            $id1 = new OrganizationId(123);
            $id2 = new OrganizationId(456);

            expect($id1->equals($id2))->toBeFalse()
                ->and($id2->equals($id1))->toBeFalse();
        });

        it('returns true for same instance', function () {
            $id = new OrganizationId(789);

            expect($id->equals($id))->toBeTrue();
        });

        it('returns true for equal large values', function () {
            $id1 = new OrganizationId(999999999);
            $id2 = new OrganizationId(999999999);

            expect($id1->equals($id2))->toBeTrue();
        });

        it('returns false for small difference', function () {
            $id1 = new OrganizationId(100);
            $id2 = new OrganizationId(101);

            expect($id1->equals($id2))->toBeFalse();
        });

        it('returns false for minimal and maximal values', function () {
            $id1 = new OrganizationId(1);
            $id2 = new OrganizationId(PHP_INT_MAX);

            expect($id1->equals($id2))->toBeFalse();
        });
    });

    describe('toInt conversion', function () {
        it('returns integer value for small id', function () {
            $id = new OrganizationId(42);

            expect($id->toInt())->toBe(42)
                ->and($id->toInt())->toBeInt();
        });

        it('returns integer value for large id', function () {
            $id = new OrganizationId(9876543);

            expect($id->toInt())->toBe(9876543)
                ->and($id->toInt())->toBeInt();
        });

        it('returns one for minimal valid id', function () {
            $id = new OrganizationId(1);

            expect($id->toInt())->toBe(1);
        });

        it('returns maximum integer for maximum id', function () {
            $id = new OrganizationId(PHP_INT_MAX);

            expect($id->toInt())->toBe(PHP_INT_MAX);
        });

        it('maintains value consistency', function () {
            $originalValue = 555555;
            $id = new OrganizationId($originalValue);

            expect($id->toInt())->toBe($originalValue)
                ->and($id->value)->toBe($originalValue)
                ->and($id->toInt())->toBe($id->value);
        });
    });

    describe('string conversion', function () {
        it('converts to string representation', function () {
            $id = new OrganizationId(123);

            expect((string) $id)->toBe('123')
                ->and($id->__toString())->toBe('123')
                ->and((string) $id)->toBeString();
        });

        it('converts large numbers to string', function () {
            $id = new OrganizationId(987654321);

            expect((string) $id)->toBe('987654321')
                ->and($id->__toString())->toBe('987654321');
        });

        it('converts one to string', function () {
            $id = new OrganizationId(1);

            expect((string) $id)->toBe('1');
        });

        it('converts maximum integer to string', function () {
            $id = new OrganizationId(PHP_INT_MAX);
            $expectedString = (string) PHP_INT_MAX;

            expect((string) $id)->toBe($expectedString)
                ->and($id->__toString())->toBe($expectedString);
        });

        it('maintains string consistency', function () {
            $id = new OrganizationId(777);
            $stringValue = (string) $id;

            expect($stringValue)->toBe($id->__toString())
                ->and($stringValue)->toBe((string) $id->value)
                ->and(strlen($stringValue))->toBe(3);
        });
    });

    describe('value immutability', function () {
        it('maintains readonly value property', function () {
            $id = new OrganizationId(100);
            $value = $id->value;

            expect($value)->toBe(100)
                ->and($id->value)->toBe($value);

            // Value should remain unchanged
            expect($id->value)->toBe(100);
        });

        it('creates separate instances with different values', function () {
            $id1 = new OrganizationId(100);
            $id2 = new OrganizationId(200);

            expect($id1->value)->toBe(100)
                ->and($id2->value)->toBe(200)
                ->and($id1->value)->not->toBe($id2->value);
        });
    });

    describe('edge cases and boundary conditions', function () {
        it('handles consecutive values correctly', function () {
            $id1 = new OrganizationId(999);
            $id2 = new OrganizationId(1000);

            expect($id1->value)->toBe(999)
                ->and($id2->value)->toBe(1000)
                ->and($id1->equals($id2))->toBeFalse()
                ->and($id2->toInt() - $id1->toInt())->toBe(1);
        });

        it('works with powers of two', function () {
            $id1 = new OrganizationId(1024);
            $id2 = new OrganizationId(2048);

            expect($id1->value)->toBe(1024)
                ->and($id2->value)->toBe(2048)
                ->and($id1->toInt())->toBe(1024)
                ->and($id2->toInt())->toBe(2048);
        });

        it('handles scientific notation equivalent values', function () {
            $scientificValue = (int) 1e6; // 1,000,000
            $id = new OrganizationId($scientificValue);

            expect($id->value)->toBe(1000000)
                ->and($id->toInt())->toBe(1000000)
                ->and((string) $id)->toBe('1000000');
        });
    });

    describe('type safety and interface compliance', function () {
        it('implements Stringable interface', function () {
            $id = new OrganizationId(456);

            expect($id)->toBeInstanceOf(Stringable::class);
        });

        it('provides consistent type information', function () {
            $id = new OrganizationId(789);

            expect(is_object($id))->toBeTrue()
                ->and($id instanceof OrganizationId)->toBeTrue()
                ->and($id instanceof Stringable)->toBeTrue();
        });

        it('maintains type consistency across operations', function () {
            $id = OrganizationId::fromInt(321);
            $converted = $id->toInt();
            $stringified = (string) $id;

            expect($id)->toBeInstanceOf(OrganizationId::class)
                ->and($converted)->toBeInt()
                ->and($stringified)->toBeString()
                ->and($converted)->toBe(321)
                ->and($stringified)->toBe('321');
        });
    });
});
