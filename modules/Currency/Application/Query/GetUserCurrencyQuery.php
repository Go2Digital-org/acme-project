<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

class GetUserCurrencyQuery implements QueryInterface
{
    public function __construct(
        public readonly int $userId,
    ) {}
}
