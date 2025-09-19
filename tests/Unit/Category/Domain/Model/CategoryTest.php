<?php

declare(strict_types=1);

use Modules\Category\Domain\ValueObject\CategoryStatus;

// Simple pure domain logic tests for Category value objects and basic behavior
describe('Category Status Domain Logic', function (): void {
    it('identifies active status correctly', function (): void {
        $activeStatus = CategoryStatus::ACTIVE;
        expect($activeStatus->isActive())->toBeTrue()
            ->and($activeStatus->value)->toBe('active');
    });

    it('identifies inactive status correctly', function (): void {
        $inactiveStatus = CategoryStatus::INACTIVE;
        expect($inactiveStatus->isActive())->toBeFalse()
            ->and($inactiveStatus->value)->toBe('inactive');
    });

    it('compares status values correctly', function (): void {
        expect(CategoryStatus::ACTIVE)->not->toBe(CategoryStatus::INACTIVE)
            ->and(CategoryStatus::ACTIVE)->toBe(CategoryStatus::ACTIVE);
    });
});

describe('Category Status Factory Methods', function (): void {
    it('creates active status via factory method', function (): void {
        $status = CategoryStatus::active();
        expect($status)->toBe(CategoryStatus::ACTIVE)
            ->and($status->isActive())->toBeTrue();
    });

    it('creates inactive status via factory method', function (): void {
        $status = CategoryStatus::inactive();
        expect($status)->toBe(CategoryStatus::INACTIVE)
            ->and($status->isActive())->toBeFalse();
    });
});

describe('Category Status String Representation', function (): void {
    it('has correct value property', function (): void {
        expect(CategoryStatus::ACTIVE->value)->toBe('active')
            ->and(CategoryStatus::INACTIVE->value)->toBe('inactive');
    });

    it('can access values for string operations', function (): void {
        $activeValue = CategoryStatus::ACTIVE->value;
        $inactiveValue = CategoryStatus::INACTIVE->value;

        expect($activeValue)->toBe('active')
            ->and($inactiveValue)->toBe('inactive')
            ->and(is_string($activeValue))->toBeTrue()
            ->and(is_string($inactiveValue))->toBeTrue();
    });
});

describe('Category Status Labels and Colors', function (): void {
    it('returns correct labels', function (): void {
        expect(CategoryStatus::ACTIVE->label())->toBe('Active')
            ->and(CategoryStatus::INACTIVE->label())->toBe('Inactive');
    });

    it('returns correct colors', function (): void {
        expect(CategoryStatus::ACTIVE->color())->toBe('success')
            ->and(CategoryStatus::INACTIVE->color())->toBe('gray');
    });
});
