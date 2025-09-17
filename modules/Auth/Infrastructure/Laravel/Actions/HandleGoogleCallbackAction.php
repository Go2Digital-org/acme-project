<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Actions;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use Modules\User\Infrastructure\Laravel\Models\User;

class HandleGoogleCallbackAction
{
    public function execute(): User
    {
        /** @var \Laravel\Socialite\Two\User $googleUser */
        $googleUser = Socialite::driver('google')->user();

        // Ensure we have required fields from Google
        if (! $googleUser->email || ! $googleUser->name || ! $googleUser->id) {
            throw new InvalidArgumentException('Google user data is incomplete - missing email, name, or ID.');
        }

        /** @var string $email */
        $email = $googleUser->email;
        /** @var string $name */
        $name = $googleUser->name;
        $googleId = (string) $googleUser->id;

        $user = User::where('email', $email)->first();

        if (! $user) {
            return User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        if (! $user->google_id) {
            $user->update(['google_id' => $googleId]);
        }

        return $user;
    }
}
