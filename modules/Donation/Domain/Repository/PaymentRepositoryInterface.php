<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Repository;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Donation\Domain\Model\Payment;
use Modules\Donation\Domain\ValueObject\PaymentStatus;

/**
 * Payment Repository Interface.
 *
 * Defines the contract for payment data access.
 * Provides domain-specific query methods for payments.
 */
interface PaymentRepositoryInterface
{
    /**
     * Find payment by ID.
     */
    public function findById(int $id): ?Payment;

    /**
     * Find payment by intent ID.
     */
    public function findByIntentId(string $intentId): ?Payment;

    /**
     * Find payment by transaction ID.
     */
    public function findByTransactionId(string $transactionId): ?Payment;

    /**
     * Find payments for a donation.
     *
     * @return Collection<int, Payment>
     */
    public function findByDonationId(int $donationId): Collection;

    /**
     * Find payments by status.
     *
     * @return Collection<int, Payment>
     */
    public function findByStatus(PaymentStatus $status): Collection;

    /**
     * Find payments by gateway.
     *
     * @return Collection<int, Payment>
     */
    public function findByGateway(string $gatewayName): Collection;

    /**
     * Find expired payments that need cleanup.
     *
     * @return Collection<int, Payment>
     */
    public function findExpiredPayments(): Collection;

    /**
     * Find stuck/stale payments for recovery.
     *
     * @return Collection<int, Payment>
     */
    public function findStalePayments(int $minutesThreshold = 30): Collection;

    /**
     * Find payments requiring action.
     *
     * @return Collection<int, Payment>
     */
    public function findRequiringAction(): Collection;

    /**
     * Get payments with pagination.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Get payments for donation with pagination.
     *
     * @return LengthAwarePaginator<int, Payment>
     */
    public function paginateByDonation(int $donationId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Save payment.
     */
    public function save(Payment $payment): bool;

    /**
     * Delete payment.
     */
    public function delete(Payment $payment): bool;

    /**
     * Get payment statistics for dashboard.
     */
    /** @return array<array-key, mixed> */
    public function getStatistics(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Get successful payments total amount.
     */
    public function getTotalSuccessfulAmount(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): float;

    /**
     * Get payment success rate.
     */
    public function getSuccessRate(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): float;

    /**
     * Get average processing time in seconds.
     */
    public function getAverageProcessingTime(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): ?float;

    /**
     * Get payment volume by gateway.
     *
     * @return array<string, int>
     */
    public function getVolumeByGateway(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Get failure reasons breakdown.
     *
     * @return array<string, int>
     */
    public function getFailureReasons(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array;

    /**
     * Find payments needing reconciliation.
     *
     * @return Collection<int, Payment>
     */
    public function findNeedingReconciliation(): Collection;

    /**
     * Bulk update payment statuses.
     *
     * @param  int[]  $paymentIds
     */
    public function bulkUpdateStatus(array $paymentIds, PaymentStatus $status): int;

    /**
     * Find payments with metadata key.
     *
     * @return Collection<int, Payment>
     */
    public function findWithMetadataKey(string $key, mixed $value = null): Collection;

    /**
     * Get payment retry candidates.
     *
     * @return Collection<int, Payment>
     */
    public function getRetryCandidates(int $maxRetries = 3): Collection;
}
