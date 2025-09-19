<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Analytics\Domain\Model\ApplicationCache;

class OptimizedCampaignStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    /** @var int|string|array<string, mixed> */
    /** @var array<string, int|null>|int|string */
    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $stats = ApplicationCache::getStats('optimized_campaign_stats');

        if ($stats === []) {
            return $this->getEmptyStats();
        }

        return [
            Stat::make('Active Campaigns', number_format($stats['active_campaigns'] ?? 0))
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Completed Campaigns', number_format($stats['completed_campaigns'] ?? 0))
                ->description('Successfully finished')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),

            Stat::make('Total Raised', '€' . number_format($stats['total_raised'] ?? 0, 2))
                ->description('From all campaigns')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('warning'),

            Stat::make('Avg Completion', number_format($stats['avg_completion_percentage'] ?? 0, 1) . '%')
                ->description('Campaign completion rate')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }

    /**
     * @return array<int, Stat>
     */
    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Active Campaigns', '0')
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray'),

            Stat::make('New This Week', '0')
                ->description('Created in last 7 days')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('gray'),

            Stat::make('Total Raised', '€0')
                ->description('From active campaigns')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('gray'),

            Stat::make('Avg Progress', '0%')
                ->description('Campaign completion')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }

    protected function getPollingInterval(): ?string
    {
        return '300s';
    }
}
