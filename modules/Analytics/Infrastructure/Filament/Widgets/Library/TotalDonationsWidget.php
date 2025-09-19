<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Analytics\Domain\Model\ApplicationCache;

class TotalDonationsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    /** @var int|string|array<string, mixed> */
    /** @var array<string, int|null>|int|string */
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $stats = ApplicationCache::getStats('total_donations');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        return [
            Stat::make('Total Donations', number_format($stats['total_donations'] ?? 0))
                ->description($this->getGrowthDescription($stats['total_donations'] ?? 0, $stats['previous_donations'] ?? 0))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Amount', '€' . number_format($stats['total_amount'] ?? 0, 2))
                ->description($this->getGrowthDescription($stats['total_amount'] ?? 0, $stats['previous_amount'] ?? 0))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Average Donation', '€' . number_format($stats['avg_donation'] ?? 0, 2))
                ->description('Per donation average')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Unique Donors', number_format($stats['unique_donors'] ?? 0))
                ->description('Active contributors')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
        ];
    }

    /**
     * @return array<int, Stat>
     */
    private function getEmptyData(): array
    {
        return [
            Stat::make('Total Donations', number_format(0))
                ->description('No data available')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Amount', '€' . number_format(0, 2))
                ->description('No data available')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Average Donation', '€' . number_format(0, 2))
                ->description('No data available')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Unique Donors', number_format(0))
                ->description('No data available')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
        ];
    }

    protected function getGrowthDescription(int|float $current, int|float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'New activity (no previous data)' : 'No previous data';
        }

        $percentage = (($current - $previous) / $previous) * 100;
        $direction = $percentage >= 0 ? 'increase' : 'decrease';

        return sprintf('%+.1f%% %s from previous period', $percentage, $direction);
    }
}
