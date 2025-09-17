<?php

declare(strict_types=1);

namespace Modules\Admin\Application\ReadModel;

use DateTimeImmutable;

final readonly class AdminDashboardReadModel
{
    /**
     * @param  array<mixed>  $recentActivity
     * @param  array<mixed>  $quickActions
     * @param  array<mixed>  $systemAlerts
     */
    public function __construct(
        public SystemStatsReadModel $systemStats,
        public array $recentActivity,
        public array $quickActions,
        public array $systemAlerts,
        public string $dateRange,
        public DateTimeImmutable $lastUpdated
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'system_stats' => $this->systemStats->toArray(),
            'recent_activity' => $this->recentActivity,
            'quick_actions' => $this->quickActions,
            'system_alerts' => $this->systemAlerts,
            'date_range' => $this->dateRange,
            'last_updated' => $this->lastUpdated->format('Y-m-d H:i:s'),
        ];
    }
}

final readonly class SystemStatsReadModel
{
    /**
     * @param  array<string, mixed>  $performanceMetrics
     */
    public function __construct(
        public int $totalUsers,
        public int $activeCampaigns,
        public int $totalDonations,
        public float $totalDonationAmount,
        public int $pendingApprovals,
        public array $performanceMetrics
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_users' => $this->totalUsers,
            'active_campaigns' => $this->activeCampaigns,
            'total_donations' => $this->totalDonations,
            'total_donation_amount' => $this->totalDonationAmount,
            'pending_approvals' => $this->pendingApprovals,
            'performance_metrics' => $this->performanceMetrics,
        ];
    }
}
