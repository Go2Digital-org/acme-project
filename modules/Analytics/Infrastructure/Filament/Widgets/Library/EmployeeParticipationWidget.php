<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Filament\Widgets\Library;

use Filament\Widgets\ChartWidget;
use Modules\Analytics\Domain\Model\ApplicationCache;

class EmployeeParticipationWidget extends ChartWidget
{
    protected ?string $heading = 'Employee Participation & Engagement';

    protected ?string $description = 'Employee engagement metrics and participation rates';

    protected ?string $pollingInterval = '5m';

    /** @var int|string|array<string, mixed> */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function getDescription(): ?string
    {
        $stats = ApplicationCache::getStats('employee_participation');

        if ($stats === []) {
            return 'Employee engagement metrics and participation rates';
        }

        $participationRate = number_format($stats['participation_rate'] ?? 0, 2);
        $participatingEmployees = number_format($stats['participating_employees'] ?? 0);
        $totalEmployees = number_format($stats['total_employees'] ?? 0);

        return "Participation Rate: {$participationRate}% | Active: {$participatingEmployees} / {$totalEmployees}";
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
        $stats = ApplicationCache::getStats('employee_participation');

        if ($stats === []) {
            return $this->getEmptyData();
        }

        // Create participation vs non-participation data for pie chart
        $participating = $stats['participating_employees'] ?? 0;
        $nonParticipating = $stats['non_participating'] ?? 0;

        return [
            'datasets' => [
                [
                    'label' => 'Employee Participation',
                    'data' => [$participating, $nonParticipating],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',  // Blue for participating
                        'rgba(156, 163, 175, 0.8)',   // Gray for non-participating
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(156, 163, 175)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Participating Employees', 'Non-Participating Employees'],
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
                    'label' => 'Active Participants',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                ],
                [
                    'label' => 'Donations Count',
                    'data' => [0, 0, 0, 0, 0, 0, 0],
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
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
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Participants',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Donations',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
