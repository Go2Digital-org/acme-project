<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Campaign\Application\Event\CampaignPublishedEvent;
use Modules\Campaign\Domain\Exception\CampaignNotFoundException;
use Modules\Campaign\Domain\Exception\CampaignStatusException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;

final readonly class PublishCampaignCommandHandler
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository
    ) {}

    /**
     * @throws CampaignNotFoundException
     * @throws CampaignStatusException
     * @throws Exception
     */
    public function handle(PublishCampaignCommand $command): void
    {
        $campaign = $this->campaignRepository->findById($command->campaignId);

        if (! $campaign instanceof Campaign) {
            throw new CampaignNotFoundException(
                "Campaign with ID {$command->campaignId} not found"
            );
        }

        // Validate campaign can be published
        if ($campaign->status !== 'approved') {
            throw new CampaignStatusException(
                'Campaign must be approved before it can be published'
            );
        }

        if (! $campaign->start_date || ! $campaign->end_date) {
            throw new CampaignStatusException(
                'Campaign must have start and end dates set before publishing'
            );
        }

        DB::transaction(function () use ($campaign, $command) {
            // Update campaign status to active
            $this->campaignRepository->updateById($campaign->id, [
                'status' => 'active',
                'published_at' => now(),
                'published_by' => $command->publishedBy,
                'updated_at' => now(),
            ]);

            // Dispatch domain event
            // At this point dates are guaranteed to be non-null due to validation above
            $startDate = $campaign->start_date;
            $endDate = $campaign->end_date;

            if ($startDate === null || $endDate === null) {
                throw new CampaignStatusException(
                    'Campaign must have start and end dates set before publishing'
                );
            }

            Event::dispatch(new CampaignPublishedEvent(
                publishedBy: $command->publishedBy,
                campaignId: $campaign->id,
                organizationId: $campaign->organization_id,
                title: $campaign->getTitle(),
                targetAmount: (float) $campaign->goal_amount,
                startDate: $startDate->toISOString(),
                endDate: $endDate->toISOString()
            ));
        });
    }
}
