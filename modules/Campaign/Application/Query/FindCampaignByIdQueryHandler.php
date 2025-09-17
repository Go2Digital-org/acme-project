<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use InvalidArgumentException;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class FindCampaignByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): Campaign
    {
        if (! $query instanceof FindCampaignByIdQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $campaign = $this->repository->findById($query->campaignId);

        if (! $campaign instanceof Campaign) {
            throw CampaignException::notFound($query->campaignId);
        }

        return $campaign;
    }
}
