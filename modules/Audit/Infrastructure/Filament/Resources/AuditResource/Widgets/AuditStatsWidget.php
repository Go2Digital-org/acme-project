<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Filament\Resources\AuditResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Audit\Domain\Model\Audit;

class AuditStatsWidget extends BaseWidget
{
    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $today = Audit::whereDate('created_at', today())->count();
        $thisWeek = Audit::where('created_at', '>=', now()->startOfWeek())->count();
        $thisMonth = Audit::where('created_at', '>=', now()->startOfMonth())->count();

        $byEvent = Audit::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('event, count(*) as count')
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();

        return [
            Stat::make('Today\'s Activity', $today)
                ->description('Audit events today')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 3, 4, 5, 6, 3, $today])
                ->color('success'),

            Stat::make('This Week', $thisWeek)
                ->description('Audit events this week')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),

            Stat::make('This Month', $thisMonth)
                ->description('Audit events this month')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),

            Stat::make('Most Common', ucfirst((string) (array_key_first($byEvent) ?? 'N/A')))
                ->description('Most frequent event type')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }
}
