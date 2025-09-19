<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\ViewComposer;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Currency\Application\Query\GetCurrenciesForViewQuery;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Shared\Application\Query\QueryBusInterface;
use stdClass;

/**
 * Currency dropdown view composer using CQRS pattern.
 * Provides pre-processed currency data to eliminate PHP logic in views.
 */
final readonly class CurrencyDropdownComposer
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private CurrencyPreferenceService $currencyPreferenceService
    ) {}

    /**
     * Bind data to the view.
     * Uses CQRS to get optimized currency data with cache-first strategy.
     */
    public function compose(View $view): void
    {
        try {
            // Get currencies via CQRS with caching
            $readModel = $this->queryBus->ask(new GetCurrenciesForViewQuery);

            // Get current user currency preference
            $currentCurrencyCode = $this->currencyPreferenceService->getCurrentCurrency()->getCode();

            // Transform ReadModel data for view compatibility
            $currencyData = $readModel->getCurrenciesForDropdown();
            /** @var array<string, mixed> $typedData */
            $typedData = $currencyData;
            /** @var Collection<int, stdClass> $currencies */
            $currencies = collect($typedData)
                ->map(fn (array $currency): stdClass => (object) $currency);

            // Pre-calculate current currency symbol to avoid PHP logic in view
            $currentCurrencyRecord = $currencies->firstWhere('code', $currentCurrencyCode);
            $currentCurrencySymbol = $currentCurrencyRecord !== null ? $currentCurrencyRecord->symbol : '💱';

            $view->with([
                'currencies' => $currencies,
                'currentCurrency' => $currentCurrencyCode,
                'currentCurrencySymbol' => $currentCurrencySymbol,
                'defaultCurrency' => $readModel->getDefaultCurrency(),
            ]);
        } catch (Exception $e) {
            // Fallback to safe defaults on any error
            $view->with([
                'currencies' => collect([]),
                'currentCurrency' => 'EUR', // Safe fallback
                'currentCurrencySymbol' => '€', // Safe fallback symbol
                'defaultCurrency' => null,
            ]);

            // Log the error for debugging
            logger()->error('Currency dropdown composer failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
