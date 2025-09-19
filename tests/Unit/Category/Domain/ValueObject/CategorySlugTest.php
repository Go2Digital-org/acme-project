<?php

declare(strict_types=1);

use Modules\Category\Domain\ValueObject\CategorySlug;

describe('CategorySlug', function (): void {
    describe('constructor', function (): void {
        it('creates a valid slug from lowercase alphanumeric string', function (): void {
            $slug = new CategorySlug('environment');

            expect($slug->value())->toBe('environment')
                ->and($slug->__toString())->toBe('environment')
                ->and((string) $slug)->toBe('environment');
        });

        it('creates a valid slug with numbers', function (): void {
            $slug = new CategorySlug('environment2024');

            expect($slug->value())->toBe('environment2024')
                ->and($slug->__toString())->toBe('environment2024');
        });

        it('creates a valid slug with underscores', function (): void {
            $slug = new CategorySlug('environment_protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates a valid slug with hyphens', function (): void {
            $slug = new CategorySlug('environment-protection');

            expect($slug->value())->toBe('environment-protection')
                ->and($slug->__toString())->toBe('environment-protection');
        });

        it('creates a valid slug with mixed separators', function (): void {
            $slug = new CategorySlug('environment_protection-2024');

            expect($slug->value())->toBe('environment_protection-2024')
                ->and($slug->__toString())->toBe('environment_protection-2024');
        });

        it('creates a valid slug with single character', function (): void {
            $slug = new CategorySlug('a');

            expect($slug->value())->toBe('a')
                ->and($slug->__toString())->toBe('a');
        });

        it('creates a valid slug with numbers only', function (): void {
            $slug = new CategorySlug('123');

            expect($slug->value())->toBe('123')
                ->and($slug->__toString())->toBe('123');
        });

        it('creates a valid slug with underscore only', function (): void {
            $slug = new CategorySlug('_');

            expect($slug->value())->toBe('_')
                ->and($slug->__toString())->toBe('_');
        });

        it('creates a valid slug with hyphen only', function (): void {
            $slug = new CategorySlug('-');

            expect($slug->value())->toBe('-')
                ->and($slug->__toString())->toBe('-');
        });

        it('creates a valid slug with long string', function (): void {
            $longSlug = 'very_long_environment_protection_sustainability_climate_change_2024';
            $slug = new CategorySlug($longSlug);

            expect($slug->value())->toBe($longSlug)
                ->and($slug->__toString())->toBe($longSlug);
        });

        it('throws exception for empty string', function (): void {
            expect(fn () => new CategorySlug(''))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for whitespace only', function (): void {
            expect(fn () => new CategorySlug('   '))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for tab only', function (): void {
            expect(fn () => new CategorySlug("\t"))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for newline only', function (): void {
            expect(fn () => new CategorySlug("\n"))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for zero string', function (): void {
            expect(fn () => new CategorySlug('0'))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception for uppercase letters', function (): void {
            expect(fn () => new CategorySlug('Environment'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for mixed case letters', function (): void {
            expect(fn () => new CategorySlug('enviroNment'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for spaces', function (): void {
            expect(fn () => new CategorySlug('environment protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for special characters', function (): void {
            expect(fn () => new CategorySlug('environment@protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for dots', function (): void {
            expect(fn () => new CategorySlug('environment.protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for forward slashes', function (): void {
            expect(fn () => new CategorySlug('environment/protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for backslashes', function (): void {
            expect(fn () => new CategorySlug('environment\\protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for plus signs', function (): void {
            expect(fn () => new CategorySlug('environment+protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for equals signs', function (): void {
            expect(fn () => new CategorySlug('environment=protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for parentheses', function (): void {
            expect(fn () => new CategorySlug('environment(protection)'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for brackets', function (): void {
            expect(fn () => new CategorySlug('environment[protection]'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for braces', function (): void {
            expect(fn () => new CategorySlug('environment{protection}'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for question marks', function (): void {
            expect(fn () => new CategorySlug('environment?protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for exclamation marks', function (): void {
            expect(fn () => new CategorySlug('environment!protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for commas', function (): void {
            expect(fn () => new CategorySlug('environment,protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for semicolons', function (): void {
            expect(fn () => new CategorySlug('environment;protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for colons', function (): void {
            expect(fn () => new CategorySlug('environment:protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for quotes', function (): void {
            expect(fn () => new CategorySlug('environment"protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for apostrophes', function (): void {
            expect(fn () => new CategorySlug("environment'protection"))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for asterisks', function (): void {
            expect(fn () => new CategorySlug('environment*protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for percent signs', function (): void {
            expect(fn () => new CategorySlug('environment%protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for hash symbols', function (): void {
            expect(fn () => new CategorySlug('environment#protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for dollar signs', function (): void {
            expect(fn () => new CategorySlug('environment$protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for ampersands', function (): void {
            expect(fn () => new CategorySlug('environment&protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for pipe symbols', function (): void {
            expect(fn () => new CategorySlug('environment|protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for tilde', function (): void {
            expect(fn () => new CategorySlug('environment~protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for backticks', function (): void {
            expect(fn () => new CategorySlug('environment`protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for less than', function (): void {
            expect(fn () => new CategorySlug('environment<protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for greater than', function (): void {
            expect(fn () => new CategorySlug('environment>protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for caret', function (): void {
            expect(fn () => new CategorySlug('environment^protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for unicode characters', function (): void {
            expect(fn () => new CategorySlug('environment_protección'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });

        it('throws exception for emoji', function (): void {
            expect(fn () => new CategorySlug('environment🌍protection'))
                ->toThrow(InvalidArgumentException::class, 'Category slug must contain only lowercase letters, numbers, underscores, and hyphens');
        });
    });

    describe('fromString', function (): void {
        it('creates slug from simple string', function (): void {
            $slug = CategorySlug::fromString('Environment Protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with multiple spaces', function (): void {
            $slug = CategorySlug::fromString('Environment    Protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with mixed separators', function (): void {
            $slug = CategorySlug::fromString('Environment-Protection_2024');

            expect($slug->value())->toBe('environment_protection_2024')
                ->and($slug->__toString())->toBe('environment_protection_2024');
        });

        it('creates slug from string with special characters', function (): void {
            $slug = CategorySlug::fromString('Environment & Protection!');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with numbers', function (): void {
            $slug = CategorySlug::fromString('Environment 2024 Protection');

            expect($slug->value())->toBe('environment_2024_protection')
                ->and($slug->__toString())->toBe('environment_2024_protection');
        });

        it('creates slug from string with leading spaces', function (): void {
            $slug = CategorySlug::fromString('   Environment Protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with trailing spaces', function (): void {
            $slug = CategorySlug::fromString('Environment Protection   ');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with tabs', function (): void {
            $slug = CategorySlug::fromString("Environment\tProtection");

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with newlines', function (): void {
            $slug = CategorySlug::fromString("Environment\nProtection");

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with mixed whitespace', function (): void {
            $slug = CategorySlug::fromString("Environment \t\n Protection");

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with punctuation', function (): void {
            $slug = CategorySlug::fromString('Environment, Protection & Sustainability!');

            expect($slug->value())->toBe('environment_protection_sustainability')
                ->and($slug->__toString())->toBe('environment_protection_sustainability');
        });

        it('creates slug from string with dots', function (): void {
            $slug = CategorySlug::fromString('Environment.Protection.Sustainability');

            expect($slug->value())->toBe('environmentprotectionsustainability')
                ->and($slug->__toString())->toBe('environmentprotectionsustainability');
        });

        it('creates slug from string with parentheses', function (): void {
            $slug = CategorySlug::fromString('Environment (Protection)');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with brackets', function (): void {
            $slug = CategorySlug::fromString('Environment [Protection]');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with braces', function (): void {
            $slug = CategorySlug::fromString('Environment {Protection}');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from already valid slug', function (): void {
            $slug = CategorySlug::fromString('environment_protection');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from single word', function (): void {
            $slug = CategorySlug::fromString('Environment');

            expect($slug->value())->toBe('environment')
                ->and($slug->__toString())->toBe('environment');
        });

        it('creates slug from string with uppercase', function (): void {
            $slug = CategorySlug::fromString('ENVIRONMENT PROTECTION');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with mixed case', function (): void {
            $slug = CategorySlug::fromString('EnViRoNmEnT PrOtEcTiOn');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with unicode characters', function (): void {
            $slug = CategorySlug::fromString('Protección del Medio Ambiente');

            expect($slug->value())->toBe('proteccin_del_medio_ambiente')
                ->and($slug->__toString())->toBe('proteccin_del_medio_ambiente');
        });

        it('creates slug from string with accented characters', function (): void {
            $slug = CategorySlug::fromString('Écologie et Développement');

            expect($slug->value())->toBe('cologie_et_dveloppement')
                ->and($slug->__toString())->toBe('cologie_et_dveloppement');
        });

        it('creates slug from string with leading and trailing underscores', function (): void {
            $slug = CategorySlug::fromString('__Environment Protection__');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from string with leading and trailing hyphens', function (): void {
            $slug = CategorySlug::fromString('--Environment Protection--');

            expect($slug->value())->toBe('environment_protection')
                ->and($slug->__toString())->toBe('environment_protection');
        });

        it('creates slug from very long string', function (): void {
            $longString = 'Very Long Environment Protection and Sustainability Category Name with Many Words';
            $slug = CategorySlug::fromString($longString);

            expect($slug->value())->toBe('very_long_environment_protection_and_sustainability_category_name_with_many_words')
                ->and($slug->__toString())->toBe('very_long_environment_protection_and_sustainability_category_name_with_many_words');
        });

        it('throws exception when resulting slug is empty', function (): void {
            expect(fn () => CategorySlug::fromString(''))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception when resulting slug is only special characters', function (): void {
            expect(fn () => CategorySlug::fromString('!@#$%^&*()'))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });

        it('throws exception when resulting slug is only whitespace', function (): void {
            expect(fn () => CategorySlug::fromString('   '))
                ->toThrow(InvalidArgumentException::class, 'Category slug cannot be empty');
        });
    });

    describe('fromText', function (): void {
        it('is an alias for fromString', function (): void {
            $slugFromString = CategorySlug::fromString('Environment Protection');
            $slugFromText = CategorySlug::fromText('Environment Protection');

            expect($slugFromText->value())->toBe($slugFromString->value())
                ->and($slugFromText->__toString())->toBe($slugFromString->__toString());
        });

        it('handles complex text same as fromString', function (): void {
            $complexText = 'Environment & Protection! (Sustainability) 2024';
            $slugFromString = CategorySlug::fromString($complexText);
            $slugFromText = CategorySlug::fromText($complexText);

            expect($slugFromText->value())->toBe($slugFromString->value())
                ->and($slugFromText->__toString())->toBe($slugFromString->__toString());
        });
    });

    describe('value object equality', function (): void {
        it('considers two slugs with same value as equal', function (): void {
            $slug1 = new CategorySlug('environment_protection');
            $slug2 = new CategorySlug('environment_protection');

            expect($slug1->value())->toBe($slug2->value())
                ->and($slug1->__toString())->toBe($slug2->__toString())
                ->and((string) $slug1)->toBe((string) $slug2);
        });

        it('considers two slugs with different values as different', function (): void {
            $slug1 = new CategorySlug('environment_protection');
            $slug2 = new CategorySlug('healthcare');

            expect($slug1->value())->not->toBe($slug2->value())
                ->and($slug1->__toString())->not->toBe($slug2->__toString())
                ->and((string) $slug1)->not->toBe((string) $slug2);
        });

        it('considers slugs created differently but same value as equal', function (): void {
            $slug1 = new CategorySlug('environment_protection');
            $slug2 = CategorySlug::fromString('Environment Protection');

            expect($slug1->value())->toBe($slug2->value())
                ->and($slug1->__toString())->toBe($slug2->__toString())
                ->and((string) $slug1)->toBe((string) $slug2);
        });

        it('is immutable', function (): void {
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

    describe('string representation', function (): void {
        it('can be cast to string', function (): void {
            $slug = new CategorySlug('environment_protection');
            $stringValue = (string) $slug;

            expect($stringValue)->toBe('environment_protection')
                ->and($stringValue)->toBeString();
        });

        it('implements Stringable interface', function (): void {
            $slug = new CategorySlug('environment_protection');

            expect($slug)->toBeInstanceOf(Stringable::class);
        });

        it('toString returns same as value', function (): void {
            $slug = new CategorySlug('environment_protection');

            expect($slug->__toString())->toBe($slug->value())
                ->and((string) $slug)->toBe($slug->value());
        });
    });
});
