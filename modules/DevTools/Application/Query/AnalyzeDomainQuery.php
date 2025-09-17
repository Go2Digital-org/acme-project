<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\Query;

final class AnalyzeDomainQuery
{
    public function __construct(
        public string $module,
        public bool $includeRelations = true,
        public bool $includeMetrics = true
    ) {}
}
