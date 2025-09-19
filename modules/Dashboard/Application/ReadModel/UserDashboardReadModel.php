<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\ReadModel;

use Modules\Dashboard\Domain\ValueObject\DashboardStatistics;
use Modules\Dashboard\Domain\ValueObject\ImpactMetrics;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class UserDashboardReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 300; // 5 minutes for user dashboard

    public function getStatistics(): DashboardStatistics
    {
        return $this->get('statistics');
    }

    /**
     * @return array<string, mixed>
     */
    public function getActivityFeed(): array
    {
        return $this->get('activity_feed', []);
    }

    public function getImpactMetrics(): ImpactMetrics
    {
        return $this->get('impact_metrics');
    }

    public function hasRecentActivity(): bool
    {
        return count($this->getActivityFeed()) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->getId(),
            'statistics' => $this->getStatistics(),
            'activity_feed' => $this->getActivityFeed(),
            'impact_metrics' => $this->getImpactMetrics(),
            'has_recent_activity' => $this->hasRecentActivity(),
            'version' => $this->getVersion(),
        ];
    }
}
