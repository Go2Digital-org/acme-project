<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\User\Infrastructure\Laravel\Models\User;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ]);

        $validatedData = $validator->validate();

        // PHPStan level 8 compliance: ensure array keys exist and are strings
        /** @var string $name */
        $name = $validatedData['name'];
        /** @var string $email */
        $email = $validatedData['email'];
        /** @var string $password */
        $password = $validatedData['password'];

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}
