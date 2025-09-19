<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Filament\DisabledWidgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

class CampaignApprovalStatsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $pendingCount = Campaign::where('status', CampaignStatus::PENDING_APPROVAL->value)->count();
        $approvedToday = Campaign::where('status', CampaignStatus::ACTIVE->value)
            ->whereDate('approved_at', today())
            ->count();
        $rejectedToday = Campaign::where('status', CampaignStatus::REJECTED->value)
            ->whereDate('rejected_at', today())
            ->count();

        // Calculate average approval time for campaigns approved in the last 30 days
        $avgApprovalTime = Campaign::where('status', CampaignStatus::ACTIVE->value)
            ->whereNotNull('approved_at')
            ->whereNotNull('submitted_for_approval_at')
            ->where('approved_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, submitted_for_approval_at, approved_at)) as avg_hours')
            ->value('avg_hours');

        $avgApprovalTimeFormatted = $avgApprovalTime
            ? ($avgApprovalTime < 24 ? round($avgApprovalTime) . ' hours' : round($avgApprovalTime / 24, 1) . ' days')
            : 'N/A';

        return [
            Stat::make('Pending Approval', $pendingCount)
                ->description('Campaigns waiting for review')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 5 ? 'danger' : ($pendingCount > 0 ? 'warning' : 'success'))
                ->url(route('filament.admin.resources.campaigns.index', [
                    'tableFilters' => ['status' => ['value' => CampaignStatus::PENDING_APPROVAL->value]],
                ])),

            Stat::make('Approved Today', $approvedToday)
                ->description('Campaigns approved today')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rejected Today', $rejectedToday)
                ->description('Campaigns rejected today')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Avg Approval Time', $avgApprovalTimeFormatted)
                ->description('Last 30 days average')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
