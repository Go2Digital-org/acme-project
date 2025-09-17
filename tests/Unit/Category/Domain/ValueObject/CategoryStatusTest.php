<?php

declare(strict_types=1);

use Modules\Category\Domain\ValueObject\CategoryStatus;

describe('CategoryStatus Enum', function (): void {
    describe('Enum Values', function (): void {
        it('has all expected enum cases', function (): void {
            $cases = CategoryStatus::cases();

            expect($cases)->toHaveCount(2)
                ->and(collect($cases)->pluck('value'))->toContain(
                    'active',
                    'inactive'
                );
        });

        it('creates enum instances from values', function (): void {
            expect(CategoryStatus::ACTIVE->value)->toBe('active')
                ->and(CategoryStatus::INACTIVE->value)->toBe('inactive');
        });
    });

    describe('Status Checking', function (): void {
        it('identifies active status correctly', function (): void {
            expect(CategoryStatus::ACTIVE->isActive())->toBeTrue()
                ->and(CategoryStatus::INACTIVE->isActive())->toBeFalse();
        });

        it('active method is consistent with enum value', function (): void {
            expect(CategoryStatus::ACTIVE->isActive())->toBeTrue()
                ->and(CategoryStatus::ACTIVE->value)->toBe('active');
        });
    });

    describe('Labels', function (): void {
        it('returns correct labels for each status', function (): void {
            expect(CategoryStatus::ACTIVE->label())->toBe('Active')
                ->and(CategoryStatus::INACTIVE->label())->toBe('Inactive');
        });

        it('labels are properly capitalized', function (): void {
            foreach (CategoryStatus::cases() as $status) {
                $label = $status->label();
                expect($label)->toBeString()
                    ->and($label[0])->toBe(strtoupper($label[0]));
            }
        });

        it('labels differ from enum values', function (): void {
            expect(CategoryStatus::ACTIVE->label())->not->toBe(CategoryStatus::ACTIVE->value)
                ->and(CategoryStatus::INACTIVE->label())->not->toBe(CategoryStatus::INACTIVE->value);
        });
    });

    describe('Colors', function (): void {
        it('returns correct colors for each status', function (): void {
            expect(CategoryStatus::ACTIVE->color())->toBe('success')
                ->and(CategoryStatus::INACTIVE->color())->toBe('gray');
        });

        it('active status has success color', function (): void {
            expect(CategoryStatus::ACTIVE->color())->toBe('success');
        });

        it('inactive status has gray color', function (): void {
            expect(CategoryStatus::INACTIVE->color())->toBe('gray');
        });

        it('colors are valid CSS-like color names', function (): void {
            foreach (CategoryStatus::cases() as $status) {
                $color = $status->color();
                expect($color)->toBeString()
                    ->and(strlen($color))->toBeGreaterThan(2)
                    ->and($color)->toMatch('/^[a-z]+$/'); // Only lowercase letters
            }
        });
    });

    describe('Consistency', function (): void {
        it('active status has consistent properties', function (): void {
            $active = CategoryStatus::ACTIVE;

            expect($active->value)->toBe('active')
                ->and($active->isActive())->toBeTrue()
                ->and($active->label())->toBe('Active')
                ->and($active->color())->toBe('success');
        });

        it('inactive status has consistent properties', function (): void {
            $inactive = CategoryStatus::INACTIVE;

            expect($inactive->value)->toBe('inactive')
                ->and($inactive->isActive())->toBeFalse()
                ->and($inactive->label())->toBe('Inactive')
                ->and($inactive->color())->toBe('gray');
        });

        it('only one status is active', function (): void {
            $activeCount = 0;
            foreach (CategoryStatus::cases() as $status) {
                if ($status->isActive()) {
                    $activeCount++;
                }
            }

            expect($activeCount)->toBe(1);
        });

        it('all statuses have unique values', function (): void {
            $values = array_map(fn ($status) => $status->value, CategoryStatus::cases());
            $uniqueValues = array_unique($values);

            expect(count($values))->toBe(count($uniqueValues));
        });

        it('all statuses have unique labels', function (): void {
            $labels = array_map(fn ($status) => $status->label(), CategoryStatus::cases());
            $uniqueLabels = array_unique($labels);

            expect(count($labels))->toBe(count($uniqueLabels));
        });
    });

    describe('Factory Methods', function (): void {
        it('creates active status via active() method', function (): void {
            $status = CategoryStatus::active();

            expect($status)->toBe(CategoryStatus::ACTIVE)
                ->and($status->value)->toBe('active')
                ->and($status->isActive())->toBeTrue()
                ->and($status->label())->toBe('Active')
                ->and($status->color())->toBe('success');
        });

        it('creates inactive status via inactive() method', function (): void {
            $status = CategoryStatus::inactive();

            expect($status)->toBe(CategoryStatus::INACTIVE)
                ->and($status->value)->toBe('inactive')
                ->and($status->isActive())->toBeFalse()
                ->and($status->label())->toBe('Inactive')
                ->and($status->color())->toBe('gray');
        });

        it('factory methods return same instances', function (): void {
            $active1 = CategoryStatus::active();
            $active2 = CategoryStatus::active();
            $inactive1 = CategoryStatus::inactive();
            $inactive2 = CategoryStatus::inactive();

            expect($active1)->toBe($active2)
                ->and($inactive1)->toBe($inactive2)
                ->and($active1)->not->toBe($inactive1);
        });

        it('factory methods are consistent with enum cases', function (): void {
            expect(CategoryStatus::active())->toBe(CategoryStatus::ACTIVE)
                ->and(CategoryStatus::inactive())->toBe(CategoryStatus::INACTIVE);
        });
    });

    describe('Enum Comparison', function (): void {
        it('active statuses are equal', function (): void {
            expect(CategoryStatus::ACTIVE === CategoryStatus::ACTIVE)->toBeTrue()
                ->and(CategoryStatus::active() === CategoryStatus::ACTIVE)->toBeTrue()
                ->and(CategoryStatus::active() === CategoryStatus::ACTIVE)->toBeTrue();
        });

        it('inactive statuses are equal', function (): void {
            expect(CategoryStatus::INACTIVE === CategoryStatus::INACTIVE)->toBeTrue()
                ->and(CategoryStatus::inactive() === CategoryStatus::INACTIVE)->toBeTrue()
                ->and(CategoryStatus::inactive() === CategoryStatus::INACTIVE)->toBeTrue();
        });

        it('active and inactive statuses are not equal', function (): void {
            expect(CategoryStatus::ACTIVE === CategoryStatus::INACTIVE)->toBeFalse()
                ->and(CategoryStatus::INACTIVE === CategoryStatus::ACTIVE)->toBeFalse()
                ->and(CategoryStatus::active() === CategoryStatus::inactive())->toBeFalse()
                ->and(CategoryStatus::inactive() === CategoryStatus::active())->toBeFalse();
        });

        it('supports match expressions', function (): void {
            $activeResult = match (CategoryStatus::ACTIVE) {
                CategoryStatus::ACTIVE => 'active_matched',
                CategoryStatus::INACTIVE => 'inactive_matched',
            };

            $inactiveResult = match (CategoryStatus::INACTIVE) {
                CategoryStatus::ACTIVE => 'active_matched',
                CategoryStatus::INACTIVE => 'inactive_matched',
            };

            expect($activeResult)->toBe('active_matched')
                ->and($inactiveResult)->toBe('inactive_matched');
        });
    });

    describe('String Representation', function (): void {
        it('can be converted to string via value property', function (): void {
            expect(CategoryStatus::ACTIVE->value)->toBe('active')
                ->and(CategoryStatus::INACTIVE->value)->toBe('inactive');
        });

        it('enum values are lowercase', function (): void {
            expect(CategoryStatus::ACTIVE->value)->toBe(strtolower(CategoryStatus::ACTIVE->value))
                ->and(CategoryStatus::INACTIVE->value)->toBe(strtolower(CategoryStatus::INACTIVE->value));
        });

        it('can be used in arrays', function (): void {
            $statuses = [
                CategoryStatus::ACTIVE,
                CategoryStatus::INACTIVE,
            ];

            expect($statuses)->toHaveCount(2)
                ->and($statuses[0])->toBe(CategoryStatus::ACTIVE)
                ->and($statuses[1])->toBe(CategoryStatus::INACTIVE);
        });

        it('can be used as array keys', function (): void {
            $statusMap = [
                CategoryStatus::ACTIVE->value => 'Active Category',
                CategoryStatus::INACTIVE->value => 'Inactive Category',
            ];

            expect($statusMap['active'])->toBe('Active Category')
                ->and($statusMap['inactive'])->toBe('Inactive Category')
                ->and($statusMap)->toHaveCount(2);
        });

        it('enum name matches value for consistency', function (): void {
            expect(CategoryStatus::ACTIVE->name)->toBe('ACTIVE')
                ->and(CategoryStatus::INACTIVE->name)->toBe('INACTIVE')
                ->and(strtolower(CategoryStatus::ACTIVE->name))->toBe(CategoryStatus::ACTIVE->value)
                ->and(strtolower(CategoryStatus::INACTIVE->name))->toBe(CategoryStatus::INACTIVE->value);
        });
    });

    describe('Immutability', function (): void {
        it('enum values cannot be changed', function (): void {
            $status = CategoryStatus::ACTIVE;
            $originalValue = $status->value;

            // Enum values are immutable by design
            expect($status->value)->toBe($originalValue)
                ->and($status->value)->toBe('active');
        });

        it('method results are consistent', function (): void {
            $status = CategoryStatus::ACTIVE;

            expect($status->isActive())->toBe($status->isActive())
                ->and($status->label())->toBe($status->label())
                ->and($status->color())->toBe($status->color())
                ->and($status->value)->toBe($status->value);
        });

        it('multiple calls return same results', function (): void {
            $active = CategoryStatus::ACTIVE;
            $inactive = CategoryStatus::INACTIVE;

            for ($i = 0; $i < 3; $i++) {
                expect($active->isActive())->toBeTrue()
                    ->and($active->label())->toBe('Active')
                    ->and($active->color())->toBe('success')
                    ->and($inactive->isActive())->toBeFalse()
                    ->and($inactive->label())->toBe('Inactive')
                    ->and($inactive->color())->toBe('gray');
            }
        });
    });

    describe('Type Safety', function (): void {
        it('only accepts valid enum cases', function (): void {
            $validStatuses = [CategoryStatus::ACTIVE, CategoryStatus::INACTIVE];

            foreach ($validStatuses as $status) {
                expect($status)->toBeInstanceOf(CategoryStatus::class);
            }
        });

        it('factory methods return proper type', function (): void {
            expect(CategoryStatus::active())->toBeInstanceOf(CategoryStatus::class)
                ->and(CategoryStatus::inactive())->toBeInstanceOf(CategoryStatus::class);
        });

        it('case values are always strings', function (): void {
            expect(CategoryStatus::ACTIVE->value)->toBeString()
                ->and(CategoryStatus::INACTIVE->value)->toBeString();
        });

        it('method return types are correct', function (): void {
            $status = CategoryStatus::ACTIVE;

            expect($status->isActive())->toBeBool()
                ->and($status->label())->toBeString()
                ->and($status->color())->toBeString()
                ->and($status->value)->toBeString();
        });

        it('all values are non-empty strings', function (): void {
            foreach (CategoryStatus::cases() as $status) {
                expect($status->value)->toBeString()
                    ->and($status->value)->not->toBeEmpty()
                    ->and($status->label())->toBeString()
                    ->and($status->label())->not->toBeEmpty()
                    ->and($status->color())->toBeString()
                    ->and($status->color())->not->toBeEmpty();
            }
        });
    });
});
