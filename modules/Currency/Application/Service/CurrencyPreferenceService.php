<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Modules\Currency\Domain\Repository\CurrencyPreferenceRepositoryInterface;
use Modules\Currency\Domain\ValueObject\Currency;

class CurrencyPreferenceService
{
    private const SESSION_KEY = 'user_currency';

    /**
     * Cache for the current request to avoid repeated lookups
     */
    private ?Currency $currentCurrencyCache = null;

    public function __construct(
        private readonly CurrencyPreferenceRepositoryInterface $repository,
    ) {}

    public function getCurrentCurrency(): Currency
    {
        // Return cached value if already determined in this request
        if ($this->currentCurrencyCache instanceof Currency) {
            return $this->currentCurrencyCache;
        }

        // Priority: User preference > Session > Default
        if (Auth::check()) {
            $userId = Auth::id();

            if (is_int($userId)) {
                $userCurrency = $this->repository->getUserCurrency($userId);

                if ($userCurrency instanceof Currency) {
                    $this->currentCurrencyCache = $userCurrency;

                    return $userCurrency;
                }
            }
        }

        $sessionCurrency = Session::get(self::SESSION_KEY);

        if ($sessionCurrency) {
            try {
                $currency = Currency::fromString($sessionCurrency);
                $this->currentCurrencyCache = $currency;

                return $currency;
            } catch (InvalidArgumentException) {
                // Invalid session currency, clear it
                Session::forget(self::SESSION_KEY);
            }
        }

        $defaultCurrency = $this->repository->getDefaultCurrency();
        $this->currentCurrencyCache = $defaultCurrency;

        return $defaultCurrency;
    }

    public function setCurrentCurrency(Currency $currency): void
    {
        // Clear the cache when currency is changed
        $this->currentCurrencyCache = null;

        // Store in session for all users
        Session::put(self::SESSION_KEY, $currency->getCode());

        // Store in database for authenticated users
        if (Auth::check()) {
            $userId = Auth::id();

            if (is_int($userId)) {
                $this->repository->setUserCurrency($userId, $currency);
            }
        }

        // Update cache with the new currency
        $this->currentCurrencyCache = $currency;
    }

    public function getUserCurrency(int $userId): ?Currency
    {
        return $this->repository->getUserCurrency($userId);
    }

    public function setUserCurrency(int $userId, Currency $currency): void
    {
        $this->repository->setUserCurrency($userId, $currency);
    }

    public function clearCurrentCurrency(): void
    {
        // Clear the cache
        $this->currentCurrencyCache = null;

        Session::forget(self::SESSION_KEY);

        if (Auth::check()) {
            $userId = Auth::id();

            if (is_int($userId)) {
                $this->repository->removeUserCurrency($userId);
            }
        }
    }

    /**
     * @return array<int, Currency>
     */
    public function getAvailableCurrencies(): array
    {
        return Currency::getAvailableCurrencies();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAvailableCurrenciesData(): array
    {
        return Currency::getAvailableCurrenciesData();
    }

    public function formatAmount(float $amount, ?Currency $currency = null): string
    {
        $currency ??= $this->getCurrentCurrency();

        return $currency->formatAmount($amount);
    }

    public function getDefaultCurrency(): Currency
    {
        return $this->repository->getDefaultCurrency();
    }
}
