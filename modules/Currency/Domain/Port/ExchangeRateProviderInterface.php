<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Port;

use Modules\Currency\Domain\Exception\ExchangeRateProviderException;
use Modules\Currency\Domain\ValueObject\ExchangeRate;

interface ExchangeRateProviderInterface
{
    /**
     * Fetch exchange rates from the provider.
     *
     * @param  string  $baseCurrency  The base currency code (e.g., 'EUR')
     * @return array<string, ExchangeRate> Array of ExchangeRate objects keyed by currency code
     *
     * @throws ExchangeRateProviderException
     */
    public function fetchRates(string $baseCurrency): array;

    /**
     * Check if the provider is available and configured.
     */
    public function isAvailable(): bool;

    /**
     * Get the provider name for logging and identification.
     */
    public function getName(): string;

    /**
     * Get the provider's priority (lower number = higher priority).
     */
    public function getPriority(): int;
}
