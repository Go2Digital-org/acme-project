<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Application\Event\CampaignDeletedEvent;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class DeleteCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): bool
    {
        if (! $command instanceof DeleteCampaignCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): bool {
            $campaign = $this->repository->findById($command->campaignId);

            if (! $campaign instanceof Campaign) {
                throw CampaignException::notFound($command->campaignId);
            }

            // Validate employee permissions
            if ($campaign->user_id !== $command->userId) {
                throw CampaignException::unauthorizedAccess($campaign);
            }

            // Soft delete the campaign (moves to trash)
            $deleted = $this->repository->delete($command->campaignId);

            if ($deleted) {
                // Dispatch domain event for soft deletion
                event(new CampaignDeletedEvent(
                    campaignId: $campaign->id,
                    userId: $command->userId,
                    organizationId: $campaign->organization_id,
                    title: $campaign->getTitle(),
                ));
            }

            return $deleted;
        });
    }
}
