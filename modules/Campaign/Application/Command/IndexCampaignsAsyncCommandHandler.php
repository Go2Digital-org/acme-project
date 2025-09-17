<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\Service\CampaignIndexerInterface;

final readonly class IndexCampaignsAsyncCommandHandler
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private CampaignIndexerInterface $indexer
    ) {}

    public function handle(IndexCampaignsAsyncCommand $command): void
    {
        $campaigns = $this->campaignRepository->findForIndexing(
            offset: $command->offset,
            limit: $command->limit ?? $command->chunkSize
        );

        foreach ($campaigns as $campaign) {
            $this->indexer->index($campaign);
        }
    }
}
