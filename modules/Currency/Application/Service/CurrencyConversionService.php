<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Service;

use Modules\Currency\Domain\Exception\CurrencyConversionException;
use Modules\Currency\Domain\Model\Currency;

class CurrencyConversionService
{
    private const BASE_CURRENCY = 'EUR';

    public function __construct(
        private readonly CurrencyPreferenceService $preferenceService,
    ) {}

    /**
     * Convert amount between two currencies.
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return $amount;
        }

        // Get exchange rates
        $fromRate = $this->getExchangeRate($fromCurrency, self::BASE_CURRENCY);
        $toRate = $this->getExchangeRate(self::BASE_CURRENCY, $toCurrency);

        // Convert through base currency (EUR)
        $baseAmount = $amount / $fromRate;

        return $baseAmount * $toRate;
    }

    /**
     * Convert amount to user's preferred currency.
     *
     * @return array<string, mixed>
     */
    public function convertToUserCurrency(float $amount, string $fromCurrency = 'EUR'): array
    {
        $userCurrency = $this->preferenceService->getCurrentCurrency();
        $userCurrencyCode = $userCurrency->getCode();

        if ($fromCurrency === $userCurrencyCode) {
            return [
                'amount' => $amount,
                'currency' => $userCurrencyCode,
                'formatted' => $this->preferenceService->formatAmount($amount, $userCurrency),
            ];
        }

        $convertedAmount = $this->convertAmount($amount, $fromCurrency, $userCurrencyCode);

        return [
            'amount' => $convertedAmount,
            'currency' => $userCurrencyCode,
            'formatted' => $this->preferenceService->formatAmount($convertedAmount, $userCurrency),
        ];
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // If converting from base currency
        if ($fromCurrency === self::BASE_CURRENCY) {
            $currency = Currency::findByCode($toCurrency);

            if (! $currency instanceof Currency) {
                throw new CurrencyConversionException("Currency {$toCurrency} not found");
            }

            return $currency->exchange_rate;
        }

        // If converting to base currency
        if ($toCurrency === self::BASE_CURRENCY) {
            $currency = Currency::findByCode($fromCurrency);

            if (! $currency instanceof Currency) {
                throw new CurrencyConversionException("Currency {$fromCurrency} not found");
            }

            return 1 / $currency->exchange_rate;
        }

        // Converting between two non-base currencies
        $fromCurrency = Currency::findByCode($fromCurrency);
        $toCurrency = Currency::findByCode($toCurrency);

        if (! $fromCurrency instanceof Currency || ! $toCurrency instanceof Currency) {
            throw new CurrencyConversionException('Currency not found');
        }

        // Convert through base currency
        return $toCurrency->exchange_rate / $fromCurrency->exchange_rate;
    }

    /**
     * Clear exchange rate cache.
     */
    public function clearCache(): void
    {
        // Cache clearing removed - no longer using cache
    }

    /**
     * Get all available currencies with their exchange rates.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableCurrencies(): array
    {
        return Currency::active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($currency): array => [
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'exchange_rate' => $currency->exchange_rate,
                'is_default' => $currency->is_default,
            ])
            ->toArray();
    }

    /**
     * Format amount in specified currency.
     */
    public function formatInCurrency(float $amount, string $currencyCode): string
    {
        $currency = Currency::findByCode($currencyCode);

        if (! $currency instanceof Currency) {
            throw new CurrencyConversionException("Currency {$currencyCode} not found");
        }

        return $currency->formatAmount($amount);
    }
}
