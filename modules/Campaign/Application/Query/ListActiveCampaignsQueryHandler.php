<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class ListActiveCampaignsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
    ) {}

    /**
     * @return array<Campaign>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof ListActiveCampaignsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        return $this->repository->findActiveCampaigns();
    }
}
