<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Application\Event\CampaignCompletedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class CompleteCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof CompleteCampaignCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Campaign {
            $campaign = $this->repository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Validate employee permissions
            if ($campaign->user_id !== $command->userId) {
                throw CampaignException::unauthorizedAccess($campaign);
            }

            // Validate campaign can be completed
            if (! $campaign->isActive()) {
                throw CampaignException::cannotComplete($campaign);
            }

            // Mark as completed using domain logic
            $campaign->markAsCompleted();
            $campaign->save();

            // Dispatch domain event
            event(new CampaignCompletedEvent(
                campaignId: $campaign->id,
                userId: $command->userId,
                organizationId: $campaign->organization_id,
                totalRaised: (float) $campaign->current_amount,
                goalAmount: (float) $campaign->goal_amount,
            ));

            return $campaign;
        });
    }
}
