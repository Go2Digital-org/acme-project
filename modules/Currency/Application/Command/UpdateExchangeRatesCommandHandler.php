<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Command;

use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Currency\Domain\Event\ExchangeRatesUpdatedEvent;
use Modules\Currency\Domain\Exception\ExchangeRateProviderException;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Port\ExchangeRateProviderInterface;
use Modules\Currency\Domain\ValueObject\ExchangeRate;

final readonly class UpdateExchangeRatesCommandHandler
{
    /**
     * @param  ExchangeRateProviderInterface[]  $providers
     */
    public function __construct(
        private array $providers,
    ) {}

    public function handle(UpdateExchangeRatesCommand $command): void
    {
        if (! $command->forceUpdate && ! $this->shouldUpdate()) {
            Log::info('Exchange rates are up to date, skipping update');

            return;
        }

        $providers = $this->getSortedProviders($command->preferredProvider);

        if ($providers === []) {
            throw ExchangeRateProviderException::noProvidersAvailable();
        }

        $rates = null;
        $successfulProvider = null;
        $lastException = null;

        foreach ($providers as $provider) {
            if (! $provider->isAvailable()) {
                Log::warning("Exchange rate provider '{$provider->getName()}' is not available");

                continue;
            }

            try {
                Log::info("Attempting to fetch exchange rates from '{$provider->getName()}'");
                $rates = $provider->fetchRates($command->baseCurrency);
                $successfulProvider = $provider->getName();

                Log::info("Successfully fetched exchange rates from '{$successfulProvider}'", [
                    'count' => count($rates),
                ]);

                break;
            } catch (Exception $e) {
                $lastException = $e;
                Log::error("Failed to fetch rates from '{$provider->getName()}'", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($rates === null) {
            if ($lastException instanceof Exception) {
                throw $lastException;
            }

            throw ExchangeRateProviderException::noProvidersAvailable();
        }

        $this->updateDatabaseRates($rates, $command->baseCurrency, $successfulProvider);
        $this->clearCache();

        event(new ExchangeRatesUpdatedEvent(
            $this->formatRatesForEvent($rates),
            $command->baseCurrency,
            $successfulProvider,
            new DateTimeImmutable,
        ));

        Log::info('Exchange rates updated successfully', [
            'provider' => $successfulProvider,
            'rates_count' => count($rates),
        ]);
    }

    /**
     * @return ExchangeRateProviderInterface[]
     */
    private function getSortedProviders(?string $preferredProvider): array
    {
        if ($this->providers === []) {
            return [];
        }

        $sorted = $this->providers;

        // Sort by priority (lower number = higher priority)
        usort($sorted, fn ($a, $b): int => $a->getPriority() <=> $b->getPriority());

        // If preferred provider is specified, move it to the front
        if ($preferredProvider) {
            $preferred = array_filter(
                $sorted,
                fn (ExchangeRateProviderInterface $p): bool => $p->getName() === $preferredProvider,
            );
            $others = array_filter(
                $sorted,
                fn (ExchangeRateProviderInterface $p): bool => $p->getName() !== $preferredProvider,
            );

            $sorted = array_merge(array_values($preferred), array_values($others));
        }

        return $sorted;
    }

    private function shouldUpdate(): bool
    {
        // Always update when called - cache checking removed
        return true;
    }

    /**
     * @param  array<string, ExchangeRate>  $rates
     */
    private function updateDatabaseRates(array $rates, string $baseCurrency, string $provider): void
    {
        DB::transaction(function () use ($rates, $baseCurrency, $provider): void {
            $timestamp = now();

            foreach ($rates as $currencyCode => $exchangeRate) {
                Currency::where('code', $currencyCode)
                    ->update([
                        'exchange_rate' => $exchangeRate->getRate(),
                        'updated_at' => $timestamp,
                    ]);

                // Store in history table
                DB::table('exchange_rate_history')->insert([
                    'base_currency' => $baseCurrency,
                    'target_currency' => $currencyCode,
                    'rate' => $exchangeRate->getRate(),
                    'provider' => $provider,
                    'fetched_at' => $timestamp,
                    'created_at' => $timestamp,
                ]);
            }
        });
    }

    private function clearCache(): void
    {
        // Cache clearing removed - no longer using cache
    }

    /**
     * @param  array<string, ExchangeRate>  $rates
     * @return array<string, float>
     */
    private function formatRatesForEvent(array $rates): array
    {
        $formatted = [];
        foreach ($rates as $code => $rate) {
            $formatted[$code] = $rate->getRate();
        }

        return $formatted;
    }
}
