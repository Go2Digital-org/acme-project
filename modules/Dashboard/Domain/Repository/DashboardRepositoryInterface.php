<?php

declare(strict_types=1);

namespace Modules\Dashboard\Domain\Repository;

use Modules\Dashboard\Domain\ValueObject\DashboardStatistics;
use Modules\Dashboard\Domain\ValueObject\ImpactMetrics;

interface DashboardRepositoryInterface
{
    /**
     * Get user's dashboard statistics.
     */
    public function getUserStatistics(int $userId): DashboardStatistics;

    /**
     * Get user's recent activity feed.
     *
     * @return array<int, mixed>
     */
    public function getUserActivityFeed(int $userId, int $limit = 10): array;

    /**
     * Get user's impact metrics.
     */
    public function getUserImpactMetrics(int $userId): ImpactMetrics;

    /**
     * Get user's organization ranking.
     */
    public function getUserOrganizationRanking(int $userId): int;

    /**
     * Get top donators leaderboard.
     *
     * @return array<int, mixed>
     */
    public function getTopDonatorsLeaderboard(int $organizationId, int $limit = 5): array;

    /**
     * Get optimized campaign statistics.
     *
     * @return array<string, mixed>
     */
    public function getOptimizedCampaignStats(): array;

    /**
     * Get organization statistics.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationStats(): array;

    /**
     * Get payment analytics data.
     *
     * @return array<string, mixed>
     */
    public function getPaymentAnalytics(): array;

    /**
     * Get real-time statistics.
     *
     * @return array<string, mixed>
     */
    public function getRealtimeStats(): array;

    /**
     * Get revenue summary data.
     *
     * @return array<string, mixed>
     */
    public function getRevenueSummary(): array;

    /**
     * Get success rate metrics.
     *
     * @return array<string, mixed>
     */
    public function getSuccessRates(): array;

    /**
     * Get time-based analytics data.
     *
     * @return array<string, mixed>
     */
    public function getTimeBasedAnalytics(): array;

    /**
     * Get total donations statistics.
     *
     * @return array<string, mixed>
     */
    public function getTotalDonationsStats(): array;

    /**
     * Get user engagement statistics.
     *
     * @return array<string, mixed>
     */
    public function getUserEngagementStats(): array;
}
