<?php

declare(strict_types=1);

use Modules\Category\Domain\ValueObject\CategorySlug;

describe('CategorySlug', function () {
    describe('constructor', function () {
        it('creates a valid slug from lowercase alphanumeric string', function () {
            $slug = new CategorySlug('environment');

            expect($slug->value())->toBe('environment')
                ->and($slug->__toString())->toBe('environment')
                ->and((string) $slug)->toBe('environment');
        });

        it('creates a valid slug with numbers', function () {
            $slug = new CategorySlug('environment2024');

            expect($slug->value())->toBe('environment2024')
                ->and($slug->__toString())->toBe('environment2024');
        });

        it('creates a valid slug with underscores', function () {
            $slug = new CategorySlug('environment_protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates a valid slug with hyphens', function () {
            $slug = new CategorySlug('environment-protection');

            expect($slug->value())->toBe('environment-protection')
                ->and($slug->__toString())->toBe('environment-protection');
        });

        it('creates a valid slug with mixed separators', function () {
            $slug = new CategorySlug('environment_protection-2024');

            expect($slug->value())->toBe('environment_protection-2024')
                ->and($slug->__toString())->toBe('environment_protection-2024');
        });

        it('creates a valid slug with single character', function () {
            $slug = new CategorySlug('a');

            expect($slug->value())->toBe('a')
                ->and($slug->__toString())->toBe('a');
        });

        it('creates a valid slug with numbers only', function () {
            $slug = new CategorySlug('123');

            expect($slug->value())->toBe('123')
                ->and($slug->__toString())->toBe('123');
        });

        it('creates a valid slug with underscore only', function () {
            $slug = new CategorySlug('_');

            expect($slug->value())->toBe('_')
                ->and($slug->__toString())->toBe('_');
        });

        it('creates a valid slug with hyphen only', function () {
            $slug = new CategorySlug('-');

            expect($slug->value())->toBe('-')
                ->and($slug->__toString())->toBe('-');
        });

        it('creates a valid slug with long string', function () {
            $longSlug = 'very_long_environment_protection_sustainability_climate_change_2024';
            $slug = new CategorySlug($longSlug);

            expect($slug->value())->toBe($longSlug)
                ->and($slug->__toString())->toBe($longSlug);
        });

        it('throws exception for empty string', function () {
            expect(fn () => new CategorySlug(''))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for whitespace only', function () {
            expect(fn () => new CategorySlug('   '))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for tab only', function () {
            expect(fn () => new CategorySlug("\t"))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for newline only', function () {
            expect(fn () => new CategorySlug("\n"))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for zero string', function () {
            expect(fn () => new CategorySlug('0'))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for uppercase letters', function () {
            expect(fn () => new CategorySlug('Environment'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for mixed case letters', function () {
            expect(fn () => new CategorySlug('enviroNment'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for spaces', function () {
            expect(fn () => new CategorySlug('environment protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for special characters', function () {
            expect(fn () => new CategorySlug('environment@protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for dots', function () {
            expect(fn () => new CategorySlug('environment.protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for forward slashes', function () {
            expect(fn () => new CategorySlug('environment/protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for backslashes', function () {
            expect(fn () => new CategorySlug('environment\\protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for plus signs', function () {
            expect(fn () => new CategorySlug('environment+protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for equals signs', function () {
            expect(fn () => new CategorySlug('environment=protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for parentheses', function () {
            expect(fn () => new CategorySlug('environment(protection)'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for brackets', function () {
            expect(fn () => new CategorySlug('environment[protection]'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for braces', function () {
            expect(fn () => new CategorySlug('environment{protection}'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for question marks', function () {
            expect(fn () => new CategorySlug('environment?protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for exclamation marks', function () {
            expect(fn () => new CategorySlug('environment!protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for commas', function () {
            expect(fn () => new CategorySlug('environment,protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for semicolons', function () {
            expect(fn () => new CategorySlug('environment;protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for colons', function () {
            expect(fn () => new CategorySlug('environment:protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for quotes', function () {
            expect(fn () => new CategorySlug('environment"protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for apostrophes', function () {
            expect(fn () => new CategorySlug("environment'protection"))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for asterisks', function () {
            expect(fn () => new CategorySlug('environment*protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for percent signs', function () {
            expect(fn () => new CategorySlug('environment%protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for hash symbols', function () {
            expect(fn () => new CategorySlug('environment#protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for dollar signs', function () {
            expect(fn () => new CategorySlug('environment$protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for ampersands', function () {
            expect(fn () => new CategorySlug('environment&protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for pipe symbols', function () {
            expect(fn () => new CategorySlug('environment|protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for tilde', function () {
            expect(fn () => new CategorySlug('environment~protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for backticks', function () {
            expect(fn () => new CategorySlug('environment`protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for less than', function () {
            expect(fn () => new CategorySlug('environment<protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for greater than', function () {
            expect(fn () => new CategorySlug('environment>protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for caret', function () {
            expect(fn () => new CategorySlug('environment^protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for unicode characters', function () {
            expect(fn () => new CategorySlug('environment_protección'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for emoji', function () {
            expect(fn () => new CategorySlug('environment🌍protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });
    });

    describe('fromString', function () {
        it('creates slug from simple string', function () {
            $slug = CategorySlug::fromString('Environment Protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with multiple spaces', function () {
            $slug = CategorySlug::fromString('Environment    Protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with mixed separators', function () {
            $slug = CategorySlug::fromString('Environment-Protection_2024');

            expect($slug->value())->toBe('environment_protection_2024')
                ->and($slug->__toString())->toBe('environment_protection_2024');
        });

        it('creates slug from string with special characters', function () {
            $slug = CategorySlug::fromString('Environment & Protection!');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with numbers', function () {
            $slug = CategorySlug::fromString('Environment 2024 Protection');

            expect($slug->value())->toBe('environment_2024_protection')
                ->and($slug->__toString())->toBe('environment_2024_protection');
        });

        it('creates slug from string with leading spaces', function () {
            $slug = CategorySlug::fromString('   Environment Protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with trailing spaces', function () {
            $slug = CategorySlug::fromString('Environment Protection   ');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with tabs', function () {
            $slug = CategorySlug::fromString("Environment\tProtection");

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with newlines', function () {
            $slug = CategorySlug::fromString("Environment\nProtection");

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with mixed whitespace', function () {
            $slug = CategorySlug::fromString("Environment \t\n Protection");

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with punctuation', function () {
            $slug = CategorySlug::fromString('Environment, Protection & Sustainability!');

            expect($slug->value())->toBe('environment_protection_sustainability')
                ->and($slug->__toString())->toBe('environment_protection_sustainability');
        });

        it('creates slug from string with dots', function () {
            $slug = CategorySlug::fromString('Environment.Protection.Sustainability');

            expect($slug->value())->toBe('environmentprotectionsustainability')
                ->and($slug->__toString())->toBe('environmentprotectionsustainability');
        });

        it('creates slug from string with parentheses', function () {
            $slug = CategorySlug::fromString('Environment (Protection)');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with brackets', function () {
            $slug = CategorySlug::fromString('Environment [Protection]');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with braces', function () {
            $slug = CategorySlug::fromString('Environment {Protection}');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from already valid slug', function () {
            $slug = CategorySlug::fromString('environment_protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from single word', function () {
            $slug = CategorySlug::fromString('Environment');

            expect($slug->value())->toBe('environment')
                ->and($slug->__toString())->toBe('environment');
        });

        it('creates slug from string with uppercase', function () {
            $slug = CategorySlug::fromString('ENVIRONMENT PROTECTION');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with mixed case', function () {
            $slug = CategorySlug::fromString('EnViRoNmEnT PrOtEcTiOn');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with unicode characters', function () {
            $slug = CategorySlug::fromString('Protección del Medio Ambiente');

            expect($slug->value())->toBe('proteccin_del_medio_ambiente')
                ->and($slug->__toString())->toBe('proteccin_del_medio_ambiente');
        });

        it('creates slug from string with accented characters', function () {
            $slug = CategorySlug::fromString('Écologie et Développement');

            expect($slug->value())->toBe('cologie_et_dveloppement')
                ->and($slug->__toString())->toBe('cologie_et_dveloppement');
        });

        it('creates slug from string with leading and trailing underscores', function () {
            $slug = CategorySlug::fromString('__Environment Protection__');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with leading and trailing hyphens', function () {
            $slug = CategorySlug::fromString('--Environment Protection--');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from very long string', function () {
            $longString = 'Very Long Environment Protection and Sustainability Category Name with Many Words';
            $slug = CategorySlug::fromString($longString);

            expect($slug->value())->toBe('very_long_environment_protection_and_sustainability_category_name_with_many_words')
                ->and($slug->__toString())->toBe('very_long_environment_protection_and_sustainability_category_name_with_many_words');
        });

        it('throws exception when resulting slug is empty', function () {
            expect(fn () => CategorySlug::fromString(''))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception when resulting slug is only special characters', function () {
            expect(fn () => CategorySlug::fromString('!@#$%^&*()'))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception when resulting slug is only whitespace', function () {
            expect(fn () => CategorySlug::fromString('   '))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });
    });

    describe('fromText', function () {
        it('is an alias for fromString', function () {
            $slugFromString = CategorySlug::fromString('Environment Protection');
            $slugFromText = CategorySlug::fromText('Environment Protection');

            expect($slugFromText->value())->toBe($slugFromString->value())
                ->and($slugFromText->__toString())->toBe($slugFromString->__toString());
        });

        it('handles complex text same as fromString', function () {
            $complexText = 'Environment & Protection! (Sustainability) 2024';
            $slugFromString = CategorySlug::fromString($complexText);
            $slugFromText = CategorySlug::fromText($complexText);

            expect($slugFromText->value())->toBe($slugFromString->value())
                ->and($slugFromText->__toString())->toBe($slugFromString->__toString());
        });
    });

    describe('value object equality', function () {
        it('considers two slugs with same value as equal', function () {
            $slug1 = new CategorySlug('environment_protection');
            $slug2 = new CategorySlug('environment_protection');

            expect($slug1->value())->toBe($slug2->value())
                ->and($slug1->__toString())->toBe($slug2->__toString())
                ->and((string) $slug1)->toBe((string) $slug2);
        });

        it('considers two slugs with different values as different', function () {
            $slug1 = new CategorySlug('environment_protection');
            $slug2 = new CategorySlug('healthcare');

            expect($slug1->value())->not->toBe($slug2->value())
                ->and($slug1->__toString())->not->toBe($slug2->__toString())
                ->and((string) $slug1)->not->toBe((string) $slug2);
        });

        it('considers slugs created differently but same value as equal', function () {
            $slug1 = new CategorySlug('environment_protection');
            $slug2 = CategorySlug::fromString('Environment Protection');

            expect($slug1->value())->toBe($slug2->value())
                ->and($slug1->__toString())->toBe($slug2->__toString())
                ->and((string) $slug1)->toBe((string) $slug2);
        });

        it('is immutable', function () {
            $originalValue = 'environment_protection';
            $slug = new CategorySlug($originalValue);

            // Value should remain the same
            expect($slug->value())->toBe($originalValue)
                ->and($slug->__toString())->toBe($originalValue);

            // Creating new instance doesn't affect original
            $newSlug = CategorySlug::fromString('Healthcare');
            expect($slug->value())->toBe($originalValue)
                ->and($newSlug->value())->toBe('healthcare');
        });
    });

    describe('string representation', function () {
        it('can be cast to string', function () {
            $slug = new CategorySlug('environment_protection');
            $stringValue = (string) $slug;

            expect($stringValue)->toBe('environment_protection')
                ->and($stringValue)->toBeString();
        });

        it('implements Stringable interface', function () {
            $slug = new CategorySlug('environment_protection');

            expect($slug)->toBeInstanceOf(Stringable::class);
        });

        it('toString returns same as value', function () {
            $slug = new CategorySlug('environment_protection');

            expect($slug->__toString())->toBe($slug->value())
                ->and((string) $slug)->toBe($slug->value());
        });
    });
});
