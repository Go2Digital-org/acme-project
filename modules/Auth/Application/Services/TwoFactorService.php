<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\User\Infrastructure\Laravel\Models\User;

final class TwoFactorService
{
    public function disableTwoFactor(int $userId, ?string $password = null): void
    {
        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        $user = User::findOrFail($userId);

        // Require password confirmation for security-critical operation
        if ($password !== null && ! Hash::check($password, $user->password)) {
            Log::warning('2FA disable attempt with invalid password', [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            throw new InvalidArgumentException('Invalid password provided');
        }

        // Clear 2FA data
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        // Log security event
        Log::info('Two-factor authentication disabled', [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * @return array<string>
     */
    public function getRecoveryCodes(int $userId): array
    {
        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        $user = User::findOrFail($userId);

        // Security check: Only allow access to own recovery codes unless admin
        $currentUser = auth()->user();
        if (! $currentUser || ($currentUser->id !== $userId && ! $currentUser->hasRole('admin'))) {
            Log::warning('Unauthorized recovery codes access attempt', [
                'user_id' => $userId,
                'current_user_id' => $currentUser?->id,
                'ip_address' => request()->ip(),
            ]);
            throw new InvalidArgumentException('Unauthorized access to recovery codes');
        }

        $codes = $user->recoveryCodes();

        // Log access for audit trail
        Log::info('Recovery codes accessed', [
            'user_id' => $userId,
            'accessed_by' => $currentUser->id,
            'ip_address' => request()->ip(),
        ]);

        // Ensure we return string array for recovery codes
        return array_values(array_filter($codes, 'is_string'));
    }

    /**
     * @return array<string>
     */
    public function regenerateRecoveryCodes(int $userId, string $password): array
    {
        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        $user = User::findOrFail($userId);

        // Require password confirmation for security-critical operation
        if (! Hash::check($password, $user->password)) {
            Log::warning('Recovery codes regeneration attempt with invalid password', [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            throw new InvalidArgumentException('Invalid password provided');
        }

        $user->replaceRecoveryCodes();

        $codes = $user->recoveryCodes();

        // Log security event
        Log::info('Recovery codes regenerated', [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Ensure we return string array for recovery codes
        return array_values(array_filter($codes, 'is_string'));
    }

    public function isTwoFactorEnabled(int $userId): bool
    {
        // Validate user ID
        if ($userId <= 0) {
            return false;
        }

        $user = User::find($userId);

        return $user && ! empty($user->two_factor_secret);
    }

    public function hasRecoveryCodes(int $userId): bool
    {
        // Validate user ID
        if ($userId <= 0) {
            return false;
        }

        $user = User::find($userId);

        return $user && ! empty($user->two_factor_recovery_codes);
    }

    public function enableTwoFactor(int $userId, string $secret, string $password): void
    {
        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        // Validate secret
        if ($secret === '' || $secret === '0' || strlen($secret) < 16) {
            throw new InvalidArgumentException('Invalid 2FA secret provided');
        }

        $user = User::findOrFail($userId);

        // Require password confirmation
        if (! Hash::check($password, $user->password)) {
            Log::warning('2FA enable attempt with invalid password', [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            throw new InvalidArgumentException('Invalid password provided');
        }

        // Set 2FA data
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();

        // Generate recovery codes
        $user->replaceRecoveryCodes();

        // Log security event
        Log::info('Two-factor authentication enabled', [
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
