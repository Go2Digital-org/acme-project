<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates monetary amounts with proper decimal precision.
 */
final readonly class MoneyAmountRule implements ValidationRule
{
    public function __construct(
        private float $min = 0.01,
        private ?float $max = null,
        private int $decimalPlaces = 2,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            $fail('The :attribute must be a valid monetary amount.');

            return;
        }

        $numericValue = (float) $value;

        if ($numericValue < $this->min) {
            $fail("The :attribute must be at least {$this->min}.");

            return;
        }

        if ($this->max !== null && $numericValue > $this->max) {
            $fail("The :attribute must not exceed {$this->max}.");

            return;
        }

        // Check decimal places
        $stringValue = (string) $value;
        $dotPosition = strrchr($stringValue, '.');

        if ($dotPosition !== false) {
            $decimalPart = substr($dotPosition, 1);

            if (strlen($decimalPart) > $this->decimalPlaces) {
                $fail("The :attribute must not have more than {$this->decimalPlaces} decimal places.");
            }
        }
    }
}
