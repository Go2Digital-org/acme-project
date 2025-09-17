<?php

declare(strict_types=1);

use Modules\Organization\Domain\ValueObject\OrganizationCategory;

describe('OrganizationCategory', function () {
    describe('enum cases', function () {
        it('defines all expected categories', function () {
            $expectedCategories = [
                'TECHNOLOGY',
                'HEALTHCARE',
                'FINANCE',
                'EDUCATION',
                'RETAIL',
                'MANUFACTURING',
                'CONSULTING',
                'NON_PROFIT',
                'GOVERNMENT',
                'MEDIA',
                'REAL_ESTATE',
                'TRANSPORTATION',
                'ENERGY',
                'TELECOMMUNICATIONS',
                'HOSPITALITY',
                'AGRICULTURE',
                'CONSTRUCTION',
                'LEGAL',
                'OTHER',
            ];

            $actualCategories = array_map(fn ($case) => $case->name, OrganizationCategory::cases());

            expect($actualCategories)->toHaveCount(19)
                ->and($actualCategories)->toEqual($expectedCategories);

            foreach ($expectedCategories as $category) {
                expect(constant(OrganizationCategory::class . '::' . $category))->toBeInstanceOf(OrganizationCategory::class);
            }
        });

        it('has correct string values for each case', function () {
            expect(OrganizationCategory::TECHNOLOGY->value)->toBe('technology')
                ->and(OrganizationCategory::HEALTHCARE->value)->toBe('healthcare')
                ->and(OrganizationCategory::FINANCE->value)->toBe('finance')
                ->and(OrganizationCategory::EDUCATION->value)->toBe('education')
                ->and(OrganizationCategory::RETAIL->value)->toBe('retail')
                ->and(OrganizationCategory::MANUFACTURING->value)->toBe('manufacturing')
                ->and(OrganizationCategory::CONSULTING->value)->toBe('consulting')
                ->and(OrganizationCategory::NON_PROFIT->value)->toBe('non_profit')
                ->and(OrganizationCategory::GOVERNMENT->value)->toBe('government')
                ->and(OrganizationCategory::MEDIA->value)->toBe('media')
                ->and(OrganizationCategory::REAL_ESTATE->value)->toBe('real_estate')
                ->and(OrganizationCategory::TRANSPORTATION->value)->toBe('transportation')
                ->and(OrganizationCategory::ENERGY->value)->toBe('energy')
                ->and(OrganizationCategory::TELECOMMUNICATIONS->value)->toBe('telecommunications')
                ->and(OrganizationCategory::HOSPITALITY->value)->toBe('hospitality')
                ->and(OrganizationCategory::AGRICULTURE->value)->toBe('agriculture')
                ->and(OrganizationCategory::CONSTRUCTION->value)->toBe('construction')
                ->and(OrganizationCategory::LEGAL->value)->toBe('legal')
                ->and(OrganizationCategory::OTHER->value)->toBe('other');
        });

        it('creates from string values correctly', function () {
            expect(OrganizationCategory::from('technology'))->toBe(OrganizationCategory::TECHNOLOGY)
                ->and(OrganizationCategory::from('healthcare'))->toBe(OrganizationCategory::HEALTHCARE)
                ->and(OrganizationCategory::from('finance'))->toBe(OrganizationCategory::FINANCE)
                ->and(OrganizationCategory::from('education'))->toBe(OrganizationCategory::EDUCATION)
                ->and(OrganizationCategory::from('retail'))->toBe(OrganizationCategory::RETAIL)
                ->and(OrganizationCategory::from('manufacturing'))->toBe(OrganizationCategory::MANUFACTURING)
                ->and(OrganizationCategory::from('consulting'))->toBe(OrganizationCategory::CONSULTING)
                ->and(OrganizationCategory::from('non_profit'))->toBe(OrganizationCategory::NON_PROFIT)
                ->and(OrganizationCategory::from('government'))->toBe(OrganizationCategory::GOVERNMENT)
                ->and(OrganizationCategory::from('media'))->toBe(OrganizationCategory::MEDIA);
        });

        it('throws error for invalid string values', function () {
            expect(fn () => OrganizationCategory::from('invalid'))
                ->toThrow(ValueError::class);

            expect(fn () => OrganizationCategory::from(''))
                ->toThrow(ValueError::class);

            expect(fn () => OrganizationCategory::from('tech'))
                ->toThrow(ValueError::class);
        });
    });

    describe('getLabel method', function () {
        it('returns correct labels for all categories', function () {
            expect(OrganizationCategory::TECHNOLOGY->getLabel())->toBe('Technology')
                ->and(OrganizationCategory::HEALTHCARE->getLabel())->toBe('Healthcare')
                ->and(OrganizationCategory::FINANCE->getLabel())->toBe('Finance')
                ->and(OrganizationCategory::EDUCATION->getLabel())->toBe('Education')
                ->and(OrganizationCategory::RETAIL->getLabel())->toBe('Retail')
                ->and(OrganizationCategory::MANUFACTURING->getLabel())->toBe('Manufacturing')
                ->and(OrganizationCategory::CONSULTING->getLabel())->toBe('Consulting')
                ->and(OrganizationCategory::NON_PROFIT->getLabel())->toBe('Non-Profit')
                ->and(OrganizationCategory::GOVERNMENT->getLabel())->toBe('Government')
                ->and(OrganizationCategory::MEDIA->getLabel())->toBe('Media')
                ->and(OrganizationCategory::REAL_ESTATE->getLabel())->toBe('Real Estate')
                ->and(OrganizationCategory::TRANSPORTATION->getLabel())->toBe('Transportation')
                ->and(OrganizationCategory::ENERGY->getLabel())->toBe('Energy')
                ->and(OrganizationCategory::TELECOMMUNICATIONS->getLabel())->toBe('Telecommunications')
                ->and(OrganizationCategory::HOSPITALITY->getLabel())->toBe('Hospitality')
                ->and(OrganizationCategory::AGRICULTURE->getLabel())->toBe('Agriculture')
                ->and(OrganizationCategory::CONSTRUCTION->getLabel())->toBe('Construction')
                ->and(OrganizationCategory::LEGAL->getLabel())->toBe('Legal')
                ->and(OrganizationCategory::OTHER->getLabel())->toBe('Other');
        });

        it('returns string labels for all cases', function () {
            foreach (OrganizationCategory::cases() as $category) {
                $label = $category->getLabel();

                expect($label)->toBeString()
                    ->and($label)->not->toBeEmpty()
                    ->and(strlen($label))->toBeGreaterThan(0);
            }
        });

        it('has unique labels for each category', function () {
            $labels = array_map(fn ($category) => $category->getLabel(), OrganizationCategory::cases());
            $uniqueLabels = array_unique($labels);

            expect(count($labels))->toBe(count($uniqueLabels))
                ->and($labels)->toHaveCount(19);
        });
    });

    describe('getColor method', function () {
        it('returns correct colors for all categories', function () {
            expect(OrganizationCategory::TECHNOLOGY->getColor())->toBe('blue')
                ->and(OrganizationCategory::HEALTHCARE->getColor())->toBe('green')
                ->and(OrganizationCategory::FINANCE->getColor())->toBe('purple')
                ->and(OrganizationCategory::EDUCATION->getColor())->toBe('indigo')
                ->and(OrganizationCategory::RETAIL->getColor())->toBe('orange')
                ->and(OrganizationCategory::MANUFACTURING->getColor())->toBe('gray')
                ->and(OrganizationCategory::CONSULTING->getColor())->toBe('cyan')
                ->and(OrganizationCategory::NON_PROFIT->getColor())->toBe('pink')
                ->and(OrganizationCategory::GOVERNMENT->getColor())->toBe('red')
                ->and(OrganizationCategory::MEDIA->getColor())->toBe('yellow')
                ->and(OrganizationCategory::REAL_ESTATE->getColor())->toBe('lime')
                ->and(OrganizationCategory::TRANSPORTATION->getColor())->toBe('teal')
                ->and(OrganizationCategory::ENERGY->getColor())->toBe('amber')
                ->and(OrganizationCategory::TELECOMMUNICATIONS->getColor())->toBe('violet')
                ->and(OrganizationCategory::HOSPITALITY->getColor())->toBe('rose')
                ->and(OrganizationCategory::AGRICULTURE->getColor())->toBe('emerald')
                ->and(OrganizationCategory::CONSTRUCTION->getColor())->toBe('stone')
                ->and(OrganizationCategory::LEGAL->getColor())->toBe('slate')
                ->and(OrganizationCategory::OTHER->getColor())->toBe('neutral');
        });

        it('returns valid color strings for all cases', function () {
            $validColors = [
                'blue', 'green', 'purple', 'indigo', 'orange', 'gray', 'cyan', 'pink', 'red',
                'yellow', 'lime', 'teal', 'amber', 'violet', 'rose', 'emerald', 'stone', 'slate', 'neutral',
            ];

            foreach (OrganizationCategory::cases() as $category) {
                $color = $category->getColor();

                expect($color)->toBeString()
                    ->and($color)->toBeIn($validColors)
                    ->and(strlen($color))->toBeGreaterThan(2);
            }
        });

        it('assigns unique colors to categories', function () {
            $colors = array_map(fn ($category) => $category->getColor(), OrganizationCategory::cases());
            $uniqueColors = array_unique($colors);

            expect(count($colors))->toBe(count($uniqueColors))
                ->and($colors)->toHaveCount(19);
        });
    });

    describe('getIcon method', function () {
        it('returns correct icons for all categories', function () {
            expect(OrganizationCategory::TECHNOLOGY->getIcon())->toBe('heroicon-o-computer-desktop')
                ->and(OrganizationCategory::HEALTHCARE->getIcon())->toBe('heroicon-o-heart')
                ->and(OrganizationCategory::FINANCE->getIcon())->toBe('heroicon-o-banknotes')
                ->and(OrganizationCategory::EDUCATION->getIcon())->toBe('heroicon-o-academic-cap')
                ->and(OrganizationCategory::RETAIL->getIcon())->toBe('heroicon-o-shopping-bag')
                ->and(OrganizationCategory::MANUFACTURING->getIcon())->toBe('heroicon-o-cog-6-tooth')
                ->and(OrganizationCategory::CONSULTING->getIcon())->toBe('heroicon-o-light-bulb')
                ->and(OrganizationCategory::NON_PROFIT->getIcon())->toBe('heroicon-o-hand-raised')
                ->and(OrganizationCategory::GOVERNMENT->getIcon())->toBe('heroicon-o-building-library')
                ->and(OrganizationCategory::MEDIA->getIcon())->toBe('heroicon-o-tv')
                ->and(OrganizationCategory::REAL_ESTATE->getIcon())->toBe('heroicon-o-home')
                ->and(OrganizationCategory::TRANSPORTATION->getIcon())->toBe('heroicon-o-truck')
                ->and(OrganizationCategory::ENERGY->getIcon())->toBe('heroicon-o-bolt')
                ->and(OrganizationCategory::TELECOMMUNICATIONS->getIcon())->toBe('heroicon-o-phone')
                ->and(OrganizationCategory::HOSPITALITY->getIcon())->toBe('heroicon-o-building-office')
                ->and(OrganizationCategory::AGRICULTURE->getIcon())->toBe('heroicon-o-sun')
                ->and(OrganizationCategory::CONSTRUCTION->getIcon())->toBe('heroicon-o-wrench-screwdriver')
                ->and(OrganizationCategory::LEGAL->getIcon())->toBe('heroicon-o-scale')
                ->and(OrganizationCategory::OTHER->getIcon())->toBe('heroicon-o-building-office-2');
        });

        it('returns heroicon format for all icons', function () {
            foreach (OrganizationCategory::cases() as $category) {
                $icon = $category->getIcon();

                expect($icon)->toBeString()
                    ->and($icon)->toStartWith('heroicon-o-')
                    ->and(strlen($icon))->toBeGreaterThan(11);
            }
        });

        it('assigns unique icons to categories', function () {
            $icons = array_map(fn ($category) => $category->getIcon(), OrganizationCategory::cases());
            $uniqueIcons = array_unique($icons);

            expect(count($icons))->toBe(count($uniqueIcons))
                ->and($icons)->toHaveCount(19);
        });

        it('uses valid heroicon outline icons', function () {
            $validIconPrefixes = ['heroicon-o-'];

            foreach (OrganizationCategory::cases() as $category) {
                $icon = $category->getIcon();
                $hasValidPrefix = false;

                foreach ($validIconPrefixes as $prefix) {
                    if (str_starts_with($icon, $prefix)) {
                        $hasValidPrefix = true;
                        break;
                    }
                }

                expect($hasValidPrefix)->toBeTrue();
            }
        });
    });

    describe('getOptions static method', function () {
        it('returns array with all category options', function () {
            $options = OrganizationCategory::getOptions();

            expect($options)->toBeArray()
                ->and($options)->toHaveCount(19)
                ->and(array_keys($options))->toEqual(array_map(fn ($case) => $case->value, OrganizationCategory::cases()));
        });

        it('maps values to labels correctly', function () {
            $options = OrganizationCategory::getOptions();

            expect($options['technology'])->toBe('Technology')
                ->and($options['healthcare'])->toBe('Healthcare')
                ->and($options['finance'])->toBe('Finance')
                ->and($options['education'])->toBe('Education')
                ->and($options['retail'])->toBe('Retail')
                ->and($options['manufacturing'])->toBe('Manufacturing')
                ->and($options['consulting'])->toBe('Consulting')
                ->and($options['non_profit'])->toBe('Non-Profit')
                ->and($options['government'])->toBe('Government')
                ->and($options['media'])->toBe('Media');
        });

        it('includes all categories in options', function () {
            $options = OrganizationCategory::getOptions();
            $expectedKeys = array_map(fn ($case) => $case->value, OrganizationCategory::cases());
            $actualKeys = array_keys($options);

            expect($actualKeys)->toEqual($expectedKeys)
                ->and(count($actualKeys))->toBe(count($expectedKeys));
        });

        it('returns consistent results on multiple calls', function () {
            $options1 = OrganizationCategory::getOptions();
            $options2 = OrganizationCategory::getOptions();

            expect($options1)->toEqual($options2)
                ->and($options1)->toHaveCount(count($options2));
        });

        it('contains only string keys and values', function () {
            $options = OrganizationCategory::getOptions();

            foreach ($options as $key => $value) {
                expect($key)->toBeString()
                    ->and($value)->toBeString()
                    ->and($key)->not->toBeEmpty()
                    ->and($value)->not->toBeEmpty();
            }
        });
    });

    describe('enum behavior and consistency', function () {
        it('maintains case consistency', function () {
            foreach (OrganizationCategory::cases() as $category) {
                expect($category->name)->toBe(strtoupper(str_replace(' ', '_', $category->name)))
                    ->and($category->value)->toBe(strtolower(str_replace(' ', '_', $category->value)));
            }
        });

        it('supports comparison operations', function () {
            $tech1 = OrganizationCategory::TECHNOLOGY;
            $tech2 = OrganizationCategory::TECHNOLOGY;
            $healthcare = OrganizationCategory::HEALTHCARE;

            expect($tech1 === $tech2)->toBeTrue()
                ->and($tech1 === $healthcare)->toBeFalse()
                ->and($tech1 !== $healthcare)->toBeTrue();
        });

        it('works with match expressions', function () {
            $result = match (OrganizationCategory::TECHNOLOGY) {
                OrganizationCategory::TECHNOLOGY => 'tech_category',
                OrganizationCategory::HEALTHCARE => 'health_category',
                default => 'other_category'
            };

            expect($result)->toBe('tech_category');
        });

        it('supports serialization consistency', function () {
            foreach (OrganizationCategory::cases() as $category) {
                $serialized = serialize($category);
                $unserialized = unserialize($serialized);

                expect($unserialized)->toBe($category)
                    ->and($unserialized->value)->toBe($category->value)
                    ->and($unserialized->name)->toBe($category->name);
            }
        });

        it('provides exhaustive coverage in switches', function () {
            $allHandled = true;

            foreach (OrganizationCategory::cases() as $category) {
                $handled = match ($category) {
                    OrganizationCategory::TECHNOLOGY,
                    OrganizationCategory::HEALTHCARE,
                    OrganizationCategory::FINANCE,
                    OrganizationCategory::EDUCATION,
                    OrganizationCategory::RETAIL,
                    OrganizationCategory::MANUFACTURING,
                    OrganizationCategory::CONSULTING,
                    OrganizationCategory::NON_PROFIT,
                    OrganizationCategory::GOVERNMENT,
                    OrganizationCategory::MEDIA,
                    OrganizationCategory::REAL_ESTATE,
                    OrganizationCategory::TRANSPORTATION,
                    OrganizationCategory::ENERGY,
                    OrganizationCategory::TELECOMMUNICATIONS,
                    OrganizationCategory::HOSPITALITY,
                    OrganizationCategory::AGRICULTURE,
                    OrganizationCategory::CONSTRUCTION,
                    OrganizationCategory::LEGAL,
                    OrganizationCategory::OTHER => true,
                };

                expect($handled)->toBeTrue();
            }

            expect($allHandled)->toBeTrue();
        });
    });

    describe('integration and practical usage', function () {
        it('can be used in arrays and collections', function () {
            $categories = [
                OrganizationCategory::TECHNOLOGY,
                OrganizationCategory::HEALTHCARE,
                OrganizationCategory::FINANCE,
            ];

            expect($categories)->toHaveCount(3)
                ->and($categories[0])->toBe(OrganizationCategory::TECHNOLOGY)
                ->and($categories[1])->toBe(OrganizationCategory::HEALTHCARE)
                ->and($categories[2])->toBe(OrganizationCategory::FINANCE);

            expect(in_array(OrganizationCategory::TECHNOLOGY, $categories, true))->toBeTrue()
                ->and(in_array(OrganizationCategory::RETAIL, $categories, true))->toBeFalse();
        });

        it('supports filtering and mapping operations', function () {
            $businessCategories = array_filter(
                OrganizationCategory::cases(),
                fn ($cat) => in_array($cat, [
                    OrganizationCategory::TECHNOLOGY,
                    OrganizationCategory::FINANCE,
                    OrganizationCategory::CONSULTING,
                    OrganizationCategory::RETAIL,
                ])
            );

            expect($businessCategories)->toHaveCount(4);

            $labels = array_map(fn ($cat) => $cat->getLabel(), $businessCategories);
            expect($labels)->toContain('Technology')
                ->and($labels)->toContain('Finance')
                ->and($labels)->toContain('Consulting')
                ->and($labels)->toContain('Retail');
        });

        it('provides consistent string representation', function () {
            foreach (OrganizationCategory::cases() as $category) {
                expect((string) $category->value)->toBe($category->value)
                    ->and($category->value)->toBeString()
                    ->and(strlen($category->value))->toBeGreaterThan(0);
            }
        });
    });
});
