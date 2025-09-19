<?php

declare(strict_types=1);

namespace Modules\Currency\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class UserCurrencyReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 3600; // 1 hour for user currency

    public function getCurrencyCode(): string
    {
        return $this->get('currency_code', 'USD');
    }

    public function getCurrencySymbol(): string
    {
        return $this->get('currency_symbol', '$');
    }

    public function getCurrencyName(): string
    {
        return $this->get('currency_name', 'US Dollar');
    }

    /**
     * @return array<string, float>
     */
    public function getExchangeRates(): array
    {
        return $this->get('exchange_rates', []);
    }

    public function getExchangeRate(string $toCurrency): ?float
    {
        return $this->getExchangeRates()[$toCurrency] ?? null;
    }

    public function isDefaultCurrency(): bool
    {
        return $this->get('is_default', false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->getId(),
            'currency_code' => $this->getCurrencyCode(),
            'currency_symbol' => $this->getCurrencySymbol(),
            'currency_name' => $this->getCurrencyName(),
            'exchange_rates' => $this->getExchangeRates(),
            'is_default' => $this->isDefaultCurrency(),
            'version' => $this->getVersion(),
        ];
    }
}
