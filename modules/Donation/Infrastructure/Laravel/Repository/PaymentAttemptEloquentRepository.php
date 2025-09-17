<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Repository;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Donation\Domain\Model\PaymentAttempt;
use Modules\Donation\Domain\Repository\PaymentAttemptRepositoryInterface;
use Modules\Donation\Domain\ValueObject\PaymentStatus;

/**
 * Payment Attempt Eloquent Repository Implementation.
 *
 * Provides comprehensive audit trail data access for payment processing attempts.
 */
class PaymentAttemptEloquentRepository implements PaymentAttemptRepositoryInterface
{
    public function __construct(
        private readonly PaymentAttempt $model
    ) {}

    /**
     * Find payment attempt by ID.
     */
    public function findById(int $id): ?PaymentAttempt
    {
        return $this->model->find($id);
    }

    /**
     * Find attempts for a specific payment.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findByPaymentId(int $paymentId): Collection
    {
        return $this->model
            ->where('payment_id', $paymentId)
            ->orderBy('attempt_number', 'asc')
            ->get();
    }

    /**
     * Find attempts by gateway.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findByGateway(string $gatewayName): Collection
    {
        return $this->model
            ->where('gateway_name', $gatewayName)
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Find attempts by status.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findByStatus(PaymentStatus $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Find failed attempts that are retryable.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findRetryableFailures(): Collection
    {
        $permanentFailureCodes = [
            'card_declined',
            'insufficient_funds',
            'invalid_card',
            'expired_card',
            'authentication_required',
        ];

        return $this->model
            ->where('status', PaymentStatus::FAILED)
            ->where(function ($query) use ($permanentFailureCodes) {
                $query->whereNull('error_code')
                    ->orWhereNotIn('error_code', $permanentFailureCodes);
            })
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Find attempts by gateway request ID.
     */
    public function findByGatewayRequestId(string $requestId): ?PaymentAttempt
    {
        return $this->model
            ->where('gateway_request_id', $requestId)
            ->first();
    }

    /**
     * Get attempts with pagination.
     *
     * @return LengthAwarePaginator<int, PaymentAttempt>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('payment')
            ->orderBy('attempted_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get attempts for payment with pagination.
     *
     * @return LengthAwarePaginator<int, PaymentAttempt>
     */
    public function paginateByPayment(int $paymentId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('payment_id', $paymentId)
            ->orderBy('attempt_number', 'asc')
            ->paginate($perPage);
    }

    /**
     * Save payment attempt.
     */
    public function save(PaymentAttempt $attempt): bool
    {
        return $attempt->save();
    }

    /**
     * Delete payment attempt.
     */
    public function delete(PaymentAttempt $attempt): bool
    {
        return (bool) $attempt->delete();
    }

    /**
     * Get audit statistics.
     *
     * @return array<array-key, mixed>
     */
    public function getAuditStatistics(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('attempted_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('attempted_at', '<=', $to);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_attempts,
            COUNT(CASE WHEN status = ? THEN 1 END) as successful_attempts,
            COUNT(CASE WHEN status = ? THEN 1 END) as failed_attempts,
            COUNT(CASE WHEN status = ? THEN 1 END) as pending_attempts,
            AVG(response_time_ms) as avg_response_time,
            COUNT(DISTINCT gateway_name) as gateways_used,
            COUNT(DISTINCT payment_id) as unique_payments
        ', [PaymentStatus::COMPLETED->value, PaymentStatus::FAILED->value, PaymentStatus::PENDING->value])
            ->first();

        if (! $stats) {
            return [
                'total_attempts' => 0,
                'successful_attempts' => 0,
                'failed_attempts' => 0,
                'pending_attempts' => 0,
                'success_rate' => 0.0,
                'failure_rate' => 0.0,
                'avg_response_time' => null,
                'gateways_used' => 0,
                'unique_payments' => 0,
            ];
        }

        /** @phpstan-ignore-next-line property.notFound */
        $total = (int) $stats->total_attempts;
        /** @phpstan-ignore-next-line property.notFound */
        $successful = (int) $stats->successful_attempts;
        /** @phpstan-ignore-next-line property.notFound */
        $failed = (int) $stats->failed_attempts;

        return [
            'total_attempts' => $total,
            'successful_attempts' => $successful,
            'failed_attempts' => $failed,
            /** @phpstan-ignore-next-line property.notFound */
            'pending_attempts' => (int) $stats->pending_attempts,
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0.0,
            'failure_rate' => $total > 0 ? ($failed / $total) * 100 : 0.0,
            /** @phpstan-ignore-next-line property.notFound */
            'avg_response_time' => $stats->avg_response_time ? (float) $stats->avg_response_time : null,
            /** @phpstan-ignore-next-line property.notFound */
            'gateways_used' => (int) $stats->gateways_used,
            /** @phpstan-ignore-next-line property.notFound */
            'unique_payments' => (int) $stats->unique_payments,
        ];
    }

    /**
     * Get gateway success rates.
     *
     * @return array<string, float>
     */
    public function getGatewaySuccessRates(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('attempted_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('attempted_at', '<=', $to);
        }

        $results = $query->selectRaw('
            gateway_name,
            COUNT(*) as total_attempts,
            COUNT(CASE WHEN status = ? THEN 1 END) as successful_attempts
        ', [PaymentStatus::COMPLETED->value])
            ->groupBy('gateway_name')
            ->get();

        $successRates = [];
        foreach ($results as $result) {
            /** @phpstan-ignore-next-line property.notFound */
            $total = (int) $result->total_attempts;
            /** @phpstan-ignore-next-line property.notFound */
            $successful = (int) $result->successful_attempts;
            $successRates[$result->gateway_name] = $total > 0 ? ($successful / $total) * 100 : 0.0;
        }

        return $successRates;
    }

    /**
     * Get average response times by gateway.
     *
     * @return array<string, float>
     */
    public function getAverageResponseTimes(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('attempted_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('attempted_at', '<=', $to);
        }

        $results = $query->selectRaw('
            gateway_name,
            AVG(response_time_ms) as avg_response_time
        ')
            ->whereNotNull('response_time_ms')
            ->groupBy('gateway_name')
            ->get();

        $averageTimes = [];
        foreach ($results as $result) {
            /** @phpstan-ignore-next-line property.notFound */
            $averageTimes[$result->gateway_name] = (float) $result->avg_response_time;
        }

        return $averageTimes;
    }

    /**
     * Get most common failure reasons.
     *
     * @return array<string, int>
     */
    public function getMostCommonFailures(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('attempted_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('attempted_at', '<=', $to);
        }

        $results = $query->selectRaw('
            COALESCE(error_code, "UNKNOWN") as failure_reason,
            COUNT(*) as failure_count
        ')
            ->where('status', PaymentStatus::FAILED)
            ->groupBy('error_code')
            ->orderBy('failure_count', 'desc')
            ->get();

        $failures = [];
        foreach ($results as $result) {
            /** @phpstan-ignore-next-line property.notFound */
            $failures[$result->failure_reason] = (int) $result->failure_count;
        }

        return $failures;
    }

    /**
     * Get attempts requiring investigation.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findRequiringInvestigation(): Collection
    {
        return $this->model
            ->where(function ($query) {
                $query->where('status', PaymentStatus::FAILED)
                    ->whereIn('error_code', ['network_error', 'gateway_timeout', 'unexpected_error'])
                    ->orWhere('response_time_ms', '>', 30000); // > 30 seconds
            })
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Clean up old audit records.
     */
    public function cleanupOldRecords(int $daysToKeep = 365): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return $this->model
            ->where('attempted_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Get fraud indicators.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function getFraudIndicators(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection
    {
        $query = $this->model->newQuery();

        if ($from instanceof DateTimeInterface) {
            $query->where('attempted_at', '>=', $from);
        }

        if ($to instanceof DateTimeInterface) {
            $query->where('attempted_at', '<=', $to);
        }

        return $query->where(function ($query) {
            $query->where('error_code', 'fraud_detected')
                ->orWhere('error_code', 'suspicious_activity')
                ->orWhere('error_message', 'like', '%fraud%')
                ->orWhere('error_message', 'like', '%suspicious%');
        })
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Find suspicious patterns.
     *
     * @return array<string, mixed>
     */
    public function findSuspiciousPatterns(): array
    {
        // Multiple failed attempts from same IP in short time frame
        $suspiciousIPs = DB::table('payment_attempts')->selectRaw('ip_address, COUNT(*) as attempt_count')
            ->where('status', PaymentStatus::FAILED->value)
            ->where('attempted_at', '>', now()->subHours(1))
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('attempt_count', '>', 5)
            ->get()
            ->pluck('attempt_count', 'ip_address')
            ->toArray();
        $rapidAttempts = DB::table('payment_attempts as pa1')
        // Rapid successive attempts on same payment
            ->select('pa1.payment_id')
            ->selectRaw('COUNT(*) as attempt_count')
            ->join('payment_attempts as pa2', function ($join) {
                $join->on('pa1.payment_id', '=', 'pa2.payment_id')
                    ->whereRaw('pa2.attempted_at BETWEEN pa1.attempted_at AND DATE_ADD(pa1.attempted_at, INTERVAL 5 MINUTE)');
            })
            ->where('pa1.attempted_at', '>', now()->subHour())
            ->groupBy('pa1.payment_id')
            ->having('attempt_count', '>', 3)
            ->get()
            ->pluck('attempt_count', 'payment_id')
            ->toArray();

        // Unusual error patterns
        $unusualErrors = DB::table('payment_attempts')
            ->selectRaw('error_code, COUNT(*) as error_count')
            ->where('status', PaymentStatus::FAILED->value)
            ->where('attempted_at', '>', now()->subDay())
            ->whereNotNull('error_code')
            ->groupBy('error_code')
            ->having('error_count', '>', 10)
            ->get()
            ->pluck('error_count', 'error_code')
            ->toArray();

        return [
            'suspicious_ips' => $suspiciousIPs,
            'rapid_attempts' => $rapidAttempts,
            'unusual_errors' => $unusualErrors,
            'total_indicators' => count($suspiciousIPs) + count($rapidAttempts) + count($unusualErrors),
        ];
    }
}
