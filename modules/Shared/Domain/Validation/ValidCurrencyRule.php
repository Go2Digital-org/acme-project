<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates ISO 4217 currency codes.
 */
final readonly class ValidCurrencyRule implements ValidationRule
{
    /** @var array<string> */
    private array $supportedCurrencies;

    /**
     * @param  array<string>|null  $supportedCurrencies
     */
    public function __construct(?array $supportedCurrencies = null)
    {
        $this->supportedCurrencies = $supportedCurrencies ?? [
            'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY', 'CHF', 'SEK', 'NOK', 'DKK',
            'PLN', 'CZK', 'HUF', 'BGN', 'RON', 'HRK', 'RSD', 'MKD', 'BAM', 'ALL',
        ];
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid currency code.');

            return;
        }

        $currency = strtoupper($value);

        if (strlen($currency) !== 3) {
            $fail('The :attribute must be a 3-letter currency code.');

            return;
        }

        if (! in_array($currency, $this->supportedCurrencies, true)) {
            $fail('The :attribute must be a supported currency code.');
        }
    }
}
