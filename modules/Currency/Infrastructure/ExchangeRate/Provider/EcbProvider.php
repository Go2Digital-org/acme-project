<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ExchangeRate\Provider;

use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Currency\Domain\Exception\ExchangeRateProviderException;
use Modules\Currency\Domain\Port\ExchangeRateProviderInterface;
use Modules\Currency\Domain\ValueObject\ExchangeRate;
use SimpleXMLElement;

/**
 * European Central Bank Exchange Rate Provider.
 * Provides free daily exchange rates with EUR as base currency.
 */
class EcbProvider implements ExchangeRateProviderInterface
{
    private const ECB_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    private const PROVIDER_NAME = 'ECB';

    private const PRIORITY = 1; // High priority as it's free and reliable

    public function fetchRates(string $baseCurrency): array
    {
        if ($baseCurrency !== 'EUR') {
            throw new ExchangeRateProviderException(
                'ECB provider only supports EUR as base currency',
            );
        }

        try {
            $response = Http::timeout(10)->get(self::ECB_URL);

            if (! $response->successful()) {
                throw ExchangeRateProviderException::fetchFailed(
                    self::PROVIDER_NAME,
                    "HTTP {$response->status()}",
                );
            }

            $xml = new SimpleXMLElement($response->body());

            return $this->parseXmlResponse($xml);
        } catch (Exception $e) {
            if ($e instanceof ExchangeRateProviderException) {
                throw $e;
            }

            throw ExchangeRateProviderException::fetchFailed(
                self::PROVIDER_NAME,
                $e->getMessage(),
            );
        }
    }

    public function isAvailable(): bool
    {
        // ECB is always available as it's a free service
        return true;
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    /**
     * @return array<string, ExchangeRate>
     */
    private function parseXmlResponse(SimpleXMLElement $xml): array
    {
        $rates = [];
        $timestamp = new DateTimeImmutable;

        // Navigate to the Cube elements
        $cubes = $xml->Cube->Cube;

        if (! $cubes) {
            throw ExchangeRateProviderException::invalidResponse(
                self::PROVIDER_NAME,
                'No exchange rate data found in XML',
            );
        }

        // Get the date from the time attribute
        if (isset($cubes['time'])) {
            $timestamp = new DateTimeImmutable((string) $cubes['time']);
        }

        // Add EUR to EUR rate (always 1)
        $rates['EUR'] = new ExchangeRate(
            'EUR',
            'EUR',
            1.0,
            $timestamp,
            self::PROVIDER_NAME,
        );

        // Parse each currency rate
        foreach ($cubes->Cube as $cube) {
            $currency = (string) $cube['currency'];
            $rate = (float) $cube['rate'];

            if ($currency === '' || $currency === '0' || $rate <= 0) {
                Log::warning('Invalid rate data from ECB', [
                    'currency' => $currency,
                    'rate' => $rate,
                ]);

                continue;
            }

            $rates[$currency] = new ExchangeRate(
                'EUR',
                $currency,
                $rate,
                $timestamp,
                self::PROVIDER_NAME,
            );
        }

        if (count($rates) < 2) {
            throw ExchangeRateProviderException::invalidResponse(
                self::PROVIDER_NAME,
                'Insufficient exchange rate data',
            );
        }

        Log::info('Successfully parsed ECB exchange rates', [
            'count' => count($rates),
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
        ]);

        return $rates;
    }
}
