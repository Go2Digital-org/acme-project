<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ExchangeRate\Provider;

use DateTimeImmutable;
use Modules\Currency\Domain\Port\ExchangeRateProviderInterface;
use Modules\Currency\Domain\ValueObject\ExchangeRate;
use RuntimeException;

/**
 * Fallback provider that uses static exchange rates from configuration.
 */
class ConfigProvider implements ExchangeRateProviderInterface
{
    private const PROVIDER_NAME = 'Config';

    private const PRIORITY = 999; // Lowest priority (fallback)

    public function fetchRates(string $baseCurrency): array
    {
        $configRates = config('currency.exchange_rates', []);
        $rates = [];
        $timestamp = new DateTimeImmutable;

        if ($baseCurrency !== 'EUR') {
            // Convert rates to use different base currency
            $baseRate = $configRates[$baseCurrency] ?? null;
            if (! $baseRate) {
                throw new RuntimeException("Base currency {$baseCurrency} not found in config");
            }

            foreach ($configRates as $currency => $rate) {
                $convertedRate = $rate / $baseRate;
                $rates[$currency] = new ExchangeRate(
                    $baseCurrency,
                    $currency,
                    $convertedRate,
                    $timestamp,
                    self::PROVIDER_NAME,
                );
            }
        } else {
            // Use EUR-based rates directly from config
            foreach ($configRates as $currency => $rate) {
                $rates[$currency] = new ExchangeRate(
                    'EUR',
                    $currency,
                    $rate,
                    $timestamp,
                    self::PROVIDER_NAME,
                );
            }
        }

        return $rates;
    }

    public function isAvailable(): bool
    {
        return ! empty(config('currency.exchange_rates'));
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }
}
