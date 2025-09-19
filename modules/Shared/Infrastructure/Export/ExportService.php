<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Export;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Donation\Infrastructure\Laravel\Export\DonationExporter;
use Modules\Organization\Infrastructure\Laravel\Export\OrganizationExporter;
use Modules\Shared\Infrastructure\Export\Exporters\CSRImpactExporter;
use Modules\Shared\Infrastructure\Export\Exporters\EmployeeExporter;
use RuntimeException;

final readonly class ExportService
{
    public function __construct(
        private DonationExporter $donationExporter,
        private OrganizationExporter $organizationExporter,
        private EmployeeExporter $employeeExporter,
        private CSRImpactExporter $csrImpactExporter,
    ) {}

    /**
     * Export donations data.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportDonations(array $filters = [], string $format = 'xlsx'): string
    {
        $filename = $this->generateFilename('donations', $format);

        $export = $this->donationExporter->create($filters);

        return $this->processExport($export, $filename);
    }

    /**
     * Export organizations data.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportOrganizations(array $filters = [], string $format = 'xlsx'): string
    {
        $filename = $this->generateFilename('organizations', $format);

        $export = $this->organizationExporter->create($filters);

        return $this->processExport($export, $filename);
    }

    /**
     * Export employees data.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportEmployees(array $filters = [], string $format = 'xlsx'): string
    {
        $filename = $this->generateFilename('employees', $format);

        $export = $this->employeeExporter->create($filters);

        return $this->processExport($export, $filename);
    }

    /**
     * Export comprehensive CSR impact report.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCSRImpact(array $filters = [], string $format = 'xlsx'): string
    {
        $filename = $this->generateFilename('csr-impact-report', $format);

        $export = $this->csrImpactExporter->create($filters);

        return $this->processExport($export, $filename);
    }

    /**
     * Export tax receipts for donations.
     *
     * @param  Collection<int, mixed>  $donations
     */
    public function exportTaxReceipts(Collection $donations, string $format = 'pdf'): string
    {
        $filename = $this->generateFilename('tax-receipts', $format);

        if ($format === 'pdf') {
            return $this->generateTaxReceiptsPDF($donations, $filename);
        }

        return $this->generateTaxReceiptsExcel($donations, $filename);
    }

    /**
     * Schedule automated report generation.
     */
    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $recipients
     */
    public function scheduleReport(string $type, array $filters, string $format, string $frequency, array $recipients): void
    {
        $schedule = [
            'type' => $type,
            'filters' => $filters,
            'format' => $format,
            'frequency' => $frequency, // daily, weekly, monthly, quarterly
            'recipients' => $recipients,
            'created_at' => now(),
            'next_run' => $this->calculateNextRun($frequency),
        ];

        // Store in database or cache for scheduler to pick up
        $jsonData = json_encode($schedule);

        if ($jsonData === false) {
            throw new RuntimeException('Failed to encode schedule data to JSON');
        }

        Storage::disk('local')->put(
            "scheduled_reports/{$type}_" . md5($jsonData) . '.json',
            $jsonData,
        );
    }

    /**
     * Generate comprehensive executive dashboard PDF.
     */
    /**
     * @param  array<string, mixed>  $filters
     */
    public function generateExecutiveDashboard(array $filters = []): string
    {
        $filename = $this->generateFilename('executive-dashboard', 'pdf');

        $data = [
            'generated_at' => now(),
            'period' => $filters['period'] ?? 'current_month',
            'metrics' => $this->gatherExecutiveMetrics(),
            'charts' => $this->generateDashboardCharts(),
            'summary' => $this->generateExecutiveSummary(),
        ];

        return $this->generatePDF('reports.executive-dashboard', $data, $filename);
    }

    /**
     * Generate compliance audit report.
     */
    public function generateComplianceReport(): string
    {
        $filename = $this->generateFilename('compliance-audit', 'pdf');
        $data = [
            'generated_at' => now(),
            'organizations' => $this->gatherComplianceData(),
            'summary' => $this->generateComplianceSummary(),
            'issues' => $this->identifyComplianceIssues(),
            'recommendations' => $this->generateComplianceRecommendations(),
        ];

        return $this->generatePDF('reports.compliance-audit', $data, $filename);
    }

    /**
     * @param  FromCollection|FromQuery|WithMultipleSheets  $export
     */
    private function processExport($export, string $filename): string
    {
        $path = "exports/{$filename}";

        Excel::store($export, $path, 'local');

        return Storage::disk('local')->path($path);
    }

    private function generateFilename(string $type, string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "{$type}_{$timestamp}.{$format}";
    }

    private function calculateNextRun(string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            default => now()->addDay(),
        };
    }

    /**
     * @param  Collection<int, mixed>  $donations
     */
    private function generateTaxReceiptsPDF(Collection $donations, string $filename): string
    {
        $data = [
            'donations' => $donations->where('tax_deductible', true),
            'generated_at' => now(),
            'tax_year' => now()->year,
        ];

        return $this->generatePDF('reports.tax-receipts', $data, $filename);
    }

    /**
     * @param  Collection<int, mixed>  $donations
     */
    private function generateTaxReceiptsExcel(Collection $donations, string $filename): string
    {
        $export = new class($donations) implements FromCollection, WithHeadings
        {
            /**
             * @param  Collection<int, mixed>  $donations
             */
            public function __construct(private readonly Collection $donations) {}

            /**
             * @return Collection<int, mixed>
             */
            public function collection(): Collection
            {
                return $this->donations->where('tax_deductible', true)->map(fn ($donation): array => [
                    'receipt_id' => 'TR-' . $donation->id . '-' . now()->year,
                    'donor_name' => $donation->is_anonymous ? 'Anonymous' : $donation->user->name,
                    'donor_email' => $donation->is_anonymous ? null : $donation->user->email,
                    'donation_date' => $donation->donated_at->format('Y-m-d'),
                    'amount' => $donation->amount,
                    'campaign' => $donation->campaign->title,
                    'organization' => $donation->campaign->organization->getName(),
                    'organization_tax_id' => $donation->campaign->organization->tax_id,
                    'deductible_amount' => $donation->amount, // Could be different if there were benefits
                ]);
            }

            /**
             * @return array<int, string>
             */
            public function headings(): array
            {
                return [
                    'Receipt ID',
                    'Donor Name',
                    'Donor Email',
                    'Donation Date',
                    'Amount',
                    'Campaign',
                    'Organization',
                    'Organization Tax ID',
                    'Deductible Amount',
                ];
            }
        };

        return $this->processExport($export, $filename);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function generatePDF(string $view, array $data, string $filename): string
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView($view, $data);
        $pdf->setPaper('a4', 'portrait');

        $path = "exports/{$filename}";
        Storage::disk('local')->put($path, $pdf->output());

        return Storage::disk('local')->path($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherExecutiveMetrics(): array
    {
        // Implementation would gather comprehensive metrics
        return [
            'total_raised' => 0,
            'active_campaigns' => 0,
            'employee_participation' => 0,
            'organizations_supported' => 0,
            // ... more metrics
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateDashboardCharts(): array
    {
        // Generate chart data for PDF inclusion
        return [
            'donation_trends' => [],
            'campaign_performance' => [],
            'employee_engagement' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateExecutiveSummary(): array
    {
        return [
            'highlights' => [],
            'achievements' => [],
            'areas_for_improvement' => [],
            'next_quarter_goals' => [],
        ];
    }

    /**
     * @return Collection<int, mixed>
     */
    private function gatherComplianceData(): Collection
    {
        // Gather organization compliance data
        return collect();
    }

    /**
     * @return array<string, int>
     */
    private function generateComplianceSummary(): array
    {
        return [
            'total_organizations' => 0,
            'verified_organizations' => 0,
            'compliance_rate' => 0,
            'pending_verifications' => 0,
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function identifyComplianceIssues(): array
    {
        return [
            'missing_tax_ids' => [],
            'expired_certifications' => [],
            'unverified_organizations' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateComplianceRecommendations(): array
    {
        return [
            'immediate_actions' => [],
            'process_improvements' => [],
            'policy_updates' => [],
        ];
    }
}
