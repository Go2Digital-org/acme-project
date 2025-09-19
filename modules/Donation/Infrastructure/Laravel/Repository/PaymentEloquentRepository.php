<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Donation\Domain\Model\Payment;
use Modules\Donation\Domain\Repository\PaymentRepositoryInterface;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use stdClass;

/**
 * Payment Eloquent Repository Implementation.
 *
 * Provides comprehensive payment data access with domain-specific query methods.
 */
class PaymentEloquentRepository implements PaymentRepositoryInterface
{
    public function __construct(
        private readonly Payment $model
    ) {}

    /**
     * Find payment by ID.
     */
    public function findById(int $id): ?Payment
    {
        return $this->model->find($id);
    }

    /**
     * Find payment by intent ID.
     */
    public function findByIntentId(string $intentId): ?Payment
    {
        return $this->model->where('intent_id', $intentId)->first();
    }

    /**
     * Find payment by transaction ID.
     */
    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->model->where('transaction_id', $transactionId)->first();
    }

    /**
     * Find payments for a donation.
     *
     * @return Collection<int, Payment>
     */
    public function findByDonationId(int $donationId): Collection
    {
        return $this->model
            ->where('donation_id', $donationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find payments by status.
     *
     * @return Collection<int, Payment>
     */
    public function findByStatus(PaymentStatus $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find payments by gateway.
     *
     * @return Collection<int, Payment>
     */
    public function findByGateway(string $gatewayName): Collection
    {
        return $this->model
            ->where('gateway_name', $gatewayName)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find expired payments that need cleanup.
     *
     * @return Collection<int, Payment>
     */
    public function findExpiredPayments(): Collection
    {
        return $this->model
            ->where('status', PaymentStatus::PENDING)
            ->where('expires_at', '<', now())
            ->get();
    }

    /**
     * Find stuck/stale payments for recovery.
     *
     * @return Collection<int, Payment>
     */
    public function findStalePayments(int $minutesThreshold = 30): Collection
    {
        return $this->model
            ->where('status', PaymentStatus::PENDING)
            ->where('created_at', '<', now()->subMinutes($minutesThreshold))
            ->whereNull('expires_at')
            ->get();
    }

    /**
     * Find payments requiring action.
     *
     * @return Collection<int, Payment>
     */
    public function findRequiringAction(): Collection
    {
        return $this->model
            ->where('status', PaymentStatus::REQUIRES_ACTION)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get payments with pagination.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('donation')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get payments for donation with pagination.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateByDonation(int $donationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('donation_id', $donationId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Save payment.
     */
    public function save(Payment $payment): bool
    {
        return $payment->save();
    }

    /**
     * Delete payment.
     */
    public function delete(Payment $payment): bool
    {
        return (bool) $payment->delete();
    }

    /**
     * Get payment statistics for dashboard.
     */
    /**
     * @return array<string, mixed>
     */
    public function getStatistics(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $to);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_payments,
            COUNT(CASE WHEN status = ? THEN 1 END) as successful_payments,
            COUNT(CASE WHEN status = ? THEN 1 END) as failed_payments,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending_payments,
            SUM(CASE WHEN status = ? THEN amount ELSE 0 END) as successful_amount,
            AVG(CASE WHEN status = ? AND captured_at IS NOT NULL THEN 
                TIMESTAMPDIFF(SECOND, created_at, captured_at) ELSE NULL END) as avg_processing_time
        ', [
            PaymentStatus::COMPLETED->value,
            PaymentStatus::FAILED->value,
            PaymentStatus::PENDING->value,
            PaymentStatus::COMPLETED->value,
            PaymentStatus::COMPLETED->value,
        ])
            ->first();

        /** @var stdClass|null $stats */
        if (! $stats) {
            return [
                'total_payments' => 0,
                'successful_payments' => 0,
                'failed_payments' => 0,
                'pending_payments' => 0,
                'success_rate' => 0.0,
                'successful_amount' => 0.0,
                'avg_processing_time' => null,
            ];
        }

        $total = (int) (is_object($stats) && property_exists($stats, 'total_payments') ? $stats->total_payments : 0);
        $successful = (int) (is_object($stats) && property_exists($stats, 'successful_payments') ? $stats->successful_payments : 0);

        return [
            'total_payments' => $total,
            'successful_payments' => $successful,
            'failed_payments' => (int) (is_object($stats) && property_exists($stats, 'failed_payments') ? $stats->failed_payments : 0),
            'pending_payments' => (int) (is_object($stats) && property_exists($stats, 'pending_payments') ? $stats->pending_payments : 0),
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0.0,
            'successful_amount' => (float) (is_object($stats) && property_exists($stats, 'successful_amount') ? $stats->successful_amount : 0),
            'avg_processing_time' => is_object($stats) && property_exists($stats, 'avg_processing_time') && $stats->avg_processing_time ? (float) $stats->avg_processing_time : null,
        ];
    }

    /**
     * Get successful payments total amount.
     */
    public function getTotalSuccessfulAmount(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): float
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $to);
        }

        return (float) $query
            ->where('status', PaymentStatus::COMPLETED)
            ->sum('amount');
    }

    /**
     * Get payment success rate.
     */
    public function getSuccessRate(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): float
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $to);
        }

        $total = $query->count();

        if ($total === 0) {
            return 0.0;
        }

        $successful = (clone $query)->where('status', PaymentStatus::COMPLETED)->count();

        return ($successful / $total) * 100;
    }

    /**
     * Get average processing time in seconds.
     */
    public function getAverageProcessingTime(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): ?float
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $to);
        }

        $result = $query
            ->where('status', PaymentStatus::COMPLETED)
            ->whereNotNull('captured_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, captured_at)) as avg_time')
            ->first();

        /** @var mixed $avgTime */
        $avgTime = is_object($result) && property_exists($result, 'avg_time') ? $result->avg_time : null;

        return $avgTime ? (float) $avgTime : null;
    }

    /**
     * Get payment volume by gateway.
     */
    /**
     * @return array<string, mixed>
     */
    public function getVolumeByGateway(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $to);
        }

        $results = $query->selectRaw('gateway_name, COUNT(*) as payment_count')
            ->groupBy('gateway_name')
            ->get();

        $volume = [];
        foreach ($results as $result) {
            if (is_object($result) && property_exists($result, 'gateway_name') && property_exists($result, 'payment_count')) {
                $volume[$result->gateway_name] = (int) $result->payment_count;
            }
        }

        return $volume;
    }

    /**
     * Get failure reasons breakdown.
     */
    /**
     * @return array<string, mixed>
     */
    public function getFailureReasons(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('created_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('created_at', '<=', $to);
        }

        $results = $query->selectRaw('
            COALESCE(failure_code, "UNKNOWN") as failure_reason,
            COUNT(*) as failure_count
        ')
            ->where('status', PaymentStatus::FAILED)
            ->groupBy('failure_code')
            ->orderBy('failure_count', 'desc')
            ->get();

        $failures = [];
        foreach ($results as $result) {
            if (is_object($result) && property_exists($result, 'failure_reason') && property_exists($result, 'failure_count')) {
                $failures[$result->failure_reason] = (int) $result->failure_count;
            }
        }

        return $failures;
    }

    /**
     * Find payments needing reconciliation.
     *
     * @return Collection<int, Payment>
     */
    public function findNeedingReconciliation(): Collection
    {
        return $this->model
            ->where('status', PaymentStatus::COMPLETED)
            ->whereNull('transaction_id')
            ->orWhere(function ($query): void {
                $query->where('status', PaymentStatus::PENDING)
                    ->where('created_at', '<', now()->subHours(24));
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Bulk update payment statuses.
     *
     * @param  int[]  $paymentIds
     */
    public function bulkUpdateStatus(array $paymentIds, PaymentStatus $status): int
    {
        if ($paymentIds === []) {
            return 0;
        }

        $updateData = [
            'status' => $status->value,
            'updated_at' => now(),
        ];

        // Add timestamp fields based on status
        switch ($status) {
            case PaymentStatus::COMPLETED:
                $updateData['captured_at'] = now();
                break;
            case PaymentStatus::FAILED:
                $updateData['failed_at'] = now();
                break;
            case PaymentStatus::CANCELLED:
                $updateData['cancelled_at'] = now();
                break;
        }

        return $this->model->whereIn('id', $paymentIds)->update($updateData);
    }

    /**
     * Find payments with metadata key.
     *
     * @return Collection<int, Payment>
     */
    public function findWithMetadataKey(string $key, mixed $value = null): Collection
    {
        $query = $this->model->newQuery();

        if ($value === null) {
            $query->whereJsonContains('metadata', [$key => true])
                ->orWhereNotNull(DB::raw("JSON_EXTRACT(metadata, '$.\"{$key}\"')"));

            return $query->get();
        }

        $query->whereJsonContains('metadata', [$key => $value]);

        return $query->get();
    }

    /**
     * Get payment retry candidates.
     *
     * @return Collection<int, Payment>
     */
    public function getRetryCandidates(int $maxRetries = 3): Collection
    {
        return $this->model
            ->where('status', PaymentStatus::FAILED)
            ->where(function ($query): void {
                $query->whereIn('failure_code', [
                    'network_error',
                    'gateway_timeout',
                    'temporary_decline',
                    'try_again_later',
                ])
                    ->orWhere('failure_message', 'like', '%temporary%')
                    ->orWhere('failure_message', 'like', '%retry%');
            })
            ->where(function ($query) use ($maxRetries): void {
                $query->whereJsonLength('metadata->retry_count', '<', $maxRetries)
                    ->orWhereNull('metadata->retry_count');
            })
            ->where('created_at', '>', now()->subDays(7)) // Only recent failures
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
