<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use InvalidArgumentException;
use Modules\Campaign\Application\ReadModel\CampaignAnalyticsReadModel;
use Modules\Campaign\Infrastructure\Laravel\Repository\CampaignAnalyticsRepository;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for retrieving campaign analytics using read models.
 */
final readonly class GetCampaignAnalyticsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignAnalyticsRepository $repository,
    ) {}

    public function handle(QueryInterface $query): ?CampaignAnalyticsReadModel
    {
        if (! $query instanceof GetCampaignAnalyticsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        if ($query->forceRefresh) {
            $result = $this->repository->refresh($query->campaignId);

            return $result instanceof CampaignAnalyticsReadModel ? $result : null;
        }

        $result = $this->repository->find($query->campaignId, $query->filters);

        return $result instanceof CampaignAnalyticsReadModel ? $result : null;
    }
}
