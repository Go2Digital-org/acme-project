<?php

declare(strict_types=1);

namespace Modules\Currency\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class AvailableCurrenciesReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 86400; // 24 hours for currencies

    /**
     * @return array<string, mixed>
     */
    public function getCurrencies(): array
    {
        return $this->get('currencies', []);
    }

    /**
     * @return array<int, string>
     */
    public function getCurrencyCodes(): array
    {
        return array_keys($this->getCurrencies());
    }

    public function hasCurrency(string $code): bool
    {
        return array_key_exists($code, $this->getCurrencies());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCurrency(string $code): ?array
    {
        return $this->getCurrencies()[$code] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDefaultCurrency(): ?array
    {
        $currencies = $this->getCurrencies();
        foreach ($currencies as $currency) {
            if ($currency['is_default'] ?? false) {
                return $currency;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currencies' => $this->getCurrencies(),
            'currency_codes' => $this->getCurrencyCodes(),
            'default_currency' => $this->getDefaultCurrency(),
            'total_currencies' => count($this->getCurrencies()),
            'version' => $this->getVersion(),
        ];
    }
}
