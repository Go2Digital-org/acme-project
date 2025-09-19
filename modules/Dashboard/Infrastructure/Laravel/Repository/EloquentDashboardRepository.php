<?php

declare(strict_types=1);

namespace Modules\Dashboard\Infrastructure\Laravel\Repository;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Dashboard\Domain\Repository\DashboardRepositoryInterface;
use Modules\Dashboard\Domain\ValueObject\ActivityFeedItem;
use Modules\Dashboard\Domain\ValueObject\ActivityType;
use Modules\Dashboard\Domain\ValueObject\DashboardStatistics;
use Modules\Dashboard\Domain\ValueObject\ImpactMetrics;
use Modules\Dashboard\Domain\ValueObject\LeaderboardEntry;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\ValueObject\Money;
use Modules\User\Infrastructure\Laravel\Models\User;

class EloquentDashboardRepository implements DashboardRepositoryInterface
{
    public function getUserStatistics(int $userId): DashboardStatistics
    {
        // Get all statistics in a single optimized query
        $stats = DB::table('donations')
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->selectRaw("
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_donated,
                    COUNT(DISTINCT CASE WHEN status = 'completed' THEN campaign_id END) as campaigns_supported,
                    SUM(CASE WHEN status = 'completed' AND donated_at >= ? AND donated_at <= ? THEN amount ELSE 0 END) as last_month_total,
                    SUM(CASE WHEN status = 'completed' AND donated_at >= ? THEN amount ELSE 0 END) as this_month_total,
                    COUNT(CASE WHEN status = 'completed' AND donated_at >= ? THEN 1 END) as recent_donations_count
                ", [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                now()->startOfMonth(),
                now()->subMonths(3),
            ])
            ->first();

        $totalDonated = (float) ($stats->total_donated ?? 0);
        $campaignsSupported = (int) ($stats->campaigns_supported ?? 0);
        $lastMonthTotal = (float) ($stats->last_month_total ?? 0);
        $thisMonthTotal = (float) ($stats->this_month_total ?? 0);

        $monthlyIncrease = $thisMonthTotal - $lastMonthTotal;
        $monthlyGrowthPercentage = $lastMonthTotal > 0
            ? (($thisMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100
            : 0;

        // Calculate impact score using the stats we already have
        $impactScore = $this->calculateImpactScoreOptimized($totalDonated, $campaignsSupported, (int) ($stats->recent_donations_count ?? 0));

        // Get organization-wide ranking (cached separately)
        $teamRanking = $this->getUserOrganizationRanking($userId);
        $totalTeams = $this->getTotalActiveUsers(1); // Assuming organization ID 1

        return new DashboardStatistics(
            totalDonated: new Money($totalDonated, 'EUR'),
            campaignsSupported: $campaignsSupported,
            impactScore: $impactScore,
            teamRanking: $teamRanking,
            totalTeams: $totalTeams,
            monthlyIncrease: new Money(max(0, $monthlyIncrease), 'EUR'),
            monthlyGrowthPercentage: $monthlyGrowthPercentage,
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function getUserActivityFeed(int $userId, int $limit = 10): array
    {
        $activities = [];

        // Get recent donations - only show completed donations
        $donations = Donation::with('campaign')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->orderBy('donated_at', 'desc')
            ->limit($limit)
            ->get();

        foreach ($donations as $donation) {
            $campaignTitle = $donation->campaign ? $donation->campaign->getTitle() : 'Unknown Campaign';
            $campaignUrl = $donation->campaign ? route('campaigns.show', $donation->campaign->slug ?? $donation->campaign_id) : '#';

            $activities[] = new ActivityFeedItem(
                id: 'donation_' . $donation->id,
                type: ActivityType::DONATION,
                description: sprintf(
                    'You donated €%.2f to <a href="%s" class="text-primary hover:underline">%s</a>',
                    $donation->amount,
                    $campaignUrl,
                    $campaignTitle,
                ),
                occurredAt: Carbon::parse($donation->donated_at),
                amount: new Money($donation->amount, $donation->currency),
                relatedEntityType: 'campaign',
                relatedEntityId: $donation->campaign_id,
                relatedEntityTitle: $donation->campaign ? $donation->campaign->getTitle() : null,
            );
        }

        // Get campaigns created by user
        $campaigns = Campaign::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($campaigns as $campaign) {
            $campaignUrl = route('campaigns.show', $campaign->slug ?? $campaign->id);

            $activities[] = new ActivityFeedItem(
                id: 'campaign_' . $campaign->id,
                type: ActivityType::CAMPAIGN_CREATED,
                description: sprintf('You created <a href="%s" class="text-primary hover:underline">%s</a>', $campaignUrl, $campaign->getTitle()),
                occurredAt: $campaign->created_at ?? now(),
                amount: null,
                relatedEntityType: 'campaign',
                relatedEntityId: $campaign->id,
                relatedEntityTitle: $campaign->getTitle(),
            );
        }

        // Sort by date and limit
        usort($activities, fn ($a, $b): int => $b->occurredAt->timestamp <=> $a->occurredAt->timestamp);

        return array_slice($activities, 0, $limit);
    }

    public function getUserImpactMetrics(int $userId): ImpactMetrics
    {
        // Get all metrics in optimized queries
        $donationStats = DB::table('donations as d')
            ->join('campaigns as c', 'd.campaign_id', '=', 'c.id')
            ->where('d.user_id', $userId)
            ->where('d.status', 'completed')
            ->whereNull('d.deleted_at')
            ->selectRaw('
                    SUM(d.amount) as total_donated,
                    COUNT(DISTINCT d.campaign_id) as unique_campaigns,
                    COUNT(DISTINCT c.organization_id) as organizations_supported
                ')
            ->first();

        $totalDonated = (float) ($donationStats->total_donated ?? 0);
        $peopleHelped = (int) ($totalDonated / 10);
        $organizationsSupported = (int) ($donationStats->organizations_supported ?? 0);
        $campaignCount = (int) ($donationStats->unique_campaigns ?? 0);

        // Simulate countries reached (for now, using a simple calculation)
        $countriesReached = min(15, (int) ($campaignCount * 1.5));

        // Category breakdown - get top 5 campaigns by donation amount
        $categoryBreakdown = DB::table('donations as d')
            ->join('campaigns as c', 'd.campaign_id', '=', 'c.id')
            ->where('d.user_id', $userId)
            ->where('d.status', 'completed')
            ->whereNull('d.deleted_at')
            ->select('c.title', DB::raw('SUM(d.amount) as total'))
            ->groupBy('c.title')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->pluck('total', 'title')
            ->toArray();

        return new ImpactMetrics(
            peopleHelped: $peopleHelped,
            countriesReached: $countriesReached,
            organizationsSupported: $organizationsSupported,
            categoryBreakdown: $categoryBreakdown,
            calculatedAt: now(),
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function getTopDonatorsLeaderboard(int $organizationId, int $limit = 5): array
    {
        // Cache leaderboard for 30 minutes as it doesn't change frequently
        $topDonators = Cache::remember("org_leaderboard:{$organizationId}:{$limit}", 1800, fn () => DB::table('users')
            ->join('donations', 'users.id', '=', 'donations.user_id')
            ->where('donations.status', 'completed')
            ->where('users.organization_id', $organizationId)
            ->select(
                'users.id',
                'users.name',
                DB::raw('SUM(donations.amount) as total_donations'),
                DB::raw('COUNT(DISTINCT donations.campaign_id) as campaigns_supported'),
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_donations', 'desc')
            ->limit($limit)
            ->get());

        $leaderboard = [];
        $rank = 1;
        $currentUserId = auth()->id();

        foreach ($topDonators as $donator) {
            $leaderboard[] = new LeaderboardEntry(
                rank: $rank++,
                name: $donator->name,
                totalDonations: new Money((float) $donator->total_donations, 'EUR'),
                campaignsSupported: (int) $donator->campaigns_supported,
                impactScore: min(10, (float) $donator->total_donations / 1000), // Simple impact score
                isCurrentUser: $donator->id === $currentUserId,
            );
        }

        return $leaderboard;
    }

    public function getUserOrganizationRanking(int $userId): int
    {
        // Cache the ranking for 10 minutes as it doesn't change frequently
        return (int) Cache::remember("user_ranking:{$userId}", 600, function () use ($userId): int {
            // Get user's total donations
            $userTotal = (float) Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('amount');

            // Use optimized query with the new indexes
            // We need to use a subquery to count the grouped results
            $subQuery = DB::table('donations')
                ->join('users', 'donations.user_id', '=', 'users.id')
                ->where('donations.status', 'completed')
                ->where('users.organization_id', 1) // Assuming organization ID 1
                ->select('donations.user_id', DB::raw('SUM(donations.amount) as total'))
                ->groupBy('donations.user_id')
                ->havingRaw('SUM(donations.amount) > ?', [$userTotal]);

            $higherRankedCount = (int) DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
                ->mergeBindings($subQuery)
                ->count();

            return $higherRankedCount + 1;
        });
    }

    public function getTotalActiveUsers(int $organizationId): int
    {
        // Cache the total active users count for 30 minutes
        return (int) Cache::remember("org_active_users:{$organizationId}", 1800, fn () => User::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count());
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptimizedCampaignStats(): array
    {
        return Cache::remember('dashboard:campaign_stats', 1800, function (): array {
            $stats = DB::table('campaigns')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_campaigns,
                    COUNT(CASE WHEN status = "active" THEN 1 END) as active_campaigns,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_campaigns,
                    COUNT(CASE WHEN status = "draft" THEN 1 END) as draft_campaigns,
                    AVG(goal_percentage) as avg_completion_percentage,
                    SUM(current_amount) as total_raised,
                    SUM(goal_amount) as total_goal,
                    AVG(CASE WHEN status = "completed" THEN DATEDIFF(completed_at, start_date) END) as avg_completion_days
                ')
                ->first();

            $topPerformers = DB::table('campaigns')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->orderBy('goal_percentage', 'desc')
                ->limit(5)
                ->select('id', 'title', 'current_amount', 'goal_amount', 'goal_percentage')
                ->get()
                ->toArray();

            return [
                'total_campaigns' => (int) ($stats->total_campaigns ?? 0),
                'active_campaigns' => (int) ($stats->active_campaigns ?? 0),
                'completed_campaigns' => (int) ($stats->completed_campaigns ?? 0),
                'draft_campaigns' => (int) ($stats->draft_campaigns ?? 0),
                'avg_completion_percentage' => round((float) ($stats->avg_completion_percentage ?? 0), 2),
                'total_raised' => (float) ($stats->total_raised ?? 0),
                'total_goal' => (float) ($stats->total_goal ?? 0),
                'avg_completion_days' => round((float) ($stats->avg_completion_days ?? 0), 1),
                'top_performers' => $topPerformers,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrganizationStats(): array
    {
        return Cache::remember('dashboard:organization_stats', 1800, function (): array {
            $stats = DB::table('organizations')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_organizations,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_organizations,
                    COUNT(CASE WHEN is_verified = 1 THEN 1 END) as verified_organizations
                ')
                ->first();

            $userStats = DB::table('users')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->selectRaw('
                    COUNT(*) as total_users,
                    COUNT(DISTINCT organization_id) as organizations_with_users
                ')
                ->first();

            $organizationActivity = DB::table('organizations as o')
                ->leftJoin('campaigns as c', 'o.id', '=', 'c.organization_id')
                ->leftJoin('users as u', 'o.id', '=', 'u.organization_id')
                ->whereNull('o.deleted_at')
                ->where('o.is_active', 1)
                ->groupBy('o.id', 'o.name')
                ->selectRaw('
                    o.id,
                    o.name,
                    COUNT(DISTINCT c.id) as campaign_count,
                    COUNT(DISTINCT u.id) as user_count,
                    SUM(c.current_amount) as total_raised
                ')
                ->orderBy('total_raised', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            return [
                'total_organizations' => (int) ($stats->total_organizations ?? 0),
                'active_organizations' => (int) ($stats->active_organizations ?? 0),
                'verified_organizations' => (int) ($stats->verified_organizations ?? 0),
                'total_users' => (int) ($userStats->total_users ?? 0),
                'organizations_with_users' => (int) ($userStats->organizations_with_users ?? 0),
                'top_organizations' => $organizationActivity,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentAnalytics(): array
    {
        return Cache::remember('dashboard:payment_analytics', 300, function (): array {
            $paymentStats = DB::table('payments as p')
                ->join('donations as d', 'p.donation_id', '=', 'd.id')
                ->whereNull('d.deleted_at')
                ->selectRaw('
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN p.status = "completed" THEN 1 END) as successful_payments,
                    COUNT(CASE WHEN p.status = "failed" THEN 1 END) as failed_payments,
                    COUNT(CASE WHEN p.status = "cancelled" THEN 1 END) as cancelled_payments,
                    AVG(CASE WHEN p.captured_at IS NOT NULL AND p.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, p.created_at, p.captured_at) END) as avg_processing_time_seconds
                ')
                ->first();

            $gatewayStats = DB::table('payments as p')
                ->join('donations as d', 'p.donation_id', '=', 'd.id')
                ->whereNull('d.deleted_at')
                ->groupBy('p.gateway_name')
                ->selectRaw('
                    p.gateway_name,
                    COUNT(*) as total_transactions,
                    COUNT(CASE WHEN p.status = "completed" THEN 1 END) as successful_transactions,
                    SUM(CASE WHEN p.status = "completed" THEN p.amount ELSE 0 END) as total_volume
                ')
                ->get()
                ->toArray();

            $methodStats = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->groupBy('payment_method')
                ->selectRaw('
                    payment_method,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount
                ')
                ->orderBy('transaction_count', 'desc')
                ->get()
                ->toArray();

            $totalPayments = (int) ($paymentStats->total_payments ?? 0);
            $successfulPayments = (int) ($paymentStats->successful_payments ?? 0);

            return [
                'total_payments' => $totalPayments,
                'successful_payments' => $successfulPayments,
                'failed_payments' => (int) ($paymentStats->failed_payments ?? 0),
                'cancelled_payments' => (int) ($paymentStats->cancelled_payments ?? 0),
                'success_rate' => $totalPayments > 0 ? round(($successfulPayments / $totalPayments) * 100, 2) : 0,
                'avg_processing_time_seconds' => round((float) ($paymentStats->avg_processing_time_seconds ?? 0), 2),
                'gateway_breakdown' => $gatewayStats,
                'payment_method_breakdown' => $methodStats,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getRealtimeStats(): array
    {
        return Cache::remember('dashboard:realtime_stats', 300, function (): array {
            $now = now();
            $today = $now->startOfDay();
            $thisHour = $now->startOfHour();

            $realtimeStats = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->selectRaw('
                    COUNT(CASE WHEN donated_at >= ? THEN 1 END) as donations_today,
                    COUNT(CASE WHEN donated_at >= ? THEN 1 END) as donations_this_hour,
                    SUM(CASE WHEN donated_at >= ? THEN amount ELSE 0 END) as amount_today,
                    SUM(CASE WHEN donated_at >= ? THEN amount ELSE 0 END) as amount_this_hour,
                    COUNT(CASE WHEN donated_at >= ? THEN 1 END) as donations_last_5_min
                ', [
                    $today,
                    $thisHour,
                    $today,
                    $thisHour,
                    $now->subMinutes(5),
                ])
                ->first();

            $activeUsers = DB::table('users')
                ->where('last_login_at', '>=', now()->subMinutes(15))
                ->count();

            $activeCampaigns = DB::table('campaigns')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->where('end_date', '>', now())
                ->count();

            $recentDonations = DB::table('donations as d')
                ->join('campaigns as c', 'd.campaign_id', '=', 'c.id')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->whereNull('d.deleted_at')
                ->where('d.status', 'completed')
                ->where('d.donated_at', '>=', now()->subMinutes(30))
                ->select('d.amount', 'd.donated_at', 'c.title as campaign_title', 'u.name as donor_name')
                ->orderBy('d.donated_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            return [
                'donations_today' => (int) ($realtimeStats->donations_today ?? 0),
                'donations_this_hour' => (int) ($realtimeStats->donations_this_hour ?? 0),
                'donations_last_5_min' => (int) ($realtimeStats->donations_last_5_min ?? 0),
                'amount_today' => (float) ($realtimeStats->amount_today ?? 0),
                'amount_this_hour' => (float) ($realtimeStats->amount_this_hour ?? 0),
                'active_users_15_min' => $activeUsers,
                'active_campaigns' => $activeCampaigns,
                'recent_donations' => $recentDonations,
                'last_updated' => $now->toISOString(),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getRevenueSummary(): array
    {
        return Cache::remember('dashboard:revenue_summary', 900, function (): array {
            $now = now();
            $currentMonth = $now->startOfMonth();
            $lastMonth = $now->subMonth()->startOfMonth();
            $currentYear = $now->startOfYear();
            $lastYear = $now->subYear()->startOfYear();

            $revenueStats = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->selectRaw('
                    SUM(amount) as total_revenue,
                    SUM(CASE WHEN donated_at >= ? THEN amount ELSE 0 END) as current_month_revenue,
                    SUM(CASE WHEN donated_at >= ? AND donated_at < ? THEN amount ELSE 0 END) as last_month_revenue,
                    SUM(CASE WHEN donated_at >= ? THEN amount ELSE 0 END) as current_year_revenue,
                    SUM(CASE WHEN donated_at >= ? AND donated_at < ? THEN amount ELSE 0 END) as last_year_revenue,
                    AVG(amount) as avg_donation_amount
                ', [
                    $currentMonth,
                    $lastMonth, $currentMonth,
                    $currentYear,
                    $lastYear, $currentYear,
                ])
                ->first();

            $monthlyTrend = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->where('donated_at', '>=', now()->subMonths(12))
                ->selectRaw('
                    DATE_FORMAT(donated_at, "%Y-%m") as month,
                    SUM(amount) as revenue,
                    COUNT(*) as donation_count
                ')
                ->groupBy(DB::raw('DATE_FORMAT(donated_at, "%Y-%m")'))
                ->orderBy('month')
                ->get()
                ->toArray();

            $topCampaignRevenue = DB::table('donations as d')
                ->join('campaigns as c', 'd.campaign_id', '=', 'c.id')
                ->whereNull('d.deleted_at')
                ->where('d.status', 'completed')
                ->groupBy('c.id', 'c.title')
                ->selectRaw('c.id, c.title, SUM(d.amount) as total_revenue')
                ->orderBy('total_revenue', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            $currentMonthRevenue = (float) ($revenueStats->current_month_revenue ?? 0);
            $lastMonthRevenue = (float) ($revenueStats->last_month_revenue ?? 0);
            $monthlyGrowth = $lastMonthRevenue > 0 ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

            return [
                'total_revenue' => (float) ($revenueStats->total_revenue ?? 0),
                'current_month_revenue' => $currentMonthRevenue,
                'last_month_revenue' => $lastMonthRevenue,
                'monthly_growth_percentage' => round($monthlyGrowth, 2),
                'current_year_revenue' => (float) ($revenueStats->current_year_revenue ?? 0),
                'last_year_revenue' => (float) ($revenueStats->last_year_revenue ?? 0),
                'avg_donation_amount' => round((float) ($revenueStats->avg_donation_amount ?? 0), 2),
                'monthly_trend' => $monthlyTrend,
                'top_campaigns_by_revenue' => $topCampaignRevenue,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getSuccessRates(): array
    {
        return Cache::remember('dashboard:success_rates', 900, function (): array {
            $campaignStats = DB::table('campaigns')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_campaigns,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_campaigns,
                    COUNT(CASE WHEN goal_percentage >= 100 THEN 1 END) as fully_funded_campaigns,
                    COUNT(CASE WHEN goal_percentage >= 75 THEN 1 END) as mostly_funded_campaigns
                ')
                ->first();

            $donationStats = DB::table('donations')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_donations,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_donations,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_donations
                ')
                ->first();

            $paymentMethodSuccess = DB::table('donations')
                ->whereNull('deleted_at')
                ->groupBy('payment_method')
                ->selectRaw('
                    payment_method,
                    COUNT(*) as total_attempts,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_attempts
                ')
                ->having('total_attempts', '>', 10)
                ->get()
                ->map(fn ($method): array => [
                    'payment_method' => $method->payment_method,
                    'success_rate' => $method->total_attempts > 0 ? round(($method->successful_attempts / $method->total_attempts) * 100, 2) : 0,
                    'total_attempts' => (int) $method->total_attempts,
                ])
                ->toArray();

            $organizationSuccess = DB::table('organizations as o')
                ->join('campaigns as c', 'o.id', '=', 'c.organization_id')
                ->whereNull('o.deleted_at')
                ->whereNull('c.deleted_at')
                ->groupBy('o.id', 'o.name')
                ->selectRaw('
                    o.id,
                    o.name,
                    COUNT(c.id) as total_campaigns,
                    COUNT(CASE WHEN c.status = "completed" THEN 1 END) as completed_campaigns
                ')
                ->having('total_campaigns', '>', 2)
                ->get()
                ->map(fn ($org): array => [
                    'organization_name' => $org->name,
                    'success_rate' => $org->total_campaigns > 0 ? round(($org->completed_campaigns / $org->total_campaigns) * 100, 2) : 0,
                    'total_campaigns' => (int) $org->total_campaigns,
                ])
                ->sortByDesc('success_rate')
                ->take(10)
                ->values()
                ->toArray();

            $totalCampaigns = (int) ($campaignStats->total_campaigns ?? 0);
            $totalDonations = (int) ($donationStats->total_donations ?? 0);

            return [
                'campaign_completion_rate' => $totalCampaigns > 0 ? round(((int) ($campaignStats->completed_campaigns ?? 0) / $totalCampaigns) * 100, 2) : 0,
                'campaign_funding_rate' => $totalCampaigns > 0 ? round(((int) ($campaignStats->fully_funded_campaigns ?? 0) / $totalCampaigns) * 100, 2) : 0,
                'campaign_mostly_funded_rate' => $totalCampaigns > 0 ? round(((int) ($campaignStats->mostly_funded_campaigns ?? 0) / $totalCampaigns) * 100, 2) : 0,
                'donation_success_rate' => $totalDonations > 0 ? round(((int) ($donationStats->successful_donations ?? 0) / $totalDonations) * 100, 2) : 0,
                'payment_method_success_rates' => $paymentMethodSuccess,
                'organization_success_rates' => $organizationSuccess,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getTimeBasedAnalytics(): array
    {
        return Cache::remember('dashboard:time_analytics', 600, function (): array {
            $hourlyDonations = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->where('donated_at', '>=', now()->subDays(7))
                ->selectRaw('
                    HOUR(donated_at) as hour,
                    COUNT(*) as donation_count,
                    SUM(amount) as total_amount
                ')
                ->groupBy(DB::raw('HOUR(donated_at)'))
                ->orderBy('hour')
                ->get()
                ->toArray();

            $dailyTrend = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->where('donated_at', '>=', now()->subDays(30))
                ->selectRaw('
                    DATE(donated_at) as date,
                    COUNT(*) as donation_count,
                    SUM(amount) as total_amount,
                    COUNT(DISTINCT user_id) as unique_donors
                ')
                ->groupBy(DB::raw('DATE(donated_at)'))
                ->orderBy('date')
                ->get()
                ->toArray();

            $weeklyTrend = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->where('donated_at', '>=', now()->subWeeks(12))
                ->selectRaw('
                    YEARWEEK(donated_at) as week,
                    COUNT(*) as donation_count,
                    SUM(amount) as total_amount,
                    COUNT(DISTINCT user_id) as unique_donors
                ')
                ->groupBy(DB::raw('YEARWEEK(donated_at)'))
                ->orderBy('week')
                ->get()
                ->toArray();

            $dayOfWeekAnalysis = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->where('donated_at', '>=', now()->subDays(90))
                ->selectRaw('
                    DAYOFWEEK(donated_at) as day_of_week,
                    DAYNAME(donated_at) as day_name,
                    COUNT(*) as donation_count,
                    AVG(amount) as avg_amount
                ')
                ->groupBy(DB::raw('DAYOFWEEK(donated_at)'), DB::raw('DAYNAME(donated_at)'))
                ->orderBy('day_of_week')
                ->get()
                ->toArray();

            return [
                'hourly_donations' => $hourlyDonations,
                'daily_trend_30_days' => $dailyTrend,
                'weekly_trend_12_weeks' => $weeklyTrend,
                'day_of_week_analysis' => $dayOfWeekAnalysis,
                'peak_hour' => collect($hourlyDonations)->sortByDesc('donation_count')->first()['hour'] ?? null,
                'peak_day' => collect($dayOfWeekAnalysis)->sortByDesc('donation_count')->first()['day_name'] ?? null,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getTotalDonationsStats(): array
    {
        return Cache::remember('dashboard:total_donations_stats', 600, function (): array {
            $overallStats = DB::table('donations')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_donations,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_donations,
                    COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_donations,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_donations,
                    SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_amount,
                    AVG(CASE WHEN status = "completed" THEN amount END) as avg_donation,
                    MIN(CASE WHEN status = "completed" THEN amount END) as min_donation,
                    MAX(CASE WHEN status = "completed" THEN amount END) as max_donation,
                    COUNT(DISTINCT user_id) as unique_donors,
                    COUNT(DISTINCT campaign_id) as campaigns_with_donations
                ')
                ->first();

            $donationRanges = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->selectRaw('
                    COUNT(CASE WHEN amount < 10 THEN 1 END) as under_10,
                    COUNT(CASE WHEN amount >= 10 AND amount < 50 THEN 1 END) as range_10_50,
                    COUNT(CASE WHEN amount >= 50 AND amount < 100 THEN 1 END) as range_50_100,
                    COUNT(CASE WHEN amount >= 100 AND amount < 500 THEN 1 END) as range_100_500,
                    COUNT(CASE WHEN amount >= 500 AND amount < 1000 THEN 1 END) as range_500_1000,
                    COUNT(CASE WHEN amount >= 1000 THEN 1 END) as over_1000
                ')
                ->first();

            $currencyBreakdown = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->groupBy('currency')
                ->selectRaw('currency, COUNT(*) as count, SUM(amount) as total_amount')
                ->orderBy('total_amount', 'desc')
                ->get()
                ->toArray();

            $topDonors = DB::table('donations as d')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->whereNull('d.deleted_at')
                ->where('d.status', 'completed')
                ->groupBy('u.id', 'u.name')
                ->selectRaw('u.id, u.name, COUNT(d.id) as donation_count, SUM(d.amount) as total_donated')
                ->orderBy('total_donated', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            $recurringStats = DB::table('donations')
                ->whereNull('deleted_at')
                ->where('status', 'completed')
                ->selectRaw('
                    COUNT(CASE WHEN recurring = 1 THEN 1 END) as recurring_donations,
                    COUNT(CASE WHEN recurring = 0 THEN 1 END) as one_time_donations,
                    SUM(CASE WHEN recurring = 1 THEN amount ELSE 0 END) as recurring_amount,
                    SUM(CASE WHEN recurring = 0 THEN amount ELSE 0 END) as one_time_amount
                ')
                ->first();

            return [
                'total_donations' => (int) ($overallStats->total_donations ?? 0),
                'completed_donations' => (int) ($overallStats->completed_donations ?? 0),
                'pending_donations' => (int) ($overallStats->pending_donations ?? 0),
                'failed_donations' => (int) ($overallStats->failed_donations ?? 0),
                'total_amount' => (float) ($overallStats->total_amount ?? 0),
                'avg_donation' => round((float) ($overallStats->avg_donation ?? 0), 2),
                'min_donation' => (float) ($overallStats->min_donation ?? 0),
                'max_donation' => (float) ($overallStats->max_donation ?? 0),
                'unique_donors' => (int) ($overallStats->unique_donors ?? 0),
                'campaigns_with_donations' => (int) ($overallStats->campaigns_with_donations ?? 0),
                'donation_ranges' => [
                    'under_10' => (int) ($donationRanges->under_10 ?? 0),
                    '10_to_50' => (int) ($donationRanges->range_10_50 ?? 0),
                    '50_to_100' => (int) ($donationRanges->range_50_100 ?? 0),
                    '100_to_500' => (int) ($donationRanges->range_100_500 ?? 0),
                    '500_to_1000' => (int) ($donationRanges->range_500_1000 ?? 0),
                    'over_1000' => (int) ($donationRanges->over_1000 ?? 0),
                ],
                'currency_breakdown' => $currencyBreakdown,
                'top_donors' => $topDonors,
                'recurring_stats' => [
                    'recurring_donations' => (int) ($recurringStats->recurring_donations ?? 0),
                    'one_time_donations' => (int) ($recurringStats->one_time_donations ?? 0),
                    'recurring_amount' => (float) ($recurringStats->recurring_amount ?? 0),
                    'one_time_amount' => (float) ($recurringStats->one_time_amount ?? 0),
                ],
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserEngagementStats(): array
    {
        return Cache::remember('dashboard:user_engagement_stats', 600, function (): array {
            $userStats = DB::table('users')
                ->whereNull('deleted_at')
                ->selectRaw('
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN status = "active" THEN 1 END) as active_users,
                    COUNT(CASE WHEN last_login_at >= ? THEN 1 END) as users_last_30_days,
                    COUNT(CASE WHEN last_login_at >= ? THEN 1 END) as users_last_7_days,
                    COUNT(CASE WHEN last_login_at >= ? THEN 1 END) as users_today
                ', [
                    now()->subDays(30),
                    now()->subDays(7),
                    now()->startOfDay(),
                ])
                ->first();

            $donorEngagement = DB::table('users as u')
                ->leftJoin('donations as d', function ($join): void {
                    $join->on('u.id', '=', 'd.user_id')
                        ->whereNull('d.deleted_at')
                        ->where('d.status', 'completed');
                })
                ->whereNull('u.deleted_at')
                ->where('u.status', 'active')
                ->selectRaw('
                    COUNT(DISTINCT u.id) as total_active_users,
                    COUNT(DISTINCT CASE WHEN d.id IS NOT NULL THEN u.id END) as users_who_donated,
                    COUNT(DISTINCT CASE WHEN d.donated_at >= ? THEN u.id END) as donors_last_30_days,
                    COUNT(DISTINCT CASE WHEN d.donated_at >= ? THEN u.id END) as donors_last_7_days
                ', [
                    now()->subDays(30),
                    now()->subDays(7),
                ])
                ->first();

            $campaignEngagement = DB::table('users as u')
                ->leftJoin('campaigns as c', function ($join): void {
                    $join->on('u.id', '=', 'c.user_id')
                        ->whereNull('c.deleted_at');
                })
                ->whereNull('u.deleted_at')
                ->where('u.status', 'active')
                ->selectRaw('
                    COUNT(DISTINCT CASE WHEN c.id IS NOT NULL THEN u.id END) as users_with_campaigns,
                    COUNT(DISTINCT CASE WHEN c.created_at >= ? THEN u.id END) as campaign_creators_last_30_days
                ', [
                    now()->subDays(30),
                ])
                ->first();

            $repeatDonors = DB::table('donations as d')
                ->join('users as u', 'd.user_id', '=', 'u.id')
                ->whereNull('d.deleted_at')
                ->whereNull('u.deleted_at')
                ->where('d.status', 'completed')
                ->groupBy('u.id', 'u.name')
                ->havingRaw('COUNT(d.id) > 1')
                ->selectRaw('u.id, u.name, COUNT(d.id) as donation_count, SUM(d.amount) as total_amount')
                ->orderBy('donation_count', 'desc')
                ->limit(20)
                ->get()
                ->toArray();

            $userActivityByRole = DB::table('users as u')
                ->leftJoin('donations as d', function ($join): void {
                    $join->on('u.id', '=', 'd.user_id')
                        ->whereNull('d.deleted_at')
                        ->where('d.status', 'completed');
                })
                ->leftJoin('campaigns as c', function ($join): void {
                    $join->on('u.id', '=', 'c.user_id')
                        ->whereNull('c.deleted_at');
                })
                ->whereNull('u.deleted_at')
                ->groupBy('u.role')
                ->selectRaw('
                    u.role,
                    COUNT(DISTINCT u.id) as user_count,
                    COUNT(DISTINCT d.id) as donations_made,
                    COUNT(DISTINCT c.id) as campaigns_created,
                    SUM(d.amount) as total_donated
                ')
                ->get()
                ->toArray();

            $totalActiveUsers = (int) ($userStats->active_users ?? 0);
            $usersWhoDonated = (int) ($donorEngagement->users_who_donated ?? 0);

            return [
                'total_users' => (int) ($userStats->total_users ?? 0),
                'active_users' => $totalActiveUsers,
                'users_last_30_days' => (int) ($userStats->users_last_30_days ?? 0),
                'users_last_7_days' => (int) ($userStats->users_last_7_days ?? 0),
                'users_today' => (int) ($userStats->users_today ?? 0),
                'donor_participation_rate' => $totalActiveUsers > 0 ? round(($usersWhoDonated / $totalActiveUsers) * 100, 2) : 0,
                'donors_last_30_days' => (int) ($donorEngagement->donors_last_30_days ?? 0),
                'donors_last_7_days' => (int) ($donorEngagement->donors_last_7_days ?? 0),
                'users_with_campaigns' => (int) ($campaignEngagement->users_with_campaigns ?? 0),
                'campaign_creators_last_30_days' => (int) ($campaignEngagement->campaign_creators_last_30_days ?? 0),
                'repeat_donors' => $repeatDonors,
                'user_activity_by_role' => $userActivityByRole,
            ];
        });
    }

    private function calculateImpactScoreOptimized(float $totalDonated, int $campaignsSupported, int $recentDonationsCount): float
    {
        // Calculate impact score based on already fetched data
        $amountScore = min(100, ($totalDonated / 10000) * 100);
        $diversityScore = min(100, $campaignsSupported * 10);
        $frequencyScore = min(100, $recentDonationsCount * 5);

        return round(($amountScore + $diversityScore + $frequencyScore) / 3, 2);
    }
}
