<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class ComparativeMetricsWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Comparison';

    protected ?string $description = 'This month vs last month performance';

    protected ?string $pollingInterval = '20m';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 16;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('comparative_metrics');

        if ($stats === []) {
            return 'This month vs last month performance comparison';
        }

        $change = $stats['growth_rate'] ?? 0;
        $trend = $change >= 0 ? '+' . $change . '%' : $change . '%';
        $currentMonth = '€' . number_format($stats['current_month'] ?? 0, 0);
        $previousMonth = '€' . number_format($stats['previous_month'] ?? 0, 0);

        return "Current: {$currentMonth} | Previous: {$previousMonth} | Growth: {$trend}";
    }

    public static function canView(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $stats = ApplicationCache::getStats('comparative_metrics');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        // Convert amounts to thousands for better chart readability
        $currentMonth = ($stats['current_month'] ?? 0) / 1000;
        $previousMonth = ($stats['previous_month'] ?? 0) / 1000;

        return [
            'datasets' => [
                [
                    'label' => 'Current Month (€K)',
                    'data' => [$currentMonth],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Previous Month (€K)',
                    'data' => [$previousMonth],
                    'backgroundColor' => 'rgba(156, 163, 175, 0.8)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Monthly Revenue Comparison'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getEmptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'This Month',
                    'data' => [0, 0, 0],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Last Month',
                    'data' => [0, 0, 0],
                    'backgroundColor' => 'rgba(156, 163, 175, 0.8)',
                    'borderColor' => 'rgb(156, 163, 175)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Donations Count', 'Amount (€)', 'New Campaigns'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
