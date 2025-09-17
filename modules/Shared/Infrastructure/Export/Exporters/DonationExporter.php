<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Export\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Donation\Domain\Model\Donation;

/**
 * @implements WithMapping<Donation>
 */
final readonly class DonationExporter implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private array $filters = []) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(array $filters = []): self
    {
        return new self($filters);
    }

    /**
     * @return Builder<Donation>
     */
    public function query(): Builder
    {
        $query = Donation::query()
            ->with(['campaign', 'campaign.organization', 'user']);

        // Apply filters if provided
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['campaign_id'])) {
            $query->where('campaign_id', $this->filters['campaign_id']);
        }

        if (! empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('donated_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('donated_at', '<=', $this->filters['date_to']);
        }

        if (! empty($this->filters['min_amount'])) {
            $query->where('amount', '>=', $this->filters['min_amount']);
        }

        if (! empty($this->filters['max_amount'])) {
            $query->where('amount', '<=', $this->filters['max_amount']);
        }

        if (! empty($this->filters['currency'])) {
            $query->where('currency', $this->filters['currency']);
        }

        if (! empty($this->filters['payment_method'])) {
            $query->where('payment_method', $this->filters['payment_method']);
        }

        if (! empty($this->filters['payment_gateway'])) {
            $query->where('payment_gateway', $this->filters['payment_gateway']);
        }

        if (isset($this->filters['is_anonymous'])) {
            $query->where('is_anonymous', (bool) $this->filters['is_anonymous']);
        }

        if (isset($this->filters['recurring'])) {
            $query->where('recurring', (bool) $this->filters['recurring']);
        }

        // Default order by donation date descending
        return $query->orderBy('donated_at', 'desc');
    }

    /**
     * @param  Donation  $donation
     * @return array<int, mixed>
     */
    public function map($donation): array
    {
        return [
            $donation->id,
            $donation->campaign->title ?? 'Unknown Campaign',
            $donation->campaign?->organization ? $donation->campaign->organization->getName() : 'Unknown Organization',
            $donation->is_anonymous ? 'Anonymous' : ($donation->user->name ?? 'Unknown Donor'),
            $donation->is_anonymous ? 'Hidden' : ($donation->user->email ?? ''),
            $donation->user_id ?? '',
            number_format($donation->amount, 2),
            strtoupper($donation->currency ?? 'USD'),
            $donation->payment_method?->getLabel() ?? 'Unknown',
            ucfirst($donation->payment_gateway ?? 'Unknown'),
            $donation->transaction_id ?? 'Pending',
            $donation->status->getLabel() ?? 'Unknown',
            $donation->is_anonymous ? 'Yes' : 'No',
            $donation->recurring ? 'Yes' : 'No',
            $donation->recurring_frequency ?? '',
            $donation->corporate_match_amount !== null ? number_format($donation->corporate_match_amount, 2) : '0.00',
            number_format($donation->amount + ($donation->corporate_match_amount ?? 0), 2),
            $donation->isEligibleForTaxReceipt() ? 'Yes' : 'No',
            $donation->notes ? substr((string) $donation->notes, 0, 200) . '...' : '',
            $donation->donated_at->format('Y-m-d H:i:s') ?? '',
            $donation->processed_at?->format('Y-m-d H:i:s') ?? '',
            $donation->completed_at?->format('Y-m-d H:i:s') ?? '',
            $donation->failed_at?->format('Y-m-d H:i:s') ?? '',
            $donation->cancelled_at?->format('Y-m-d H:i:s') ?? '',
            $donation->refunded_at?->format('Y-m-d H:i:s') ?? '',
            $donation->failure_reason ?? '',
            $donation->refund_reason ?? '',
            $donation->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Campaign Title',
            'Organization',
            'Donor Name',
            'Donor Email',
            'Employee ID',
            'Amount',
            'Currency',
            'Payment Method',
            'Payment Gateway',
            'Transaction ID',
            'Status',
            'Is Anonymous',
            'Is Recurring',
            'Recurring Frequency',
            'Corporate Match Amount',
            'Total Impact',
            'Tax Deductible',
            'Message',
            'Donation Date',
            'Processed Date',
            'Completed Date',
            'Failed Date',
            'Cancelled Date',
            'Refunded Date',
            'Failure Reason',
            'Refund Reason',
            'Created At',
        ];
    }
}
