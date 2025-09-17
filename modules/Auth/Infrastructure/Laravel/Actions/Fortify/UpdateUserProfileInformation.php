<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Actions\Fortify;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Modules\User\Infrastructure\Laravel\Models\User;

final class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $validatedData = $validator->validateWithBag('updateProfileInformation');

        // PHPStan level 8 compliance: ensure fields exist and are strings
        /** @var string $name */
        $name = $validatedData['name'];
        /** @var string $email */
        $email = $validatedData['email'];

        if ($email !== $user->email && $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $validatedData);

            return;
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
        ])->save();
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, mixed>  $validatedData
     */
    private function updateVerifiedUser(User $user, array $validatedData): void
    {
        // PHPStan level 8 compliance: ensure fields exist and are strings
        /** @var string $name */
        $name = $validatedData['name'];
        /** @var string $email */
        $email = $validatedData['email'];

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
