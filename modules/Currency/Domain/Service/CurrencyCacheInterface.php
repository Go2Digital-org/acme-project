<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Service;

use Illuminate\Support\Collection;
use stdClass;

interface CurrencyCacheInterface
{
    /**
     * Get cached currencies or null if not cached.
     *
     * @return Collection<int, stdClass>|null
     */
    public function get(): ?Collection;

    /**
     * Store currencies in cache.
     *
     * @param  Collection<int, stdClass>  $currencies
     */
    public function set(Collection $currencies): void;

    /**
     * Check if currencies are cached.
     */
    public function has(): bool;

    /**
     * Clear the cache.
     */
    public function clear(): void;
}
