<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Analytics\Domain\Model\ApplicationCache;

class RealTimeStatsWidget extends BaseWidget
{
    protected ?string $heading = 'Real-Time Statistics';

    protected ?string $pollingInterval = '30s';

    /** @var int|string|array<string, mixed> */
    /** @var array<string, int|null>|int|string */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return true;
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $stats = ApplicationCache::getStats('realtime_stats');

        if ($stats === []) {
            return $this->getEmptyStats();
        }

        return [
            Stat::make('Today\'s Donations', $stats['donations_today'] ?? 0)
                ->description('Donations made today')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Today\'s Amount', '€' . number_format($stats['amount_today'] ?? 0, 0))
                ->description('Total raised today')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('primary'),

            Stat::make('Active Campaigns', $stats['active_campaigns'] ?? 0)
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),

            Stat::make('Active Users', $stats['active_users_15_min'] ?? 0)
                ->description('Users active (15 min)')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning'),
        ];
    }

    /**
     * @return array<int, Stat>
     */
    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Today\'s Donations', '0')
                ->description('Donations made today')
                ->descriptionIcon('heroicon-m-heart')
                ->color('gray'),

            Stat::make('Today\'s Amount', '€0')
                ->description('Total raised today')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('gray'),

            Stat::make('Active Campaigns', '0')
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('gray'),

            Stat::make('Online Users', '0')
                ->description('Users currently browsing')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),
        ];
    }
}
