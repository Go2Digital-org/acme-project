<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\ReadModel;

use DateTime;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Analytics Report read model optimized for generated reports and dashboards.
 */
class AnalyticsReportReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        string $reportId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($reportId, $data, $version);
        $this->setCacheTtl(3600); // 1 hour for reports
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'analytics_report',
            'report:' . $this->id,
            'report_data',
        ]);
    }

    // Report Information
    /**
     * @return array<string, mixed>
     */
    public function getReportInfo(): array
    {
        return $this->get('report_info', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        return $this->get('data', []);
    }

    public function getReportId(): string
    {
        return (string) $this->id;
    }

    public function getReportName(): string
    {
        $info = $this->getReportInfo();

        return $info['name'] ?? 'Untitled Report';
    }

    public function getReportType(): string
    {
        $info = $this->getReportInfo();

        return $info['type'] ?? 'unknown';
    }

    public function getReportFormat(): string
    {
        $info = $this->getReportInfo();

        return $info['format'] ?? 'json';
    }

    public function getGeneratedAt(): string
    {
        $info = $this->getReportInfo();

        return $info['generated_at'] ?? parent::getGeneratedAt();
    }

    /**
     * @return array<string, mixed>
     */
    public function getTimeRange(): array
    {
        $info = $this->getReportInfo();

        return $info['time_range'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFiltersApplied(): array
    {
        $info = $this->getReportInfo();

        return $info['filters_applied'] ?? [];
    }

    public function getGenerationTime(): float
    {
        $info = $this->getReportInfo();

        return $info['generation_time_ms'] ?? 0.0;
    }

    public function includesComparisons(): bool
    {
        $info = $this->getReportInfo();

        return $info['include_comparisons'] ?? false;
    }

    public function includesVisualizationData(): bool
    {
        $info = $this->getReportInfo();

        return $info['include_visualization_data'] ?? false;
    }

    // Report Data Access
    /**
     * @return array<string, mixed>
     */
    public function getSummaryData(): array
    {
        $data = $this->getReportData();

        return $data['summary'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPerformanceData(): array
    {
        $data = $this->getReportData();

        return $data['performance'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTrendsData(): array
    {
        $data = $this->getReportData();

        return $data['trends'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTopDonorsData(): array
    {
        $data = $this->getReportData();

        return $data['top_donors'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatisticsData(): array
    {
        $data = $this->getReportData();

        return $data['statistics'] ?? [];
    }

    // Report Type Specific Methods
    public function isDonationAnalyticsReport(): bool
    {
        return $this->getReportType() === 'donation_analytics';
    }

    public function isCampaignPerformanceReport(): bool
    {
        return $this->getReportType() === 'campaign_performance';
    }

    public function isUserActivityReport(): bool
    {
        return $this->getReportType() === 'user_activity';
    }

    public function isOrganizationStatsReport(): bool
    {
        return $this->getReportType() === 'organization_stats';
    }

    public function isDonorTrendsReport(): bool
    {
        return $this->getReportType() === 'donor_trends';
    }

    // Data Quality and Completeness
    public function hasData(): bool
    {
        return $this->getReportData() !== [];
    }

    public function hasSummaryData(): bool
    {
        return $this->getSummaryData() !== [];
    }

    public function hasTrendsData(): bool
    {
        return $this->getTrendsData() !== [];
    }

    public function hasPerformanceData(): bool
    {
        return $this->getPerformanceData() !== [];
    }

    public function getDataCompleteness(): float
    {
        $expectedSections = ['summary', 'performance', 'trends', 'statistics'];
        $existingSections = 0;
        $data = $this->getReportData();

        foreach ($expectedSections as $section) {
            if (! empty($data[$section])) {
                $existingSections++;
            }
        }

        return ($existingSections / count($expectedSections)) * 100;
    }

    // Time Range Analysis
    public function getTimeRangeLabel(): string
    {
        $timeRange = $this->getTimeRange();

        return $timeRange['label'] ?? 'Unknown Period';
    }

    public function getStartDate(): ?string
    {
        $timeRange = $this->getTimeRange();

        return $timeRange['start'] ?? null;
    }

    public function getEndDate(): ?string
    {
        $timeRange = $this->getTimeRange();

        return $timeRange['end'] ?? null;
    }

    public function getReportPeriodDays(): int
    {
        $start = $this->getStartDate();
        $end = $this->getEndDate();

        if (! $start || ! $end) {
            return 0;
        }

        $startDate = new DateTime($start);
        $endDate = new DateTime($end);

        return $startDate->diff($endDate)->days + 1;
    }

    // Filtering Information
    public function hasFilters(): bool
    {
        return $this->getFiltersApplied() !== [];
    }

    public function getFilterCount(): int
    {
        return count($this->getFiltersApplied());
    }

    /**
     * @return array<string>
     */
    public function getFilterKeys(): array
    {
        return array_keys($this->getFiltersApplied());
    }

    public function getFilterValue(string $key): mixed
    {
        $filters = $this->getFiltersApplied();

        return $filters[$key] ?? null;
    }

    // Performance Metrics
    public function isQuickReport(): bool
    {
        return $this->getGenerationTime() < 1000; // Less than 1 second
    }

    public function isSlowReport(): bool
    {
        return $this->getGenerationTime() > 10000; // More than 10 seconds
    }

    public function getGenerationTimeCategory(): string
    {
        $time = $this->getGenerationTime();
        if ($time < 1000) {
            return 'fast';
        }
        if ($time < 5000) {
            return 'moderate';
        }

        if ($time < 10000) {
            return 'slow';
        }

        return 'very_slow';
    }

    // Export and Sharing
    public function canExport(): bool
    {
        return $this->hasData() && in_array($this->getReportFormat(), ['json', 'csv', 'excel']);
    }

    /**
     * @return array<string>
     */
    public function getExportFormats(): array
    {
        $formats = ['json'];

        if ($this->hasStructuredData()) {
            $formats[] = 'csv';
            $formats[] = 'excel';
        }

        if ($this->includesVisualizationData()) {
            $formats[] = 'pdf';
        }

        return $formats;
    }

    private function hasStructuredData(): bool
    {
        // Check if data has tabular structure suitable for CSV/Excel
        $data = $this->getReportData();

        foreach ($data as $section) {
            if (is_array($section) && $section !== []) {
                $first = reset($section);
                if (is_array($first) && isset($first[0]) && is_array($first[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    // Summary Methods
    /**
     * Get a comprehensive summary of the report.
     */
    /**
     * @return array<string, mixed>
     */
    public function getReportSummary(): array
    {
        return [
            'report' => [
                'id' => $this->getReportId(),
                'name' => $this->getReportName(),
                'type' => $this->getReportType(),
                'format' => $this->getReportFormat(),
                'generated_at' => $this->getGeneratedAt(),
                'generation_time_ms' => $this->getGenerationTime(),
                'generation_category' => $this->getGenerationTimeCategory(),
            ],
            'time_range' => [
                'label' => $this->getTimeRangeLabel(),
                'start_date' => $this->getStartDate(),
                'end_date' => $this->getEndDate(),
                'period_days' => $this->getReportPeriodDays(),
            ],
            'filters' => [
                'has_filters' => $this->hasFilters(),
                'filter_count' => $this->getFilterCount(),
                'filter_keys' => $this->getFilterKeys(),
            ],
            'data_quality' => [
                'has_data' => $this->hasData(),
                'data_completeness' => $this->getDataCompleteness(),
                'has_summary' => $this->hasSummaryData(),
                'has_trends' => $this->hasTrendsData(),
                'has_performance' => $this->hasPerformanceData(),
            ],
            'features' => [
                'includes_comparisons' => $this->includesComparisons(),
                'includes_visualization_data' => $this->includesVisualizationData(),
                'can_export' => $this->canExport(),
                'export_formats' => $this->getExportFormats(),
            ],
        ];
    }

    /**
     * Get key metrics from the report.
     */
    /**
     * @return array<string, mixed>
     */
    public function getReportKPIs(): array
    {
        $kpis = [];

        // Extract KPIs based on report type
        switch ($this->getReportType()) {
            case 'donation_analytics':
                $summary = $this->getSummaryData();
                $kpis = [
                    'total_donations' => $summary['total_donations']['value'] ?? 0,
                    'total_amount' => $summary['total_amount']['value'] ?? 0,
                    'average_donation' => $summary['average_amount']['value'] ?? 0,
                    'unique_donors' => $summary['unique_donors']['value'] ?? 0,
                ];
                break;

            case 'campaign_performance':
                $performance = $this->getPerformanceData();
                $kpis = [
                    'total_campaigns' => $performance['total_campaigns']['value'] ?? 0,
                    'active_campaigns' => $performance['active_campaigns']['value'] ?? 0,
                    'success_rate' => $performance['success_rate']['value'] ?? 0,
                    'total_raised' => $performance['total_raised']['value'] ?? 0,
                ];
                break;

            default:
                // Generic KPIs from summary data
                $summary = $this->getSummaryData();
                foreach ($summary as $key => $value) {
                    if (is_array($value) && isset($value['value'])) {
                        $kpis[$key] = $value['value'];
                    }
                }
        }

        return $kpis;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAnalyticsArray(): array
    {
        return [
            'analytics_report' => [
                'report_id' => $this->getReportId(),
                'summary' => $this->getReportSummary(),
                'kpis' => $this->getReportKPIs(),
                'report_info' => $this->getReportInfo(),
                'report_data' => $this->getReportData(),
            ],
            'metadata' => [
                'version' => $this->getVersion(),
                'cache_ttl' => $this->getCacheTtl(),
                'cache_tags' => $this->getCacheTags(),
            ],
        ];
    }
}
