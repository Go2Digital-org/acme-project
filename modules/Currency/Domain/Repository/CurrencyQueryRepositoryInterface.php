<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Repository;

use Illuminate\Support\Collection;
use Modules\Currency\Domain\Model\Currency;

interface CurrencyQueryRepositoryInterface
{
    /**
     * Get all active currencies with caching and N+1 prevention.
     *
     * @return Collection<int, Currency>
     */
    public function getActiveCurrencies(): Collection;

    /**
     * Find currency by code with caching.
     */
    public function findByCode(string $code): ?Currency;

    /**
     * Get default currency with caching.
     */
    public function getDefaultCurrency(): ?Currency;

    /**
     * Get currencies for view display (optimized for frontend).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getCurrenciesForView(): Collection;

    /**
     * Clear all currency caches.
     */
    public function clearCache(): void;

    /**
     * Warm currency caches.
     */
    public function warmCache(): void;
}
