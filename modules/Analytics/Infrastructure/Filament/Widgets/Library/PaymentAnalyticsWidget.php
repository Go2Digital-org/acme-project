<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Analytics\Domain\Model\ApplicationCache;

class PaymentAnalyticsWidget extends BaseWidget
{
    protected ?string $heading = 'Payment Analytics';

    protected ?string $pollingInterval = '30m';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 14;

    public static function canView(): bool
    {
        return true;
    }

    /**
     * @return array<int, Stat>
     */
    protected function getStats(): array
    {
        $stats = ApplicationCache::getStats('payment_analytics');

        if ($stats === []) {
            return $this->getEmptyStats();
        }

        return $this->formatStats($stats);
    }

    /**
     * Format statistics for display
     *
     * @param  array<string, mixed>  $data
     * @return array<int, Stat>
     */
    protected function formatStats(array $data): array
    {
        // Calculate total processed from payment method breakdown
        $totalProcessed = 0;
        if (isset($data['payment_method_breakdown']) && is_array($data['payment_method_breakdown'])) {
            foreach ($data['payment_method_breakdown'] as $method) {
                $totalProcessed += (float) $method['total_amount'];
            }
        }

        $successRate = $data['success_rate'] ?? 0;
        $failedPayments = $data['failed_payments'] ?? 0;
        $avgProcessingTime = $data['avg_processing_time_seconds'] ?? 0;

        return [
            Stat::make('Total Processed', '€' . number_format($totalProcessed, 0))
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('success'),

            Stat::make('Success Rate', number_format($successRate, 1) . '%')
                ->description('Payment success')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($successRate >= 95 ? 'success' : 'warning'),

            Stat::make('Failed Payments', number_format($failedPayments))
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($failedPayments > 10 ? 'danger' : 'warning'),

            Stat::make('Avg Processing', $avgProcessingTime . 's')
                ->description('Payment speed')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }

    /**
     * Get empty statistics when no data available
     *
     * @return array<int, Stat>
     */
    protected function getEmptyStats(): array
    {
        return [
            Stat::make('Total Processed', '€0')
                ->description('Last 30 days')
                ->descriptionIcon('heroicon-m-currency-euro')
                ->color('gray'),

            Stat::make('Success Rate', '0%')
                ->description('Payment success')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('gray'),

            Stat::make('Failed Payments', '0')
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('gray'),

            Stat::make('Avg Processing', '0s')
                ->description('Payment speed')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
