<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use InvalidArgumentException;
use Modules\Currency\Application\ReadModel\AvailableCurrenciesReadModel;
use Modules\Currency\Domain\Repository\CurrencyQueryRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for getting all available currencies with caching.
 * Updated to use CQRS repository pattern with cache-first strategy.
 */
final readonly class GetAvailableCurrenciesQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CurrencyQueryRepositoryInterface $currencyRepository
    ) {}

    public function handle(QueryInterface $query): AvailableCurrenciesReadModel
    {
        if (! $query instanceof GetAvailableCurrenciesQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // Get currencies from cache-first repository
        $currencies = $this->currencyRepository->getActiveCurrencies();

        // Transform to ReadModel format
        $currenciesData = $currencies->keyBy('code')->map(fn ($currency): array => [
            'id' => $currency->id,
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
            'flag' => $currency->flag,
            'is_default' => $currency->is_default,
            'is_active' => $currency->is_active,
            'exchange_rate' => $currency->exchange_rate,
            'sort_order' => $currency->sort_order,
        ])->toArray();

        return new AvailableCurrenciesReadModel(
            id: 'global',
            data: [
                'currencies' => $currenciesData,
            ]
        );
    }
}
