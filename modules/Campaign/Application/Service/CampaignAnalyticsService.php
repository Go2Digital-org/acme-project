<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Service;

use Illuminate\Support\Facades\DB;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

final readonly class CampaignAnalyticsService
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Get campaign analytics overview data.
     *
     * @return array<string, mixed>
     */
    public function getCampaignOverview(): array
    {
        $totalCampaigns = $this->campaignRepository->count();
        $activeCampaigns = $this->campaignRepository->countByStatus(CampaignStatus::ACTIVE);
        $completedCampaigns = $this->campaignRepository->countByStatus(CampaignStatus::COMPLETED);
        $draftCampaigns = $this->campaignRepository->countByStatus(CampaignStatus::DRAFT);

        return [
            'total_campaigns' => $totalCampaigns,
            'active_campaigns' => $activeCampaigns,
            'completed_campaigns' => $completedCampaigns,
            'draft_campaigns' => $draftCampaigns,
            'completion_rate' => $totalCampaigns > 0 ? round(($completedCampaigns / $totalCampaigns) * 100, 2) : 0.0,
        ];
    }

    /**
     * Get campaign performance metrics.
     *
     * @return array<string, mixed>
     */
    public function getCampaignPerformanceMetrics(): array
    {
        $totalGoalAmount = $this->getTotalGoalAmount();
        $totalRaisedAmount = $this->getTotalRaisedAmount();
        $averageProgress = $this->getAverageProgress();
        $topPerformingCampaigns = $this->getTopPerformingCampaigns();

        return [
            'total_goal_amount' => $totalGoalAmount,
            'total_raised_amount' => $totalRaisedAmount,
            'overall_progress_percentage' => $totalGoalAmount > 0 ? round(($totalRaisedAmount / $totalGoalAmount) * 100, 2) : 0.0,
            'average_campaign_progress' => $averageProgress,
            'top_performing_campaigns' => $topPerformingCampaigns,
        ];
    }

    /**
     * Get campaign analytics by organization.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignAnalyticsByOrganization(): array
    {
        $results = DB::table('campaigns')
            ->join('organizations', 'campaigns.organization_id', '=', 'organizations.id')
            ->select([
                'organizations.id',
                'organizations.name',
                DB::raw('COUNT(campaigns.id) as total_campaigns'),
                DB::raw('SUM(campaigns.goal_amount) as total_goal_amount'),
                DB::raw('SUM(campaigns.current_amount) as total_raised_amount'),
                DB::raw('AVG(campaigns.current_amount / campaigns.goal_amount * 100) as average_progress'),
            ])
            ->groupBy(['organizations.id', 'organizations.name'])
            ->orderBy('total_raised_amount', 'desc')
            ->get();

        return $results->map(fn ($result): array => [
            'organization_id' => (int) $result->id,
            'organization_name' => (string) $result->name,
            'total_campaigns' => (int) $result->total_campaigns,
            'total_goal_amount' => (float) $result->total_goal_amount,
            'total_raised_amount' => (float) $result->total_raised_amount,
            'average_progress' => round((float) $result->average_progress, 2),
            'success_rate' => $result->total_goal_amount > 0
                ? round(((float) $result->total_raised_amount / (float) $result->total_goal_amount) * 100, 2)
                : 0.0,
        ])->toArray();
    }

    /**
     * Get campaign timeline analytics.
     *
     * @return array<string, mixed>
     */
    public function getCampaignTimelineAnalytics(): array
    {
        $monthlyData = $this->getMonthlyCreationData();
        $averageDuration = $this->getAverageCampaignDuration();
        $seasonalTrends = $this->getSeasonalTrends();

        return [
            'monthly_creation_data' => $monthlyData,
            'average_duration_days' => $averageDuration,
            'seasonal_trends' => $seasonalTrends,
        ];
    }

    /**
     * Get donation analytics for campaigns.
     *
     * @return array<string, mixed>
     */
    public function getDonationAnalytics(): array
    {
        $totalDonations = $this->getTotalDonationCount();
        $averageDonationAmount = $this->getAverageDonationAmount();
        $donationDistribution = $this->getDonationDistribution();

        return [
            'total_donations' => $totalDonations,
            'average_donation_amount' => $averageDonationAmount,
            'donation_distribution' => $donationDistribution,
        ];
    }

    /**
     * Get total goal amount across all campaigns.
     */
    private function getTotalGoalAmount(): float
    {
        return (float) DB::table('campaigns')->sum('goal_amount');
    }

    /**
     * Get total raised amount across all campaigns.
     */
    private function getTotalRaisedAmount(): float
    {
        return (float) DB::table('campaigns')->sum('current_amount');
    }

    /**
     * Get average progress percentage across all campaigns.
     */
    private function getAverageProgress(): float
    {
        $result = DB::table('campaigns')
            ->selectRaw('AVG(current_amount / goal_amount * 100) as average_progress')
            ->where('goal_amount', '>', 0)
            ->first();

        return round((float) ($result->average_progress ?? 0), 2);
    }

    /**
     * Get top performing campaigns by progress percentage.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getTopPerformingCampaigns(int $limit = 10): array
    {
        $campaigns = DB::table('campaigns')
            ->select([
                'id',
                'title',
                'goal_amount',
                'current_amount',
                DB::raw('(current_amount / goal_amount * 100) as progress_percentage'),
            ])
            ->where('goal_amount', '>', 0)
            ->orderBy('progress_percentage', 'desc')
            ->limit($limit)
            ->get();

        return $campaigns->map(fn ($campaign): array => [
            'id' => (int) $campaign->id,
            'title' => (string) $campaign->title,
            'goal_amount' => (float) $campaign->goal_amount,
            'current_amount' => (float) $campaign->current_amount,
            'progress_percentage' => round((float) $campaign->progress_percentage, 2),
        ])->toArray();
    }

    /**
     * Get monthly campaign creation data.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getMonthlyCreationData(): array
    {
        $results = DB::table('campaigns')
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy(['year', 'month'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return $results->map(fn ($result): array => [
            'year' => (int) $result->year,
            'month' => (int) $result->month,
            'count' => (int) $result->count,
            'month_name' => date('F', mktime(0, 0, 0, (int) $result->month, 1) ?: 0),
        ])->toArray();
    }

    /**
     * Get average campaign duration in days.
     */
    private function getAverageCampaignDuration(): float
    {
        $result = DB::table('campaigns')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as average_duration')
            ->first();

        return round((float) ($result->average_duration ?? 0), 1);
    }

    /**
     * Get seasonal campaign trends.
     *
     * @return array<string, mixed>
     */
    private function getSeasonalTrends(): array
    {
        $results = DB::table('campaigns')
            ->selectRaw('
                CASE 
                    WHEN MONTH(created_at) IN (12,1,2) THEN "Winter"
                    WHEN MONTH(created_at) IN (3,4,5) THEN "Spring"
                    WHEN MONTH(created_at) IN (6,7,8) THEN "Summer"
                    ELSE "Fall"
                END as season,
                COUNT(*) as count,
                AVG(current_amount / goal_amount * 100) as average_success_rate
            ')
            ->where('goal_amount', '>', 0)
            ->groupBy('season')
            ->get();

        $seasonalData = [];
        foreach ($results as $result) {
            $seasonalData[(string) $result->season] = [
                'count' => (int) $result->count,
                'average_success_rate' => round((float) $result->average_success_rate, 2),
            ];
        }

        return $seasonalData;
    }

    /**
     * Get total donation count across all campaigns.
     */
    private function getTotalDonationCount(): int
    {
        return (int) DB::table('donations')->count();
    }

    /**
     * Get average donation amount.
     */
    private function getAverageDonationAmount(): float
    {
        $result = DB::table('donations')
            ->selectRaw('AVG(amount) as average_amount')
            ->first();

        return round((float) ($result->average_amount ?? 0), 2);
    }

    /**
     * Get donation distribution by amount ranges.
     *
     * @return array<string, int>
     */
    private function getDonationDistribution(): array
    {
        $results = DB::table('donations')
            ->selectRaw('
                CASE 
                    WHEN amount < 25 THEN "Under €25"
                    WHEN amount < 50 THEN "€25-€49"
                    WHEN amount < 100 THEN "€50-€99"
                    WHEN amount < 250 THEN "€100-€249"
                    WHEN amount < 500 THEN "€250-€499"
                    ELSE "€500+"
                END as range_label,
                COUNT(*) as count
            ')
            ->groupBy('range_label')
            ->get();

        $distribution = [];
        foreach ($results as $result) {
            $distribution[(string) $result->range_label] = (int) $result->count;
        }

        return $distribution;
    }
}
