<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates strong password requirements for enterprise security standards.
 */
final readonly class StrongPasswordRule implements ValidationRule
{
    public function __construct(
        private int $minLength = 8,
        private bool $requireUppercase = true,
        private bool $requireLowercase = true,
        private bool $requireNumbers = true,
        private bool $requireSpecialChars = true,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string):PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        if (strlen($value) < $this->minLength) {
            $fail("The :attribute must be at least {$this->minLength} characters long.");

            return;
        }

        if ($this->requireUppercase && ! preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');

            return;
        }

        if ($this->requireLowercase && ! preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');

            return;
        }

        if ($this->requireNumbers && ! preg_match('/\d/', $value)) {
            $fail('The :attribute must contain at least one number.');

            return;
        }

        if ($this->requireSpecialChars && ! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('The :attribute must contain at least one special character.');
        }
    }
}
