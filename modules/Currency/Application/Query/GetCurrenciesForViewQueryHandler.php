<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use InvalidArgumentException;
use Modules\Currency\Application\ReadModel\CurrenciesViewReadModel;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Repository\CurrencyQueryRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use stdClass;

/**
 * Handler for getting optimized currency data for frontend views.
 * Implements cache-first strategy with N+1 prevention.
 */
final readonly class GetCurrenciesForViewQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CurrencyQueryRepositoryInterface $currencyRepository,
        private CurrencyPreferenceService $currencyPreferenceService
    ) {}

    public function handle(QueryInterface $query): CurrenciesViewReadModel
    {
        if (! $query instanceof GetCurrenciesForViewQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Force refresh cache if requested
        if ($query->forceRefresh) {
            $this->currencyRepository->clearCache();
        }

        // Get optimized currency data from repository
        $currencies = $this->currencyRepository->getCurrenciesForView();
        $defaultCurrency = $this->currencyRepository->getDefaultCurrency();

        // Get current user currency preference
        $currentCurrencyCode = $this->currencyPreferenceService->getCurrentCurrency()->getCode();
        $currentCurrency = $currencies->firstWhere('code', $currentCurrencyCode);

        // Build ReadModel data
        $readModelData = [
            'currencies_dropdown' => $currencies->map(fn (stdClass $currency): array => [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'flag' => $currency->flag,
                'is_default' => $currency->is_default,
                'is_active' => $currency->is_active,
                'sort_order' => $currency->sort_order,
            ])->toArray(),
            'current_currency' => $currentCurrency ? [
                'id' => $currentCurrency->id,
                'code' => $currentCurrency->code,
                'name' => $currentCurrency->name,
                'symbol' => $currentCurrency->symbol,
                'flag' => $currentCurrency->flag,
                'is_default' => $currentCurrency->is_default,
            ] : null,
            'default_currency' => $defaultCurrency instanceof Currency ? [
                'id' => $defaultCurrency->id,
                'code' => $defaultCurrency->code,
                'name' => $defaultCurrency->name,
                'symbol' => $defaultCurrency->symbol,
                'flag' => $defaultCurrency->flag,
                'is_default' => true,
            ] : null,
        ];

        return new CurrenciesViewReadModel(
            id: 'currencies_view',
            data: $readModelData
        );
    }
}
