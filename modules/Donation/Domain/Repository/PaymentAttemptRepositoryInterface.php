<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Repository;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Donation\Domain\Model\PaymentAttempt;
use Modules\Donation\Domain\ValueObject\PaymentStatus;

/**
 * Payment Attempt Repository Interface.
 *
 * Defines the contract for payment attempt audit trail data access.
 */
interface PaymentAttemptRepositoryInterface
{
    /**
     * Find payment attempt by ID.
     */
    public function findById(int $id): ?PaymentAttempt;

    /**
     * Find attempts for a specific payment.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findByPaymentId(int $paymentId): Collection;

    /**
     * Find attempts by gateway.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findByGateway(string $gatewayName): Collection;

    /**
     * Find attempts by status.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findByStatus(PaymentStatus $status): Collection;

    /**
     * Find failed attempts that are retryable.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findRetryableFailures(): Collection;

    /**
     * Find attempts by gateway request ID.
     */
    public function findByGatewayRequestId(string $requestId): ?PaymentAttempt;

    /**
     * Get attempts with pagination.
     *
     * @return LengthAwarePaginator<int, PaymentAttempt>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get attempts for payment with pagination.
     *
     * @return LengthAwarePaginator<int, PaymentAttempt>
     */
    public function paginateByPayment(int $paymentId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Save payment attempt.
     */
    public function save(PaymentAttempt $attempt): bool;

    /**
     * Delete payment attempt.
     */
    public function delete(PaymentAttempt $attempt): bool;

    /**
     * Get audit statistics.
     *
     * @return array<string, mixed>
     */
    public function getAuditStatistics(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Get gateway success rates.
     *
     * @return array<string, mixed>
     */
    public function getGatewaySuccessRates(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Get average response times by gateway.
     *
     * @return array<string, mixed>
     */
    public function getAverageResponseTimes(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Get most common failure reasons.
     *
     * @return array<string, mixed>
     */
    public function getMostCommonFailures(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Get attempts requiring investigation.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function findRequiringInvestigation(): Collection;

    /**
     * Clean up old audit records.
     */
    public function cleanupOldRecords(int $daysToKeep = 365): int;

    /**
     * Get fraud indicators.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function getFraudIndicators(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): Collection;

    /**
     * Find suspicious patterns.
     *
     * @return array<string, mixed>
     */
    public function findSuspiciousPatterns(): array;
}
