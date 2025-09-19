<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListCampaignsByOrganizationQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof ListCampaignsByOrganizationQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        // For all campaigns by organization, we'll need to extend the repository interface
        $campaigns = $this->repository->findActiveByOrganization($query->organizationId);

        return [
            'campaigns' => array_map(fn (Campaign $campaign) => $campaign->toArray(), $campaigns),
            'total' => count($campaigns),
            'organization_id' => $query->organizationId,
        ];
    }
}
