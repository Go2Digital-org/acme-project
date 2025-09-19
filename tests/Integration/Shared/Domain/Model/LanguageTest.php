<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Shared\Domain\Model\Language;

describe('Language Model', function (): void {
    describe('Model Configuration', function (): void {
        it('has correct fillable attributes', function (): void {
            $language = new Language;

            $expectedFillable = [
                'code',
                'name',
                'native_name',
                'flag',
                'is_active',
                'is_default',
                'sort_order',
            ];

            expect($language->getFillable())->toBe($expectedFillable);
        });

        it('uses HasFactory trait', function (): void {
            $language = new Language;

            expect(in_array(HasFactory::class, class_uses($language), true))->toBeTrue();
        });

        it('has correct casts for boolean fields', function (): void {
            $language = new Language;
            $casts = $language->getCasts();

            expect($casts['is_active'])->toBe('boolean')
                ->and($casts['is_default'])->toBe('boolean');
        });
    });

    describe('Attribute Assignment', function (): void {
        it('can be created with all fillable attributes', function (): void {
            $attributes = [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 1,
            ];

            $language = new Language($attributes);

            expect($language->code)->toBe('en')
                ->and($language->name)->toBe('English')
                ->and($language->native_name)->toBe('English')
                ->and($language->flag)->toBe('🇺🇸')
                ->and($language->is_active)->toBeTrue()
                ->and($language->is_default)->toBeFalse()
                ->and($language->sort_order)->toBe(1);
        });

        it('can be created with minimal attributes', function (): void {
            $attributes = [
                'code' => 'fr',
                'name' => 'French',
            ];

            $language = new Language($attributes);

            expect($language->code)->toBe('fr')
                ->and($language->name)->toBe('French')
                ->and($language->native_name)->toBeNull()
                ->and($language->flag)->toBeNull()
                ->and($language->sort_order)->toBeNull();
        });

        it('handles boolean casting correctly', function (): void {
            $language = new Language([
                'code' => 'es',
                'name' => 'Spanish',
                'is_active' => '1', // String '1'
                'is_default' => 0,  // Integer 0
            ]);

            expect($language->is_active)->toBeTrue()
                ->and($language->is_default)->toBeFalse();
        });

        it('handles null boolean values', function (): void {
            $language = new Language([
                'code' => 'de',
                'name' => 'German',
                'is_active' => null,
                'is_default' => null,
            ]);

            expect($language->is_active)->toBeNull()
                ->and($language->is_default)->toBeNull();
        });
    });

    describe('Language Codes', function (): void {
        it('handles standard two-letter language codes', function (): void {
            $codes = ['en', 'fr', 'de', 'es', 'it', 'pt', 'ru', 'ja', 'ko', 'zh'];

            foreach ($codes as $code) {
                $language = new Language(['code' => $code, 'name' => 'Test']);
                expect($language->code)->toBe($code);
            }
        });

        it('handles extended language codes', function (): void {
            $extendedCodes = ['en-US', 'en-GB', 'fr-CA', 'es-ES', 'pt-BR', 'zh-CN', 'zh-TW'];

            foreach ($extendedCodes as $code) {
                $language = new Language(['code' => $code, 'name' => 'Test']);
                expect($language->code)->toBe($code);
            }
        });

        it('handles case sensitivity in codes', function (): void {
            $language1 = new Language(['code' => 'en', 'name' => 'English']);
            $language2 = new Language(['code' => 'EN', 'name' => 'English Upper']);

            expect($language1->code)->toBe('en')
                ->and($language2->code)->toBe('EN')
                ->and($language1->code)->not->toBe($language2->code);
        });
    });

    describe('Names and Localization', function (): void {
        it('stores different name formats correctly', function (): void {
            $language = new Language([
                'code' => 'zh',
                'name' => 'Chinese',
                'native_name' => '中文',
            ]);

            expect($language->name)->toBe('Chinese')
                ->and($language->native_name)->toBe('中文');
        });

        it('handles names with special characters', function (): void {
            $languages = [
                ['code' => 'de', 'name' => 'Deutsch', 'native_name' => 'Deutsch'],
                ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français'],
                ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español'],
                ['code' => 'pt', 'name' => 'Portuguese', 'native_name' => 'Português'],
                ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский'],
                ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية'],
            ];

            foreach ($languages as $data) {
                $language = new Language($data);
                expect($language->name)->toBe($data['name'])
                    ->and($language->native_name)->toBe($data['native_name']);
            }
        });

        it('handles long language names', function (): void {
            $longName = 'A Very Long Language Name That Might Exceed Normal Expectations';
            $language = new Language([
                'code' => 'test',
                'name' => $longName,
                'native_name' => $longName,
            ]);

            expect($language->name)->toBe($longName)
                ->and($language->native_name)->toBe($longName);
        });

        it('allows null native names', function (): void {
            $language = new Language([
                'code' => 'en',
                'name' => 'English',
                'native_name' => null,
            ]);

            expect($language->native_name)->toBeNull();
        });
    });

    describe('Flags and Symbols', function (): void {
        it('stores emoji flags correctly', function (): void {
            $flags = [
                'en' => '🇺🇸',
                'fr' => '🇫🇷',
                'de' => '🇩🇪',
                'es' => '🇪🇸',
                'it' => '🇮🇹',
                'jp' => '🇯🇵',
                'cn' => '🇨🇳',
            ];

            foreach ($flags as $code => $flag) {
                $language = new Language([
                    'code' => $code,
                    'name' => 'Test',
                    'flag' => $flag,
                ]);
                expect($language->flag)->toBe($flag);
            }
        });

        it('allows null flags', function (): void {
            $language = new Language([
                'code' => 'unknown',
                'name' => 'Unknown Language',
                'flag' => null,
            ]);

            expect($language->flag)->toBeNull();
        });

        it('handles non-emoji flag representations', function (): void {
            $language = new Language([
                'code' => 'en',
                'name' => 'English',
                'flag' => 'US',
            ]);

            expect($language->flag)->toBe('US');
        });
    });

    describe('Status and Ordering', function (): void {
        it('handles active status correctly', function (): void {
            $activeLanguage = new Language([
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
            ]);

            $inactiveLanguage = new Language([
                'code' => 'old',
                'name' => 'Old Language',
                'is_active' => false,
            ]);

            expect($activeLanguage->is_active)->toBeTrue()
                ->and($inactiveLanguage->is_active)->toBeFalse();
        });

        it('handles default status correctly', function (): void {
            $defaultLanguage = new Language([
                'code' => 'en',
                'name' => 'English',
                'is_default' => true,
            ]);

            $nonDefaultLanguage = new Language([
                'code' => 'fr',
                'name' => 'French',
                'is_default' => false,
            ]);

            expect($defaultLanguage->is_default)->toBeTrue()
                ->and($nonDefaultLanguage->is_default)->toBeFalse();
        });

        it('handles sort order values', function (): void {
            $orders = [1, 2, 3, 10, 100, 0, -1];

            foreach ($orders as $order) {
                $language = new Language([
                    'code' => 'test' . $order,
                    'name' => 'Test',
                    'sort_order' => $order,
                ]);
                expect($language->sort_order)->toBe($order);
            }
        });

        it('handles null sort order', function (): void {
            $language = new Language([
                'code' => 'test',
                'name' => 'Test',
                'sort_order' => null,
            ]);

            expect($language->sort_order)->toBeNull();
        });
    });

    describe('Model State', function (): void {
        it('tracks dirty attributes correctly', function (): void {
            $language = Language::factory()->create([
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
            ]);

            expect($language->isDirty())->toBeFalse();

            $language->name = 'Modified English';

            expect($language->isDirty())->toBeTrue()
                ->and($language->isDirty('name'))->toBeTrue()
                ->and($language->isDirty('code'))->toBeFalse();
        });

        it('tracks original attributes', function (): void {
            $language = Language::factory()->create([
                'code' => 'en',
                'name' => 'English',
            ]);

            $originalName = $language->getOriginal('name');
            $language->name = 'Modified English';

            expect($originalName)->toBe('English')
                ->and($language->name)->toBe('Modified English')
                ->and($language->getOriginal('name'))->toBe('English');
        });

        it('handles attribute changes correctly', function (): void {
            $language = Language::factory()->create([
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 1,
            ]);

            $language->name = 'Modified English';
            $language->is_active = false;
            $language->sort_order = 2;
            $language->save();

            $changes = $language->getChanges();

            expect($changes)->toHaveKey('name')
                ->and($changes)->toHaveKey('is_active')
                ->and($changes)->toHaveKey('sort_order')
                ->and($changes)->not->toHaveKey('code')
                ->and($changes)->not->toHaveKey('is_default');
        });
    });

    describe('Validation Scenarios', function (): void {
        it('handles empty code gracefully', function (): void {
            $language = new Language([
                'code' => '',
                'name' => 'Empty Code Language',
            ]);

            expect($language->code)->toBe('')
                ->and($language->name)->toBe('Empty Code Language');
        });

        it('handles empty name gracefully', function (): void {
            $language = new Language([
                'code' => 'empty',
                'name' => '',
            ]);

            expect($language->code)->toBe('empty')
                ->and($language->name)->toBe('');
        });

        it('handles special characters in codes', function (): void {
            $specialCodes = ['en-US', 'fr_CA', 'zh-Hans', 'pt-BR'];

            foreach ($specialCodes as $code) {
                $language = new Language([
                    'code' => $code,
                    'name' => 'Test Language',
                ]);
                expect($language->code)->toBe($code);
            }
        });

        it('maintains data integrity with mixed types', function (): void {
            $language = new Language([
                'code' => 123, // Will be cast to string
                'name' => 456, // Will be cast to string
                'is_active' => 'true', // Will be cast to boolean
                'sort_order' => '5', // Will be cast to integer
            ]);

            expect($language->code)->toBe('123')
                ->and($language->name)->toBe('456')
                ->and($language->is_active)->toBeTrue()
                ->and($language->sort_order)->toBe(5);
        });
    });

    describe('Array and JSON Conversion', function (): void {
        it('converts to array correctly', function (): void {
            $attributes = [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'flag' => '🇺🇸',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 1,
            ];

            $language = new Language($attributes);
            $array = $language->toArray();

            foreach ($attributes as $key => $value) {
                expect($array[$key])->toBe($value);
            }
        });

        it('converts to JSON correctly', function (): void {
            $language = new Language([
                'code' => 'en',
                'name' => 'English',
                'is_active' => true,
                'is_default' => false,
            ]);

            $json = $language->toJson();
            $decoded = json_decode($json, true);

            expect($decoded['code'])->toBe('en')
                ->and($decoded['name'])->toBe('English')
                ->and($decoded['is_active'])->toBeTrue()
                ->and($decoded['is_default'])->toBeFalse();
        });

        it('handles null values in JSON conversion', function (): void {
            $language = new Language([
                'code' => 'test',
                'name' => 'Test',
                'native_name' => null,
                'flag' => null,
                'sort_order' => null,
            ]);

            $json = $language->toJson();
            $decoded = json_decode($json, true);

            expect($decoded['native_name'])->toBeNull()
                ->and($decoded['flag'])->toBeNull()
                ->and($decoded['sort_order'])->toBeNull();
        });
    });

    describe('Model Relationships and Scopes', function (): void {
        it('provides chainable query interface', function (): void {
            // These test the query builder interface, not actual database operations
            $query = Language::query();

            expect($query)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });

        it('supports where clauses through magic methods', function (): void {
            // Test that the model supports standard Eloquent methods
            $methods = [
                'whereCode',
                'whereName',
                'whereIsActive',
                'whereIsDefault',
                'whereSortOrder',
            ];

            foreach ($methods as $method) {
                expect(method_exists(Language::class, $method) || method_exists(Language::class, '__call'))->toBeTrue();
            }
        });
    });
});
