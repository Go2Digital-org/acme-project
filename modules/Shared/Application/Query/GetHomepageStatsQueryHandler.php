<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Query;

use InvalidArgumentException;
use Modules\Shared\Application\Service\HomepageStatsService;

class GetHomepageStatsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly HomepageStatsService $homepageStatsService
    ) {}

    /** @return array<string, mixed> */
    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof GetHomepageStatsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        if (! $query->includeCache) {
            $this->homepageStatsService->clearCache();
        }

        return $this->homepageStatsService->getHomepageStats();
    }
}
