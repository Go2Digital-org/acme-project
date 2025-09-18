<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Application\Event\CampaignUpdatedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class UpdateCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof UpdateCampaignCommand) {
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

            // Update campaign data
            $this->repository->updateById($command->campaignId, [
                'title' => $command->title,
                'description' => $command->description,
                'goal_amount' => $command->goalAmount,
                'start_date' => $command->startDate,
                'end_date' => $command->endDate,
            ]);

            // Refresh model with new data
            $updatedCampaign = $this->repository->findById($command->campaignId);

            if (! $updatedCampaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Validate business rules
            $updatedCampaign->validateDateRange();
            $updatedCampaign->validateGoalAmount();

            // Dispatch domain event
            event(new CampaignUpdatedEvent(
                campaignId: $campaign->id,
                userId: $command->userId,
                organizationId: $command->organizationId,
            ));

            return $updatedCampaign;
        });
    }
}
