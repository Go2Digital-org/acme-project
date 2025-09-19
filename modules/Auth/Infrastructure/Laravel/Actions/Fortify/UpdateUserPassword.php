<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Modules\User\Infrastructure\Laravel\Models\User;

final class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $validator = Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ]);

        $validatedData = $validator->validateWithBag('updatePassword');

        // PHPStan level 8 compliance: ensure password field exists and is string
        /** @var string $password */
        $password = $validatedData['password'];

        $user->forceFill([
            'password' => Hash::make($password),
        ])->save();
    }
}
