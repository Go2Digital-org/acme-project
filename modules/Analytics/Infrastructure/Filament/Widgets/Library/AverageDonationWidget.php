<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Analytics\Domain\Model\ApplicationCache;

class AverageDonationWidget extends BaseWidget
{
    protected ?string $heading = 'Donation Statistics';

    protected ?string $pollingInterval = '10m';

    /** @var array<string, int|null>|int|string */
    /** @var array<string, int|null>|int|string */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 15;

    public static function canView(): bool
    {
        return true;
    }

    /** @return array<int, Stat> */
    protected function getStats(): array
    {
        $stats = ApplicationCache::getStats('average_donation');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        $avgDonation = $stats['average_donation'] ?? 0;
        $totalAmount = $stats['total_amount'] ?? 0;
        $totalDonors = $stats['total_donors'] ?? 0;
        $formattedAvg = $stats['formatted_average'] ?? '€0.00';

        return [
            Stat::make('Average Donation', $formattedAvg)
                ->description('Average per donor')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Total Amount', '€' . number_format($totalAmount, 2))
                ->description('Total donated amount')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('primary'),

            Stat::make('Total Donors', number_format($totalDonors))
                ->description('Unique donors')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Raw Average', '€' . number_format($avgDonation, 2))
                ->description('Mathematical average')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),
        ];
    }

    /** @return array<int, Stat> */
    private function getEmptyData(): array
    {
        return [
            Stat::make('Average Donation', '€0.00')
                ->description('No data available')
                ->descriptionIcon('heroicon-m-heart')
                ->color('success'),

            Stat::make('Median Donation', '€0.00')
                ->description('No data available')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Total Donations', '0')
                ->description('No data available')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Largest Donation', '€0.00')
                ->description('No data available')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
