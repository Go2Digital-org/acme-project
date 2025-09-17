<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Analytics\Domain\Model\ApplicationCache;

class GoalCompletionWidget extends BaseWidget
{
    protected ?string $heading = 'Goal Completion Status';

    protected ?string $pollingInterval = '10m';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return true;
    }

    protected function getStats(): array
    {
        $stats = ApplicationCache::getStats('goal_completion');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        $completionRate = $stats['completion_rate'] ?? 0;
        $totalRaised = '€' . number_format($stats['total_raised'] ?? 0, 0);
        $campaignsCompleted = $stats['campaigns_completed'] ?? 0;
        $totalGoal = $stats['total_goal'] ?? 0;
        $remainingToGoal = $stats['remaining_to_goal'] ?? 0;

        return [
            Stat::make('Total Raised', $totalRaised)
                ->description('Campaign funds raised')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            Stat::make('Campaigns Completed', number_format($campaignsCompleted))
                ->description('Successfully completed')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Completion Rate', number_format($completionRate, 1) . '%')
                ->description('Goal achievement rate')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($completionRate >= 50 ? 'success' : 'warning'),

            Stat::make('Remaining Goal', $totalGoal > 0 ? '€' . number_format($remainingToGoal, 0) : 'N/A')
                ->description('To reach total goal')
                ->descriptionIcon('heroicon-m-flag')
                ->color('info'),
        ];
    }

    /**
     * @return array<Stat>
     */
    private function getEmptyData(): array
    {
        return [
            Stat::make('Completed Goals', '0')
                ->description('Reached target amount')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Near Completion', '0')
                ->description('80%+ of target reached')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Total Goals', '0')
                ->description('Active campaigns')
                ->descriptionIcon('heroicon-m-flag')
                ->color('info'),

            Stat::make('Completion Rate', '0%')
                ->description('Success rate')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('warning'),
        ];
    }
}
