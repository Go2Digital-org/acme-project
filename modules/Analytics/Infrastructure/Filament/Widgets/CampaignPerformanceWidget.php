<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class CampaignPerformanceWidget extends ChartWidget
{
    protected ?string $heading = 'Campaign Performance Overview';

    protected ?string $description = 'Success rates and completion metrics';

    protected ?string $pollingInterval = '30s';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('campaign_performance');

        if ($stats === []) {
            return 'Campaign performance overview';
        }

        return sprintf(
            'Success Rate: %.1f%% | Target: €%s | Raised: €%s',
            $stats['success_rate'] ?? 0,
            number_format(($stats['total_target'] ?? 0) / 100, 2),
            number_format(($stats['total_raised'] ?? 0) / 100, 2)
        );
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $stats = ApplicationCache::getStats('campaign_performance');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Campaign Status Distribution',
                    'data' => [
                        $stats['active_campaigns'] ?? 0,
                        $stats['completed_campaigns'] ?? 0,
                        $stats['successful_campaigns'] ?? 0,
                    ],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',  // Blue for active
                        'rgba(16, 185, 129, 0.8)',  // Green for completed
                        'rgba(245, 158, 11, 0.8)',  // Amber for successful
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                'Active Campaigns',
                'Completed Campaigns',
                'Successful Campaigns',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function getEmptyData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Campaign Status Distribution',
                    'data' => [0, 0, 0],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                'Active Campaigns',
                'Completed Campaigns',
                'Successful Campaigns',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            const label = context.label || "";
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ": " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '60%',
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
