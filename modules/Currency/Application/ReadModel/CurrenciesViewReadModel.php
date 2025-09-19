<?php

declare(strict_types=1);

namespace Modules\Currency\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * ReadModel for currency data optimized for frontend views.
 * Provides preprocessed data to avoid PHP logic in Blade templates.
 */
final class CurrenciesViewReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 3600; // 1 hour for view-optimized data

    /**
     * Get all currencies formatted for dropdown display.
     *
     * @return array<string, mixed>
     */
    public function getCurrenciesForDropdown(): array
    {
        return $this->get('currencies_dropdown', []);
    }

    /**
     * Get current user currency data.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getCurrentCurrency(): ?array
    {
        return $this->get('current_currency');
    }

    /**
     * Get default currency data.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getDefaultCurrency(): ?array
    {
        return $this->get('default_currency');
    }

    /**
     * Check if a currency code is available.
     */
    public function hasCurrency(string $code): bool
    {
        $currencies = $this->getCurrenciesForDropdown();

        return collect($currencies)->contains('code', strtoupper($code));
    }

    /**
     * Get currency data by code.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getCurrencyByCode(string $code): ?array
    {
        $currencies = $this->getCurrenciesForDropdown();

        return collect($currencies)->firstWhere('code', strtoupper($code));
    }

    /**
     * Get total count of available currencies.
     */
    public function getTotalCount(): int
    {
        return count($this->getCurrenciesForDropdown());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'currencies' => $this->getCurrenciesForDropdown(),
            'current_currency' => $this->getCurrentCurrency(),
            'default_currency' => $this->getDefaultCurrency(),
            'total_count' => $this->getTotalCount(),
            'cache_version' => $this->getVersion(),
            'cached_at' => now()->toISOString(),
        ];
    }
}
