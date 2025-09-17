<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Modules\User\Infrastructure\Laravel\Models\User;

final readonly class TwoFactorService
{
    public function disableTwoFactor(int $userId): void
    {
        $user = User::findOrFail($userId);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /** @return array<int, string> */
    public function getRecoveryCodes(int $userId): array
    {
        $user = User::findOrFail($userId);

        $codes = $user->recoveryCodes();

        // Ensure we return string array for recovery codes
        return array_values(array_filter($codes, 'is_string'));
    }

    /** @return array<int, string> */
    public function regenerateRecoveryCodes(int $userId): array
    {
        $user = User::findOrFail($userId);

        $user->replaceRecoveryCodes();

        $codes = $user->recoveryCodes();

        // Ensure we return string array for recovery codes
        return array_values(array_filter($codes, 'is_string'));
    }

    public function isTwoFactorEnabled(int $userId): bool
    {
        $user = User::findOrFail($userId);

        return ! empty($user->two_factor_secret);
    }

    public function hasRecoveryCodes(int $userId): bool
    {
        $user = User::findOrFail($userId);

        return ! empty($user->two_factor_recovery_codes);
    }
}
