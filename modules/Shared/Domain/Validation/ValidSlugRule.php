<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Validates URL-friendly slugs for campaigns and other entities.
 */
final readonly class ValidSlugRule implements ValidationRule
{
    public function __construct(
        private int $minLength = 3,
        private int $maxLength = 100,
        private bool $allowNumbers = true,
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

        if (strlen($value) > $this->maxLength) {
            $fail("The :attribute must not exceed {$this->maxLength} characters.");

            return;
        }

        $pattern = $this->allowNumbers ? '/^[a-z0-9]+(?:-[a-z0-9]+)*$/' : '/^[a-z]+(?:-[a-z]+)*$/';

        if (! preg_match($pattern, $value)) {
            $fail('The :attribute must be a valid slug format (lowercase letters, numbers if allowed, and hyphens).');

            return;
        }

        if (str_starts_with($value, '-') || str_ends_with($value, '-')) {
            $fail('The :attribute cannot start or end with a hyphen.');
        }
    }
}
