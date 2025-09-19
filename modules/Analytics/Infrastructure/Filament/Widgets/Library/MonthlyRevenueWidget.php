<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class MonthlyRevenueWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Revenue Tracking';

    protected ?string $description = 'Revenue trends and monthly performance analysis';

    protected ?string $pollingInterval = '15m';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('revenue_summary');

        if ($stats === []) {
            return 'Revenue trends and monthly performance analysis';
        }

        $currentMonthFormatted = '€' . number_format($stats['current_month_revenue'] ?? 0, 0);
        $growthFormatted = ($stats['monthly_growth_percentage'] ?? 0) >= 0 ? '+' . number_format($stats['monthly_growth_percentage'] ?? 0, 1) . '%' : number_format($stats['monthly_growth_percentage'] ?? 0, 1) . '%';
        $totalRevenue = '€' . number_format($stats['total_revenue'] ?? 0, 0);

        return "This Month: {$currentMonthFormatted} | Total: {$totalRevenue} | Growth: {$growthFormatted}";
    }

    public static function canView(): bool
    {
        return true; // Simplified - always visible
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $stats = ApplicationCache::getStats('revenue_summary');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        // Extract monthly data from monthly_trend
        $monthlyData = [];
        $monthLabels = [];

        if (isset($stats['monthly_trend']) && is_array($stats['monthly_trend'])) {
            foreach ($stats['monthly_trend'] as $monthData) {
                $monthLabels[] = $monthData['month'] ?? '';
                $monthlyData[] = (float) ($monthData['revenue'] ?? 0);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Monthly Revenue',
                    'data' => $monthlyData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => 'rgb(59, 130, 246)',
                    'pointHoverBackgroundColor' => 'rgb(29, 78, 216)',
                    'pointHoverBorderColor' => 'rgb(29, 78, 216)',
                ],
            ],
            'labels' => $monthLabels,
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
                    'label' => 'Current Year',
                    'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => 'rgb(59, 130, 246)',
                    'pointHoverBackgroundColor' => 'rgb(29, 78, 216)',
                    'pointHoverBorderColor' => 'rgb(29, 78, 216)',
                ],
                [
                    'label' => 'Previous Year',
                    'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(156, 163, 175)',
                    'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                    'tension' => 0.4,
                    'fill' => false,
                    'borderDash' => [5, 5],
                    'pointBackgroundColor' => 'rgb(156, 163, 175)',
                    'pointBorderColor' => 'rgb(156, 163, 175)',
                ],
                [
                    'label' => 'Target',
                    'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.05)',
                    'tension' => 0.1,
                    'fill' => false,
                    'borderDash' => [3, 3],
                    'pointStyle' => 'triangle',
                    'pointBackgroundColor' => 'rgb(16, 185, 129)',
                    'pointBorderColor' => 'rgb(16, 185, 129)',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    public function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'boxWidth' => 12,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.dataset.label || "";
                            const value = new Intl.NumberFormat("en-US", {
                                style: "currency",
                                currency: "USD"
                            }).format(context.parsed.y);
                            return label + ": " + value;
                        }',
                    ],
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Monthly Revenue Analysis',
                    'padding' => [0, 0, 20, 0],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Month',
                        'font' => [
                            'weight' => 'bold',
                        ],
                    ],
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Revenue ($)',
                        'font' => [
                            'weight' => 'bold',
                        ],
                    ],
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(0, 0, 0, 0.1)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) {
                            return new Intl.NumberFormat("en-US", {
                                style: "currency",
                                currency: "USD",
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        }',
                    ],
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 5,
                    'hoverRadius' => 8,
                    'borderWidth' => 2,
                ],
                'line' => [
                    'borderWidth' => 3,
                ],
            ],
        ];
    }
}
