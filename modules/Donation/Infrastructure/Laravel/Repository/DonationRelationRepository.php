<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\Repository\DonationRelationRepositoryInterface;

class DonationRelationRepository implements DonationRelationRepositoryInterface
{
    public function __construct(
        private readonly Donation $model,
    ) {}

    /**
     * @return Collection<int, Model>
     */
    public function getDonationsForCampaign(int $campaignId): Collection
    {
        return $this->model->query() // @phpstan-ignore-line
            ->where('campaign_id', $campaignId)
            ->get();
    }

    public function getDonationCountForCampaign(int $campaignId): int
    {
        return $this->model->query()
            ->where('campaign_id', $campaignId)
            ->count();
    }

    public function getTotalDonationAmountForCampaign(int $campaignId): float
    {
        return (float) $this->model->query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'completed')
            ->sum('amount');
    }
}
