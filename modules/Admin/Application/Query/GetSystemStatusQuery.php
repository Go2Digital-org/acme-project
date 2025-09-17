<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetSystemStatusQuery implements QueryInterface
{
    public function __construct(
        public bool $includePerformanceMetrics = true,
        public bool $includeHealthChecks = true,
        public bool $includeQueueStatus = true,
        public bool $includeCacheStatus = true,
        public bool $includeStorageStatus = true
    ) {}
}
