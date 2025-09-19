<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Currency\Domain\Model\Currency;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     */
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a unique 3-letter currency code to avoid conflicts
        $code = $this->getUniqueCode();

        return [
            'code' => $code,
            'name' => fake()->sentence(2, false) . ' Currency',
            'symbol' => fake()->randomElement(['$', '€', '£', '¥', 'Fr', 'C$', 'A$', '₹', '₽', 'kr']),
            'flag' => fake()->randomElement(['🇺🇸', '🇪🇺', '🇬🇧', '🇯🇵', '🇨🇦', '🇦🇺', '🇨🇭', '🇮🇳', '🇷🇺', '🇸🇪']),
            'decimal_places' => fake()->randomElement([0, 2]),
            'decimal_separator' => fake()->randomElement(['.', ',']),
            'thousands_separator' => fake()->randomElement([',', ' ', '.']),
            'symbol_position' => fake()->randomElement(['before', 'after']),
            'is_active' => fake()->boolean(85),
            'is_default' => false,
            'exchange_rate' => fake()->randomFloat(4, 0.5, 2.0),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Generate a unique 3-letter currency code.
     */
    private function getUniqueCode(): string
    {
        do {
            $code = strtoupper(fake()->regexify('[A-Z]{3}'));
        } while (Currency::where('code', $code)->exists());

        return $code;
    }

    /**
     * Indicate that the currency is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the currency is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the currency is the default currency.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'is_active' => true,
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'flag' => '🇪🇺',
            'exchange_rate' => 1.0,
            'sort_order' => 1,
        ]);
    }

    /**
     * Create a specific currency by code.
     */
    public function currency(string $code): static
    {
        $currencies = [
            'USD' => [
                'name' => 'US Dollar',
                'symbol' => '$',
                'flag' => '🇺🇸',
                'exchange_rate' => 1.08,
                'decimal_places' => 2,
            ],
            'EUR' => [
                'name' => 'Euro',
                'symbol' => '€',
                'flag' => '🇪🇺',
                'exchange_rate' => 1.0,
                'decimal_places' => 2,
            ],
            'GBP' => [
                'name' => 'British Pound',
                'symbol' => '£',
                'flag' => '🇬🇧',
                'exchange_rate' => 0.86,
                'decimal_places' => 2,
            ],
            'JPY' => [
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'flag' => '🇯🇵',
                'exchange_rate' => 162.0,
                'decimal_places' => 0,
            ],
        ];

        $currencyData = $currencies[strtoupper($code)] ?? $currencies['EUR'];

        return $this->state(fn (array $attributes): array => [
            'code' => strtoupper($code),
            'name' => $currencyData['name'],
            'symbol' => $currencyData['symbol'],
            'flag' => $currencyData['flag'],
            'exchange_rate' => $currencyData['exchange_rate'],
            'decimal_places' => $currencyData['decimal_places'],
            'is_active' => true,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
        ]);
    }

    /**
     * Create USD currency.
     */
    public function usd(): static
    {
        return $this->currency('USD');
    }

    /**
     * Create EUR currency.
     */
    public function eur(): static
    {
        return $this->currency('EUR');
    }

    /**
     * Create GBP currency.
     */
    public function gbp(): static
    {
        return $this->currency('GBP');
    }

    /**
     * Create JPY currency.
     */
    public function jpy(): static
    {
        return $this->currency('JPY');
    }

    /**
     * Create with specific exchange rate.
     */
    public function withExchangeRate(float $rate): static
    {
        return $this->state(fn (array $attributes): array => [
            'exchange_rate' => $rate,
        ]);
    }

    /**
     * Create with specific sort order.
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'sort_order' => $order,
        ]);
    }

    /**
     * Create or update a currency by code to avoid duplicates.
     *
     * @param array<string, mixed> $attributes
     */
    public function createOrUpdate(array $attributes = []): Currency
    {
        $currency = Currency::where('code', $attributes['code'] ?? $this->definition()['code'])->first();

        if ($currency) {
            $currency->update($attributes);
            return $currency;
        }

        /** @var Currency */
        $result = $this->createOne($attributes);

        return $result;
    }
}
