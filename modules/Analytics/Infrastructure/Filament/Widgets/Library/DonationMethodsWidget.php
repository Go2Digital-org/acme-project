<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class DonationMethodsWidget extends ChartWidget
{
    protected ?string $heading = 'Payment Method Distribution';

    protected ?string $description = 'Analysis of donation payment methods and preferences';

    protected ?string $pollingInterval = '10m';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 8;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('payment_analytics');

        if ($stats === [] || empty($stats['payment_method_breakdown'])) {
            return 'Analysis of donation payment methods and preferences';
        }

        $methods = $stats['payment_method_breakdown'] ?? [];
        $uniqueMethods = count($methods);
        $mostPopular = empty($methods) ? 'Unknown' : $methods[0]['payment_method'] ?? 'Unknown';
        $totalAmount = array_sum(array_column($methods, 'total_amount'));
        $avgFormatted = '€' . number_format($totalAmount / max(1, $uniqueMethods), 0);

        return "Most Popular: {$mostPopular} | Methods Used: {$uniqueMethods} | Total: {$avgFormatted}";
    }

    public static function canView(): bool
    {
        return true; // Simplified - always visible
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $stats = ApplicationCache::getStats('payment_analytics');

        if ($stats === [] || empty($stats['payment_method_breakdown'])) {
            return $this->getEmptyData();
        }

        $methods = $stats['payment_method_breakdown'] ?? [];
        $labels = array_column($methods, 'payment_method');
        $counts = array_column($methods, 'transaction_count');

        return [
            'datasets' => [
                [
                    'label' => 'Payment Methods',
                    'data' => $counts,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',   // Blue
                        'rgba(251, 191, 36, 0.8)',   // Yellow
                        'rgba(16, 185, 129, 0.8)',   // Green
                        'rgba(107, 114, 128, 0.8)',  // Gray
                        'rgba(239, 68, 68, 0.8)',    // Red
                        'rgba(245, 158, 11, 0.8)',   // Amber
                        'rgba(139, 92, 246, 0.8)',   // Purple
                        'rgba(236, 72, 153, 0.8)',   // Pink
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(251, 191, 36)',
                        'rgb(16, 185, 129)',
                        'rgb(107, 114, 128)',
                        'rgb(239, 68, 68)',
                        'rgb(245, 158, 11)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                    ],
                    'borderWidth' => 2,
                    'hoverBorderWidth' => 3,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => $labels,
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
                    'label' => 'Payment Methods',
                    'data' => [0, 0, 0, 0, 0],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(107, 114, 128, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(251, 191, 36)',
                        'rgb(16, 185, 129)',
                        'rgb(107, 114, 128)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                    'hoverBorderWidth' => 3,
                    'hoverOffset' => 8,
                ],
            ],
            'labels' => ['Credit Card', 'PayPal', 'Bank Transfer', 'iDEAL', 'SOFORT'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
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
                    'position' => 'right',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'generateLabels' => <<<'JS'
function(chart) {
    const data = chart.data;
    if (data.labels.length && data.datasets.length) {
        return data.labels.map((label, i) => {
            const dataset = data.datasets[0];
            const value = dataset.data[i];
            const total = dataset.data.reduce((a, b) => a + b, 0);
            const percentage = ((value / total) * 100).toFixed(1);
            return {
                text: label + " (" + percentage + "%)",
                fillStyle: dataset.backgroundColor[i],
                strokeStyle: dataset.borderColor[i],
                lineWidth: dataset.borderWidth,
                pointStyle: "circle",
                hidden: false,
                index: i
            };
        });
    }
    return [];
}
JS,
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'callbacks' => [
                        'label' => <<<'JS'
function(context) {
    const label = context.label || "";
    const value = context.parsed;
    const total = context.dataset.data.reduce((a, b) => a + b, 0);
    const percentage = ((value / total) * 100).toFixed(1);
    const currency = new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
        minimumFractionDigits: 0
    }).format(value);
    return label + ": " + currency + " (" + percentage + "%)";
}
JS,
                    ],
                ],
                'title' => [
                    'display' => true,
                    'text' => 'Payment Method Breakdown',
                    'padding' => [0, 0, 20, 0],
                ],
            ],
            'cutout' => '60%',
            'animation' => [
                'animateScale' => true,
                'animateRotate' => true,
            ],
            'elements' => [
                'arc' => [
                    'borderAlign' => 'inner',
                ],
            ],
        ];
    }
}
