<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Query;

class GetHomepageStatsQuery implements QueryInterface
{
    public function __construct(
        public bool $includeCache = true
    ) {}
}
