<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\ValueObject;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

class Currency implements JsonSerializable, Stringable
{
    private const CURRENCIES = [
        'EUR' => [
            'symbol' => '€',
            'name' => 'Euro',
            'decimal_places' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'symbol_position' => 'before',
            'flag' => '🇪🇺',
        ],
        'USD' => [
            'symbol' => '$',
            'name' => 'US Dollar',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
            'flag' => '🇺🇸',
        ],
        'GBP' => [
            'symbol' => '£',
            'name' => 'British Pound',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
            'flag' => '🇬🇧',
        ],
        'CHF' => [
            'symbol' => 'CHF',
            'name' => 'Swiss Franc',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => "'",
            'symbol_position' => 'after',
            'flag' => '🇨🇭',
        ],
        'CAD' => [
            'symbol' => 'C$',
            'name' => 'Canadian Dollar',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
            'flag' => '🇨🇦',
        ],
        'AUD' => [
            'symbol' => 'A$',
            'name' => 'Australian Dollar',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
            'flag' => '🇦🇺',
        ],
        'JPY' => [
            'symbol' => '¥',
            'name' => 'Japanese Yen',
            'decimal_places' => 0,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
            'flag' => '🇯🇵',
        ],
        'CNY' => [
            'symbol' => '¥',
            'name' => 'Chinese Yuan',
            'decimal_places' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'symbol_position' => 'before',
            'flag' => '🇨🇳',
        ],
    ];

    private readonly string $code;

    public function __construct(string $code)
    {
        $code = strtoupper($code);

        if (! isset(self::CURRENCIES[$code])) {
            throw new InvalidArgumentException("Invalid currency code: {$code}");
        }

        $this->code = $code;
    }

    public static function fromString(string $code): self
    {
        return new self($code);
    }

    public static function EUR(): self
    {
        return new self('EUR');
    }

    public static function USD(): self
    {
        return new self('USD');
    }

    public static function GBP(): self
    {
        return new self('GBP');
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getSymbol(): string
    {
        return self::CURRENCIES[$this->code]['symbol'];
    }

    public function getName(): string
    {
        return self::CURRENCIES[$this->code]['name'];
    }

    public function getDecimalPlaces(): int
    {
        return self::CURRENCIES[$this->code]['decimal_places'];
    }

    public function getDecimalSeparator(): string
    {
        return self::CURRENCIES[$this->code]['decimal_separator'];
    }

    public function getThousandsSeparator(): string
    {
        return self::CURRENCIES[$this->code]['thousands_separator'];
    }

    public function getSymbolPosition(): string
    {
        return self::CURRENCIES[$this->code]['symbol_position'];
    }

    public function getFlag(): string
    {
        return self::CURRENCIES[$this->code]['flag'];
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }

    public function formatAmount(float $amount): string
    {
        $formatted = number_format(
            $amount,
            $this->getDecimalPlaces(),
            $this->getDecimalSeparator(),
            $this->getThousandsSeparator(),
        );

        if ($this->getSymbolPosition() === 'before') {
            return $this->getSymbol() . $formatted;
        }

        return $formatted . ' ' . $this->getSymbol();
    }

    /**
     * @return array<int, Currency>
     */
    public static function getAvailableCurrencies(): array
    {
        return array_map(
            fn (string $code): Currency => new self($code),
            array_keys(self::CURRENCIES),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function getAvailableCurrenciesData(): array
    {
        $currencies = [];

        foreach (self::CURRENCIES as $code => $data) {
            $currencies[$code] = array_merge(['code' => $code], $data);
        }

        return $currencies;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            ['code' => $this->code],
            self::CURRENCIES[$this->code],
        );
    }

    public function jsonSerialize(): mixed
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
