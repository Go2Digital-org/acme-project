<?php

declare(strict_types=1);

namespace Modules\Dashboard\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class AnalyticsDashboardReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 900; // 15 minutes for analytics

    /**
     * @return array<string, mixed>
     */
    public function getCampaignStats(): array
    {
        return $this->get('campaign_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganizationStats(): array
    {
        return $this->get('organization_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentAnalytics(): array
    {
        return $this->get('payment_analytics', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRealtimeStats(): array
    {
        return $this->get('realtime_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRevenueSummary(): array
    {
        return $this->get('revenue_summary', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSuccessRates(): array
    {
        return $this->get('success_rates', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTimeBasedAnalytics(): array
    {
        return $this->get('time_based_analytics', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getDonationsStats(): array
    {
        return $this->get('donations_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementStats(): array
    {
        return $this->get('engagement_stats', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'organization_id' => $this->getId(),
            'campaign_stats' => $this->getCampaignStats(),
            'organization_stats' => $this->getOrganizationStats(),
            'payment_analytics' => $this->getPaymentAnalytics(),
            'realtime_stats' => $this->getRealtimeStats(),
            'revenue_summary' => $this->getRevenueSummary(),
            'success_rates' => $this->getSuccessRates(),
            'time_based_analytics' => $this->getTimeBasedAnalytics(),
            'donations_stats' => $this->getDonationsStats(),
            'engagement_stats' => $this->getEngagementStats(),
            'version' => $this->getVersion(),
        ];
    }
}
