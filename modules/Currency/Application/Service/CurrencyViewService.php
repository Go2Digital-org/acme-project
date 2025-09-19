<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Service;

use Exception;
use Illuminate\Support\Collection;
use Modules\Currency\Application\Query\GetCurrenciesForViewQuery;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Service\CurrencyCacheInterface;
use Modules\Shared\Application\Query\QueryBusInterface;
use stdClass;

/**
 * Currency view service using CQRS pattern.
 *
 * @deprecated Use GetCurrenciesForViewQuery directly via QueryBus for new code.
 * This service is kept for backward compatibility.
 */
class CurrencyViewService
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CurrencyCacheInterface $cache
    ) {}

    /**
     * Get active currencies for view display.
     *
     * @deprecated Use GetCurrenciesForViewQuery directly via QueryBus.
     */
    /**
     * @return Collection<int, Currency>
     */
    public function getActiveCurrencies(): Collection
    {
        try {
            // Use CQRS query for consistent caching behavior
            $readModel = $this->queryBus->ask(new GetCurrenciesForViewQuery);

            // Transform to Collection of stdClass for backward compatibility
            $currencyData = $readModel->getCurrenciesForDropdown();
            /** @var array<string, mixed> $typedData */
            $typedData = $currencyData;
            /** @var Collection<int, Currency> $currencies */
            $currencies = collect($typedData)
                ->map(function (array $currency): Currency {
                    $currencyModel = new Currency;
                    $currencyModel->id = $currency['id'] ?? null;
                    $currencyModel->code = $currency['code'];
                    $currencyModel->name = $currency['name'];
                    $currencyModel->symbol = $currency['symbol'];
                    $currencyModel->flag = $currency['flag'];
                    $currencyModel->is_default = $currency['is_default'] ?? false;
                    $currencyModel->is_active = $currency['is_active'] ?? true;
                    $currencyModel->sort_order = $currency['sort_order'] ?? 0;

                    return $currencyModel;
                });

            // Store in request cache
            $this->cache->set($currencies);

            return $currencies;
        } catch (Exception $e) {
            // Log error and return empty collection
            logger()->error('CurrencyViewService failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            /** @var Collection<int, Currency> $result */
            $result = collect([]);
            $this->cache->set($result);

            return $result;
        }
    }
}
