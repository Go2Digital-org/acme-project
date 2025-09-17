<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for getting optimized currency data for frontend views.
 * Implements cache-first strategy with fallback chain.
 */
final readonly class GetCurrenciesForViewQuery implements QueryInterface
{
    public function __construct(
        public bool $forceRefresh = false
    ) {}
}
