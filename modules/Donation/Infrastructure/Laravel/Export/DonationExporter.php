<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Shared\Domain\Export\DonationExportRepositoryInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final readonly class DonationExporter implements WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private array $filters = [],
        private ?DonationExportRepositoryInterface $repository = null,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(array $filters = []): self
    {
        $repository = app(DonationExportRepositoryInterface::class);

        return new self($filters, $repository);
    }

    /** @return array<string, DonationSheet|DonationSummarySheet|DonationsByCampaignSheet|DonationsByEmployeeSheet> */
    public function sheets(): array
    {
        $repository = $this->repository ?? app(DonationExportRepositoryInterface::class);

        return [
            'donations' => new DonationSheet($this->filters, $repository),
            'summary' => new DonationSummarySheet($this->filters, $repository),
            'by_campaign' => new DonationsByCampaignSheet($this->filters, $repository),
            'by_employee' => new DonationsByEmployeeSheet($this->filters, $repository),
        ];
    }
}

/**
 * @implements WithMapping<object>
 */
class DonationSheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters = [],
        private readonly ?DonationExportRepositoryInterface $repository = null,
    ) {}

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        $repository = $this->repository ?? app(DonationExportRepositoryInterface::class);

        return $repository->getDonationsWithRelations($this->filters);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Transaction ID',
            'Donation Date',
            'Donor Name',
            'Donor Email',
            'Employee ID',
            'Campaign Title',
            'Organization',
            'Amount',
            'Currency',
            'Payment Method',
            'Status',
            'Anonymous',
            'Recurring',
            'Recurring Frequency',
            'Tax Deductible',
            'Processing Fee',
            'Net Amount',
            'Matching Multiplier',
            'Matching Amount',
            'Message',
            'Processed At',
            'Failed At',
            'Refunded At',
            'Failure Reason',
            'Refund Reason',
            'Created At',
        ];
    }

    /**
     * @param  mixed  $donation
     * @return array<int, mixed>
     */
    public function map($donation): array
    {
        $matchingAmount = $donation->amount * (($donation->matching_multiplier ?? 1) - 1);
        $netAmount = $donation->amount - ($donation->processing_fee ?? 0);

        return [
            $donation->id,
            $donation->transaction_id ?? 'Pending',
            $donation->donated_at?->format('Y-m-d H:i:s'),
            $donation->is_anonymous ? 'Anonymous' : ($donation->user->name ?? 'Unknown'),
            $donation->is_anonymous ? null : ($donation->user->email ?? 'Unknown'),
            $donation->user->id ?? 'Unknown',
            $donation->campaign->title ?? 'Unknown Campaign',
            $donation->campaign->organization ? $donation->campaign->organization->getName() : 'Unknown Organization',
            $donation->amount,
            $donation->currency ?? 'USD',
            $donation->payment_method?->getLabel() ?? 'Unknown',
            $donation->status?->getLabel() ?? 'Unknown',
            $donation->is_anonymous ? 'Yes' : 'No',
            $donation->is_recurring ? 'Yes' : 'No',
            $donation->recurring_frequency ?? '',
            $donation->tax_deductible ? 'Yes' : 'No',
            $donation->processing_fee ?? 0,
            $netAmount,
            $donation->matching_multiplier ?? 1,
            $matchingAmount,
            $donation->message ?? '',
            $donation->processed_at?->format('Y-m-d H:i:s') ?? '',
            $donation->failed_at?->format('Y-m-d H:i:s') ?? '',
            $donation->refunded_at?->format('Y-m-d H:i:s') ?? '',
            $donation->failure_reason ?? '',
            $donation->refund_reason ?? '',
            $donation->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:Z' => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function title(): string
    {
        return 'All Donations';
    }
}

/**
 * @implements WithMapping<object>
 */
class DonationSummarySheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters = [],
        private readonly ?DonationExportRepositoryInterface $repository = null,
    ) {}

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        $repository = $this->repository ?? app(DonationExportRepositoryInterface::class);

        return $repository->getDonationSummaryQuery($this->filters);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Status',
            'Count',
            'Total Amount',
            'Average Amount',
            'Minimum Amount',
            'Maximum Amount',
            'Total Processing Fees',
        ];
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->status?->getLabel() ?? 'Unknown',
            $row->donation_count,
            '$' . number_format($row->total_amount, 2),
            '$' . number_format($row->average_amount, 2),
            '$' . number_format($row->min_amount, 2),
            '$' . number_format($row->max_amount, 2),
            '$' . number_format($row->total_fees, 2),
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'C:G' => ['alignment' => ['horizontal' => 'right']],
        ];
    }

    public function title(): string
    {
        return 'Donation Summary';
    }
}

/**
 * @implements WithMapping<object>
 */
class DonationsByCampaignSheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters = [],
        private readonly ?DonationExportRepositoryInterface $repository = null,
    ) {}

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        $repository = $this->repository ?? app(DonationExportRepositoryInterface::class);

        return $repository->getDonationsByCampaignQuery($this->filters);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Campaign',
            'Organization',
            'Donations Count',
            'Total Raised',
            'Average Donation',
            'Goal Amount',
            'Progress %',
            'Unique Donors',
        ];
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->campaign_title,
            $row->organization_name,
            $row->donation_count,
            '$' . number_format($row->total_raised, 2),
            '$' . number_format($row->average_donation, 2),
            '$' . number_format($row->goal_amount, 2),
            number_format($row->progress_percentage, 1) . '%',
            $row->unique_donors,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'D:H' => ['alignment' => ['horizontal' => 'right']],
        ];
    }

    public function title(): string
    {
        return 'Donations by Campaign';
    }
}

/**
 * @implements WithMapping<object>
 */
class DonationsByEmployeeSheet implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters = [],
        private readonly ?DonationExportRepositoryInterface $repository = null,
    ) {}

    /**
     * @return Builder<Model>
     */
    public function query(): Builder
    {
        $repository = $this->repository ?? app(DonationExportRepositoryInterface::class);

        return $repository->getDonationsByEmployeeQuery($this->filters);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee ID',
            'Total Donations',
            'Total Amount',
            'Average Donation',
            'First Donation',
            'Latest Donation',
            'Recurring Donations',
        ];
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->name,
            $row->user_id,
            $row->donation_count,
            '$' . number_format($row->total_donated, 2),
            '$' . number_format($row->average_donation, 2),
            $row->first_donation ? date('Y-m-d', strtotime((string) $row->first_donation) ?: 0) : '',
            $row->latest_donation ? date('Y-m-d', strtotime((string) $row->latest_donation) ?: 0) : '',
            $row->recurring_donations,
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'E:F' => ['alignment' => ['horizontal' => 'right']],
        ];
    }

    public function title(): string
    {
        return 'Donations by Employee';
    }
}
