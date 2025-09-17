<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Query;

use InvalidArgumentException;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetCampaignStatsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof GetCampaignStatsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $campaign = $this->repository->findById($query->campaignId);

        if (! $campaign instanceof Campaign) {
            throw CampaignException::notFound($query->campaignId);
        }

        return [
            'id' => $campaign->id,
            'title' => $campaign->title,
            'goal_amount' => $campaign->goal_amount,
            'current_amount' => $campaign->current_amount,
            'remaining_amount' => $campaign->getRemainingAmount(),
            'progress_percentage' => $campaign->getProgressPercentage(),
            'days_remaining' => $campaign->getDaysRemaining(),
            'has_reached_goal' => $campaign->hasReachedGoal(),
            'is_active' => $campaign->isActive(),
            'status' => $campaign->status,
            'start_date' => $campaign->start_date,
            'end_date' => $campaign->end_date,
            'created_at' => $campaign->created_at,
            'completed_at' => $campaign->completed_at,
        ];
    }
}
