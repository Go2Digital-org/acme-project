<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\Currency\Application\Query\GetAvailableCurrenciesQuery;
use Modules\Currency\Infrastructure\ApiPlatform\Resource\CurrencyResource;
use Modules\Shared\Application\Query\QueryBusInterface;

/**
 * @implements ProviderInterface<CurrencyResource>
 */
final readonly class CurrencyCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $readModel = $this->queryBus->ask(new GetAvailableCurrenciesQuery);

        if (! $readModel) {
            return [
                'data' => [],
                'default' => 'USD',
                'current' => 'USD',
            ];
        }

        $currencies = $readModel->getCurrencies();

        if (empty($currencies)) {
            return [
                'data' => [],
                'default' => 'USD',
                'current' => 'USD',
            ];
        }

        // Transform currencies to array format
        $currencyList = array_values(array_map(fn (array $currencyData): array => [
            'code' => $currencyData['code'],
            'name' => $currencyData['name'],
            'symbol' => $currencyData['symbol'],
            'decimal_places' => $currencyData['decimal_places'] ?? 2,
            'decimal_separator' => $currencyData['decimal_separator'] ?? '.',
            'thousands_separator' => $currencyData['thousands_separator'] ?? ',',
            'symbol_position' => $currencyData['symbol_position'] ?? 'before',
        ], $currencies));

        // Find default currency
        $defaultCurrency = 'USD';
        foreach ($currencies as $currency) {
            if ($currency['is_default'] ?? false) {
                $defaultCurrency = $currency['code'];
                break;
            }
        }

        // Get current user currency (defaulting to default currency for now)
        $currentCurrency = $defaultCurrency; // TODO: Get from user preferences

        return [
            'data' => $currencyList,
            'default' => $defaultCurrency,
            'current' => $currentCurrency,
        ];
    }
}
