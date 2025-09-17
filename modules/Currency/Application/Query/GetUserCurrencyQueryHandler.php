<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use InvalidArgumentException;
use Modules\Currency\Application\ReadModel\UserCurrencyReadModel;
use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

class GetUserCurrencyQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly CurrencyPreferenceService $currencyService,
    ) {}

    public function handle(QueryInterface $query): UserCurrencyReadModel
    {
        if (! $query instanceof GetUserCurrencyQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $currencyData = $this->currencyService->getUserCurrency($query->userId);

        // Convert Currency object to array data for the read model
        $data = $currencyData instanceof Currency ? [
            'currency_code' => $currencyData->getCode(),
            'currency_symbol' => $currencyData->getSymbol(),
            'currency_name' => $currencyData->getName(),
            'exchange_rates' => [],
            'is_default' => false,
        ] : [];

        return new UserCurrencyReadModel(
            id: $query->userId,
            data: $data
        );
    }
}
