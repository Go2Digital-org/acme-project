<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Currency\Domain\Model\Currency;
use Modules\User\Infrastructure\Laravel\Models\User;

uses(RefreshDatabase::class);

describe('Currency Model Feature Tests', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
    });

    describe('Currency Model', function (): void {
        it('creates currency with factory', function (): void {
            $currency = Currency::factory()->create();

            expect($currency)->toBeInstanceOf(Currency::class);
            expect($currency->code)->toBeString();
            expect($currency->name)->toBeString();
            expect($currency->symbol)->toBeString();
        });

        it('creates active currency', function (): void {
            $currency = Currency::factory()->active()->create();

            expect($currency->is_active)->toBeTrue();
        });

        it('creates default currency', function (): void {
            // Ensure no existing default currency
            Currency::where('is_default', true)->delete();

            $currency = Currency::factory()->default()->create();

            expect($currency->is_default)->toBeTrue();
            expect($currency->is_active)->toBeTrue();
            expect($currency->code)->toBe('EUR');
        });

        it('creates specific currency by code', function (): void {
            $currency = Currency::factory()->usd()->create();

            expect($currency->code)->toBe('USD');
            expect($currency->name)->toBe('US Dollar');
            expect($currency->symbol)->toBe('$');
        });

        it('formats amount correctly', function (): void {
            $currency = Currency::factory()->usd()->create();

            $formatted = $currency->formatAmount(123.45);

            expect($formatted)->toBeString();
            expect($formatted)->toContain('123.45');
            expect($formatted)->toContain('$');
        });

        it('handles symbol position correctly', function (): void {
            $currency = Currency::factory()->create([
                'symbol' => '€',
                'symbol_position' => 'after',
            ]);

            $formatted = $currency->formatAmount(100.00);

            expect($formatted)->toEndWith(' €');
        });

        it('converts currency correctly', function (): void {
            $usd = Currency::factory()->usd()->create(['exchange_rate' => 1.08]);
            $eur = Currency::factory()->eur()->create(['exchange_rate' => 1.0]);

            $converted = $usd->convertTo(108.0, $eur);

            expect($converted)->toBe(100.0);
        });

        it('finds currency by code', function (): void {
            Currency::factory()->gbp()->create();

            $found = Currency::findByCode('GBP');

            expect($found)->toBeInstanceOf(Currency::class);
            expect($found->code)->toBe('GBP');
        });

        it('gets default currency', function (): void {
            // Ensure no existing default currency
            Currency::where('is_default', true)->delete();

            Currency::factory()->default()->create();

            $default = Currency::getDefaultCurrency();

            expect($default)->toBeInstanceOf(Currency::class);
            expect($default->is_default)->toBeTrue();
        });

        it('scopes active currencies', function (): void {
            Currency::factory()->active()->create();
            Currency::factory()->inactive()->create();

            $activeCurrencies = Currency::active()->get();

            expect($activeCurrencies)->toHaveCount(1);
            expect($activeCurrencies->first()->is_active)->toBeTrue();
        });

        it('gets active currencies ordered', function (): void {
            Currency::factory()->active()->create(['sort_order' => 2]);
            Currency::factory()->active()->create(['sort_order' => 1]);
            Currency::factory()->inactive()->create();

            $currencies = Currency::getActiveCurrencies();

            expect($currencies)->toHaveCount(2);
            expect($currencies->first()->sort_order)->toBe(1);
        });

        it('checks if currency is default', function (): void {
            // Ensure no existing default currency
            Currency::where('is_default', true)->delete();

            $default = Currency::factory()->default()->create();
            $regular = Currency::factory()->create();

            expect($default->isDefault())->toBeTrue();
            expect($regular->isDefault())->toBeFalse();
        });

        it('checks if currency is active', function (): void {
            $active = Currency::factory()->active()->create();
            $inactive = Currency::factory()->inactive()->create();

            expect($active->isActive())->toBeTrue();
            expect($inactive->isActive())->toBeFalse();
        });

        it('gets display name with flag', function (): void {
            $currency = Currency::factory()->usd()->create();

            $displayName = $currency->getDisplayName();

            expect($displayName)->toContain('🇺🇸');
            expect($displayName)->toContain('USD');
            expect($displayName)->toContain('US Dollar');
        });

        it('converts to value object', function (): void {
            $currency = Currency::factory()->eur()->create();

            $valueObject = $currency->toValueObject();

            expect($valueObject)->toBeInstanceOf(\Modules\Currency\Domain\ValueObject\Currency::class);
        });

        it('handles decimal places for JPY', function (): void {
            $jpy = Currency::factory()->jpy()->create();

            expect($jpy->decimal_places)->toBe(0);

            $formatted = $jpy->formatAmount(1000);
            expect($formatted)->not()->toContain('.');
        });

        it('handles exchange rate calculations', function (): void {
            $base = Currency::factory()->create(['exchange_rate' => 1.0]);
            $target = Currency::factory()->create(['exchange_rate' => 2.0]);

            $result = $base->convertTo(100, $target);

            expect($result)->toBe(200.0);
        });

        it('validates currency code case sensitivity', function (): void {
            Currency::factory()->usd()->create();

            $found = Currency::findByCode('usd');

            expect($found)->toBeInstanceOf(Currency::class);
            expect($found->code)->toBe('USD');
        });

        it('handles null return for non-existent currency', function (): void {
            $notFound = Currency::findByCode('INVALID');

            expect($notFound)->toBeNull();
        });

        it('maintains sort order consistency', function (): void {
            $first = Currency::factory()->create(['sort_order' => 1]);
            $second = Currency::factory()->create(['sort_order' => 2]);

            $ordered = Currency::orderBy('sort_order')->get();

            expect($ordered->first()->id)->toBe($first->id);
            expect($ordered->last()->id)->toBe($second->id);
        });
    });
});
