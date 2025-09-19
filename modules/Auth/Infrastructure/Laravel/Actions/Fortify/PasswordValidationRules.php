<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Actions\Fortify;

use Illuminate\Validation\Rules\Password;
use Modules\Shared\Domain\Validation\StrongPasswordRule;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'max:128',
            'confirmed',
            Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
            new StrongPasswordRule,
        ];
    }

    /**
     * Get registration-specific password rules (potentially stricter).
     *
     * @return array<int, mixed>
     */
    protected function registrationPasswordRules(): array
    {
        return [
            'required',
            'string',
            'min:10',
            'max:128',
            'confirmed',
            Password::min(10)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
            new StrongPasswordRule,
        ];
    }

    /**
     * Get admin password rules (most strict).
     *
     * @return array<int, mixed>
     */
    protected function adminPasswordRules(): array
    {
        return [
            'required',
            'string',
            'min:12',
            'max:128',
            'confirmed',
            Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
            new StrongPasswordRule,
        ];
    }
}
