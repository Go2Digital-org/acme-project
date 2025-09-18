<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Application\Event\CampaignActivatedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class ActivateCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof ActivateCampaignCommand) {
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

            // Validate campaign can be activated
            if ($campaign->status !== CampaignStatus::DRAFT) {
                throw CampaignException::cannotActivate($campaign);
            }

            // Validate business rules before activation
            $campaign->validateDateRange();
            $campaign->validateGoalAmount();

            // Activate campaign
            $this->repository->updateById($command->campaignId, [
                'status' => CampaignStatus::ACTIVE,
            ]);

            // Refresh model with new status
            $activatedCampaign = $this->repository->findById($command->campaignId);

            if (! $activatedCampaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Dispatch domain event
            event(new CampaignActivatedEvent(
                campaignId: $campaign->id,
                userId: $command->userId,
                organizationId: $campaign->organization_id,
            ));

            return $activatedCampaign;
        });
    }
}
