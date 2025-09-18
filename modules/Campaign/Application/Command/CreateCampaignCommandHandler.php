<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Campaign\Application\Event\CampaignCreatedEvent;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class CreateCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        if (! $command instanceof CreateCampaignCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Campaign {
            // Set locale context if provided
            if ($command->locale) {
                App::setLocale($command->locale);
            }

            // Determine the status based on the command
            $status = $command->status === 'pending_approval' ? CampaignStatus::PENDING_APPROVAL : CampaignStatus::DRAFT;

            $campaignData = [
                'uuid' => Str::uuid()->toString(),
                'title' => $command->title,
                'description' => $command->description,
                'goal_amount' => $command->goalAmount,
                'current_amount' => 0,
                'start_date' => $command->startDate,
                'end_date' => $command->endDate,
                'status' => $status->value, // Use the enum value (string) not the enum object
                'organization_id' => $command->organizationId,
                'user_id' => $command->userId,
                'category' => $command->category,
                'category_id' => $command->categoryId,
                'metadata' => $command->metadata === [] ? null : $command->metadata,
            ];

            // Translation data is now handled directly in title/description fields by the form requests

            $campaign = $this->repository->create($campaignData);

            // Validate business rules
            $campaign->validateDateRange();
            $campaign->validateGoalAmount();

            // Dispatch domain event
            event(new CampaignCreatedEvent(
                campaignId: $campaign->id,
                userId: $command->userId,
                organizationId: $command->organizationId,
                title: $command->title[$command->locale ?? 'en'] ?? $command->title[array_key_first($command->title)] ?? '',
                goalAmount: $command->goalAmount,
            ));

            return $campaign;
        });
    }
}
