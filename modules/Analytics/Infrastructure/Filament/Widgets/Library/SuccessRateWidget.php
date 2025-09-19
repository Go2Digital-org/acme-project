<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class SuccessRateWidget extends ChartWidget
{
    protected ?string $heading = 'Campaign Success Rate Analysis';

    protected ?string $description = 'Success rates and performance metrics across campaigns';

    protected ?string $pollingInterval = '15m';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('success_rate');

        if ($stats === []) {
            return 'Success rates and performance metrics across campaigns';
        }

        $campaignCompletion = number_format($stats['campaign_completion_rate'] ?? 0, 1);
        $fundingRate = number_format($stats['campaign_funding_rate'] ?? 0, 1);
        $donationSuccess = number_format($stats['donation_success_rate'] ?? 0, 1);

        return "Completion: {$campaignCompletion}% | Funding: {$fundingRate}% | Donations: {$donationSuccess}%";
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
        $stats = ApplicationCache::getStats('success_rate');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        // Create success rate comparison chart
        $data = [
            $stats['campaign_completion_rate'] ?? 0,
            $stats['campaign_funding_rate'] ?? 0,
            $stats['campaign_mostly_funded_rate'] ?? 0,
            $stats['donation_success_rate'] ?? 0,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Success Rate (%)',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.8)',   // Green for completion
                        'rgba(59, 130, 246, 0.8)',   // Blue for funding
                        'rgba(245, 158, 11, 0.8)',   // Orange for mostly funded
                        'rgba(139, 92, 246, 0.8)',    // Purple for donations
                    ],
                    'borderColor' => [
                        'rgb(16, 185, 129)',
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(139, 92, 246)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Campaign Completion', 'Campaign Funding', 'Mostly Funded', 'Donation Success'],
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
                    'label' => 'Success Rate (%)',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'pointBackgroundColor' => 'rgb(16, 185, 129)',
                    'pointBorderColor' => 'rgb(16, 185, 129)',
                    'pointRadius' => 5,
                ],
                [
                    'label' => 'Target Rate (%)',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 5],
                    'tension' => 0,
                    'pointRadius' => 0,
                ],
            ],
            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7'],
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
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.dataset.label + ": " + context.parsed.y + "%";
                        }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => false,
                    'min' => 0,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Success Rate (%)',
                    ],
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Time Period',
                    ],
                ],
            ],
        ];
    }
}
