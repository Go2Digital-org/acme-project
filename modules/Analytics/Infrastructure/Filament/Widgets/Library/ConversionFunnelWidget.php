<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class ConversionFunnelWidget extends ChartWidget
{
    protected ?string $heading = 'Donation Conversion Funnel';

    protected ?string $description = 'User journey from campaign view to donation';

    protected ?string $pollingInterval = '15m';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 12;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('conversion_funnel');

        if ($stats === []) {
            return 'User journey from campaign view to donation';
        }

        $completedDonations = $stats['donation_completed'] ?? 0;
        $conversionRate = $stats['conversion_rate'] ?? 0;

        return "Last 30 days | Completed: {$completedDonations} | Conversion Rate: {$conversionRate}%";
    }

    public static function canView(): bool
    {
        return true;
    }

    protected function getData(): array
    {
        $stats = ApplicationCache::getStats('conversion_funnel');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        // Use actual conversion funnel data from cache
        $funnelData = [
            $stats['visitors'] ?? 0,
            $stats['campaign_views'] ?? 0,
            $stats['donation_initiated'] ?? 0,
            $stats['donation_completed'] ?? 0,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Conversion Funnel',
                    'data' => $funnelData,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.6)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => ['Visitors', 'Campaign Views', 'Donation Initiated', 'Donation Completed'],
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
                    'label' => 'Conversion Funnel',
                    'data' => [0, 0, 0, 0],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.9)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(239, 68, 68, 0.6)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => ['Campaign Views', 'Interested Users', 'Started Donation', 'Completed Donation'],
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
                    'display' => false,
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const value = context.parsed.y;
                            const total = context.dataset.data[0];
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ": " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Users',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Funnel Stage',
                    ],
                ],
            ],
        ];
    }
}
