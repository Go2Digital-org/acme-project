<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Repository;

use InvalidArgumentException;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Repository\CurrencyPreferenceRepositoryInterface;
use Modules\Currency\Domain\ValueObject\Currency as CurrencyValueObject;
use Modules\User\Infrastructure\Laravel\Models\User;

class EloquentCurrencyPreferenceRepository implements CurrencyPreferenceRepositoryInterface
{
    private const PREFERENCE_KEY = 'currency';

    private const DEFAULT_CURRENCY = 'EUR';

    /**
     * Request-level cache for users to prevent N+1 queries
     *
     * @var array<int, User|null>
     */
    private static array $userCache = [];

    public function getUserCurrency(int $userId): ?CurrencyValueObject
    {
        // Use request-level cache to avoid duplicate queries
        if (! isset(self::$userCache[$userId])) {
            self::$userCache[$userId] = User::find($userId);
        }

        $user = self::$userCache[$userId];

        if (! $user) {
            return null;
        }

        /** @var array<string, mixed> $preferences */
        $preferences = is_array($user->preferences) ? $user->preferences : [];

        if (! array_key_exists(self::PREFERENCE_KEY, $preferences)) {
            return null;
        }

        $currencyCode = $preferences[self::PREFERENCE_KEY];

        if (! is_string($currencyCode) || $currencyCode === '') {
            return null;
        }

        try {
            return CurrencyValueObject::fromString($currencyCode);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function setUserCurrency(int $userId, CurrencyValueObject $currency): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        /** @var array<string, mixed> $preferences */
        $preferences = is_array($user->preferences) ? $user->preferences : [];
        $preferences[self::PREFERENCE_KEY] = $currency->getCode();

        $user->updatePreferences($preferences);
    }

    public function removeUserCurrency(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        /** @var array<string, mixed> $preferences */
        $preferences = is_array($user->preferences) ? $user->preferences : [];

        if (array_key_exists(self::PREFERENCE_KEY, $preferences)) {
            unset($preferences[self::PREFERENCE_KEY]);
        }

        $user->updatePreferences($preferences);
    }

    public function getDefaultCurrency(): CurrencyValueObject
    {
        // First try to get from database
        $defaultCurrency = Currency::getDefaultCurrency();

        if ($defaultCurrency instanceof Currency) {
            return $defaultCurrency->toValueObject();
        }

        // Fall back to config
        $defaultCode = config('currency.default', self::DEFAULT_CURRENCY);

        try {
            return CurrencyValueObject::fromString($defaultCode);
        } catch (InvalidArgumentException) {
            return CurrencyValueObject::EUR();
        }
    }
}
