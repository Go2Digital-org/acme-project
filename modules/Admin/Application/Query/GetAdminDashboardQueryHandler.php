<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Query;

use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Application\ReadModel\AdminDashboardReadModel;
use Modules\Admin\Application\ReadModel\SystemStatsReadModel;

final class GetAdminDashboardQueryHandler
{
    public function handle(GetAdminDashboardQuery $query): AdminDashboardReadModel
    {
        // Cache key for dashboard data
        $cacheKey = "admin.dashboard.{$query->userId}.{$query->dateRange}";

        return Cache::remember($cacheKey, 300, function () use ($query) {
            $systemStats = $this->buildSystemStats($query);
            $recentActivity = $this->buildRecentActivity($query);
            $quickActions = $this->buildQuickActions();
            $systemAlerts = $this->buildSystemAlerts();

            return new AdminDashboardReadModel(
                systemStats: $systemStats,
                recentActivity: $recentActivity,
                quickActions: $quickActions,
                systemAlerts: $systemAlerts,
                dateRange: $query->dateRange ?? 'month',
                lastUpdated: new DateTimeImmutable
            );
        });
    }

    private function buildSystemStats(GetAdminDashboardQuery $query): SystemStatsReadModel
    {
        if (! $query->includeSystemStats) {
            return new SystemStatsReadModel(0, 0, 0, 0.0, 0, []);
        }

        $dateRange = $this->getDateRangeCondition($query->dateRange);

        // Get basic statistics
        $totalUsers = DB::table('users')->count();

        $activeCampaigns = DB::table('campaigns')
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->count();

        $donationsQuery = DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id');

        if ($dateRange) {
            $donationsQuery->whereRaw($dateRange);
        }

        $totalDonations = $donationsQuery->count();
        $totalDonationAmount = $donationsQuery->sum('amount') ?? 0;

        $pendingApprovals = DB::table('campaigns')
            ->where('status', 'pending_approval')
            ->count();

        $performanceMetrics = $this->getPerformanceMetrics();

        return new SystemStatsReadModel(
            totalUsers: $totalUsers,
            activeCampaigns: $activeCampaigns,
            totalDonations: $totalDonations,
            totalDonationAmount: (float) $totalDonationAmount,
            pendingApprovals: $pendingApprovals,
            performanceMetrics: $performanceMetrics
        );
    }

    /**
     * @return array<string, array<int, object>>
     */
    private function buildRecentActivity(GetAdminDashboardQuery $query): array
    {
        if (! $query->includeRecentActivity) {
            return [];
        }

        return [
            'recent_campaigns' => DB::table('campaigns')
                ->select('id', 'title', 'created_at', 'status')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),

            'recent_donations' => DB::table('donations')
                ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->select('donations.id', 'donations.amount', 'donations.created_at', 'campaigns.title as campaign_title')
                ->orderBy('donations.created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),

            'recent_users' => DB::table('users')
                ->select('id', 'name', 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray(),
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildQuickActions(): array
    {
        return [
            [
                'label' => 'Clear Cache',
                'icon' => 'heroicon-o-trash',
                'action' => 'admin.cache.clear',
                'color' => 'warning',
            ],
            [
                'label' => 'Toggle Maintenance',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'action' => 'admin.maintenance.toggle',
                'color' => 'danger',
            ],
            [
                'label' => 'View System Status',
                'icon' => 'heroicon-o-chart-bar',
                'action' => 'admin.system.status',
                'color' => 'info',
            ],
            [
                'label' => 'Backup Database',
                'icon' => 'heroicon-o-archive-box',
                'action' => 'admin.backup.create',
                'color' => 'success',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildSystemAlerts(): array
    {
        $alerts = [];

        // Check disk space
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usagePercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;

        if ($usagePercentage > 85) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Disk space usage is above 85%',
                'action' => 'Check and clean up disk space',
            ];
        }

        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 10) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "There are {$failedJobs} failed jobs",
                'action' => 'Review and retry failed jobs',
            ];
        }

        return $alerts;
    }

    private function getDateRangeCondition(?string $dateRange): ?string
    {
        return match ($dateRange) {
            'week' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)',
            'month' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
            'year' => 'created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)',
            default => null,
        };
    }

    /**
     * @return array<string, int|float>
     */
    private function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'response_time_avg' => Cache::get('metrics.response_time_avg', 0),
            'requests_per_minute' => Cache::get('metrics.requests_per_minute', 0),
        ];
    }
}
