<?php

declare(strict_types=1);

namespace Modules\CacheWarming\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetCacheStatusQuery implements QueryInterface
{
    public function __construct(
        public ?string $cacheType = null,
        public bool $includeProgress = true,
        public bool $includeRecommendations = false,
    ) {}
}
