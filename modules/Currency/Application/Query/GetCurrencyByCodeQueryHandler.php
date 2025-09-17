<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use InvalidArgumentException;
use Modules\Currency\Application\ReadModel\AvailableCurrenciesReadModel;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Repository\CurrencyQueryRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for finding a specific currency by code with caching.
 */
final readonly class GetCurrencyByCodeQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CurrencyQueryRepositoryInterface $currencyRepository
    ) {}

    public function handle(QueryInterface $query): AvailableCurrenciesReadModel
    {
        if (! $query instanceof GetCurrencyByCodeQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Force refresh cache if requested
        if ($query->forceRefresh) {
            $this->currencyRepository->clearCache();
        }

        // Find currency by code
        $currency = $this->currencyRepository->findByCode($query->code);

        $currencyData = [];
        if ($currency instanceof Currency) {
            $currencyData[$currency->code] = [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
                'symbol' => $currency->symbol,
                'flag' => $currency->flag,
                'is_default' => $currency->is_default,
                'is_active' => $currency->is_active,
                'exchange_rate' => $currency->exchange_rate,
                'sort_order' => $currency->sort_order,
            ];
        }

        return new AvailableCurrenciesReadModel(
            id: "currency_{$query->code}",
            data: [
                'currencies' => $currencyData,
            ]
        );
    }
}
