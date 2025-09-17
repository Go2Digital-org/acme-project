<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\View\Components;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Modules\Currency\Application\Query\GetCurrenciesForViewQuery;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Shared\Application\Query\QueryBusInterface;
use stdClass;

/**
 * Currency selector component using CQRS pattern.
 * Provides optimized currency data with cache-first strategy.
 */
class CurrencySelector extends Component
{
    public ?object $currentCurrency = null;

    /** @var Collection<int, stdClass> */
    public Collection $availableCurrencies;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CurrencyPreferenceService $currencyService,
    ) {
        $this->loadCurrencyData();
    }

    public function render()
    {
        return view('components.currency-selector');
    }

    /**
     * Load currency data via CQRS with error handling.
     */
    private function loadCurrencyData(): void
    {
        try {
            // Get currencies via CQRS with caching
            $readModel = $this->queryBus->ask(new GetCurrenciesForViewQuery);

            // Get current user currency preference
            $currentCurrencyCode = $this->currencyService->getCurrentCurrency()->getCode();

            // Transform to Collection of stdClass for view compatibility
            $currencyData = $readModel->getCurrenciesForDropdown();
            /** @var array<int, array<string, mixed>> $typedData */
            $typedData = $currencyData;
            /** @var Collection<int, stdClass> $availableCurrencies */
            $availableCurrencies = collect($typedData)
                ->map(fn (array $currency): stdClass => (object) $currency);
            $this->availableCurrencies = $availableCurrencies;

            // Find current currency from the loaded currencies
            $this->currentCurrency = $this->availableCurrencies
                ->firstWhere('code', $currentCurrencyCode);
        } catch (Exception $e) {
            // Fallback to safe defaults on any error
            $this->availableCurrencies = collect([]);
            $this->currentCurrency = null;

            // Log the error for debugging
            logger()->error('CurrencySelector component failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
