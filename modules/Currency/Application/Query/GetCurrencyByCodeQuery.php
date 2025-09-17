<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query for finding a specific currency by code with caching.
 */
final readonly class GetCurrencyByCodeQuery implements QueryInterface
{
    public function __construct(
        public string $code,
        public bool $forceRefresh = false
    ) {}
}
