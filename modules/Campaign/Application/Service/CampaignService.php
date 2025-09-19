<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Campaign\Application\Command\CreateCampaignCommand;
use Modules\Campaign\Application\Command\CreateCampaignCommandHandler;
use Modules\Campaign\Application\Command\DeleteCampaignCommand;
use Modules\Campaign\Application\Command\DeleteCampaignCommandHandler;
use Modules\Campaign\Application\Command\UpdateCampaignCommand;
use Modules\Campaign\Application\Command\UpdateCampaignCommandHandler;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

final readonly class CampaignService
{
    public function __construct(
        private CreateCampaignCommandHandler $createHandler,
        private UpdateCampaignCommandHandler $updateHandler,
        private DeleteCampaignCommandHandler $deleteHandler,
        private CampaignRepositoryInterface $repository,
    ) {}

    /**
     * Store a new campaign.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function storeCampaign(array $data): Campaign
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        $command = new CreateCampaignCommand(
            title: $data['title'],
            description: $data['description'],
            goalAmount: (float) $data['goal_amount'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            organizationId: (int) ($data['organization_id'] ?? $user->organization_id),
            userId: $user->id,
            locale: $data['locale'] ?? app()->getLocale(),
            // Pass additional fields to the command
            category: $data['category'] ?? null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            metadata: [
                'creator_note' => $data['creator_note'] ?? null,
                'organization_name' => $data['organization_name'] ?? null,
                'organization_website' => $data['organization_website'] ?? null,
                'allow_anonymous_donations' => $data['allow_anonymous_donations'] ?? true,
                'show_donation_comments' => $data['show_donation_comments'] ?? true,
                'email_notifications' => $data['email_notifications'] ?? true,
            ],
            status: ($data['action'] ?? 'draft') === 'submit' ? 'pending_approval' : 'draft',
        );

        return $this->createHandler->handle($command);
    }

    /**
     * Update an existing campaign.
     */
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCampaign(int $campaignId, array $data): Campaign
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        // Get existing campaign to preserve organization_id
        $existingCampaign = $this->repository->findById($campaignId);

        if (! $existingCampaign instanceof Campaign) {
            throw new InvalidArgumentException('Campaign not found');
        }

        $command = new UpdateCampaignCommand(
            campaignId: $campaignId,
            title: $data['title'],
            description: $data['description'],
            goalAmount: (float) $data['goal_amount'],
            startDate: $data['start_date'],
            endDate: $data['end_date'],
            organizationId: $existingCampaign->organization_id,
            userId: $user->id,
            locale: $data['locale'] ?? app()->getLocale(),
        );

        return $this->updateHandler->handle($command);
    }

    /**
     * Delete a campaign.
     */
    public function deleteCampaign(int $campaignId): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        $command = new DeleteCampaignCommand(
            campaignId: $campaignId,
            userId: $user->id,
        );

        return $this->deleteHandler->handle($command);
    }

    /**
     * Restore a soft-deleted campaign.
     */
    public function restoreCampaign(int $campaignId): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        return $this->repository->restore($campaignId);
    }

    /**
     * Check if user can manage a campaign.
     */
    public function canManageCampaign(int $campaignId, ?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        // Try to find campaign including soft-deleted ones
        $campaign = $this->repository->findByIdWithTrashed($campaignId);

        if (! $campaign instanceof Campaign) {
            return false;
        }

        // User can manage if they are the creator or belong to same organization
        return $campaign->user_id === $user->id
            || $campaign->organization_id === $user->organization_id;
    }
}
