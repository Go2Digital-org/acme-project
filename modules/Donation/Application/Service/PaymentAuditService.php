<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Donation\Domain\Model\Payment;
use Modules\Donation\Domain\Model\PaymentAttempt;
use Modules\Donation\Domain\Repository\PaymentAttemptRepositoryInterface;
use Modules\Donation\Domain\ValueObject\PaymentResult;

/**
 * Payment Audit Service.
 *
 * Handles comprehensive audit logging for all payment operations.
 * Provides compliance and debugging capabilities.
 */
final readonly class PaymentAuditService
{
    public function __construct(
        private PaymentAttemptRepositoryInterface $attemptRepository,
    ) {}

    /**
     * Log payment attempt start.
     */
    /**
     * @param  array<string, mixed>  $requestData
     */
    public function logAttemptStart(
        Payment $payment,
        string $gatewayAction,
        array $requestData = [],
        ?Request $request = null,
    ): PaymentAttempt {
        return PaymentAttempt::create(
            paymentId: $payment->id,
            gatewayName: $payment->gateway_name,
            gatewayAction: $gatewayAction,
            requestData: $this->sanitizeRequestData($requestData),
            ipAddress: $request?->ip(),
            userAgent: $request?->userAgent(),
        );
    }

    /**
     * Log payment attempt completion.
     */
    public function logAttemptCompletion(
        PaymentAttempt $attempt,
        PaymentResult $result,
        int $responseTimeMs = 0,
    ): void {
        if ($result->isSuccessful()) {
            $attempt->markSuccessful(
                responseData: $this->sanitizeResponseData($result->toArray()),
                gatewayTransactionId: $result->getTransactionId(),
                responseTimeMs: $responseTimeMs,
            );

            return;
        }

        if ($result->hasFailed()) {
            $attempt->markFailed(
                errorMessage: $result->getErrorMessage(),
                errorCode: $result->getErrorCode(),
                responseData: $this->sanitizeResponseData($result->toArray()),
                responseTimeMs: $responseTimeMs,
            );

            return;
        }

        $attempt->markPending(
            responseData: $this->sanitizeResponseData($result->toArray()),
            responseTimeMs: $responseTimeMs,
        );
    }

    /**
     * Get audit trail for payment.
     *
     * @return Collection<int, PaymentAttempt>
     */
    public function getPaymentAuditTrail(int $paymentId): Collection
    {
        return $this->attemptRepository->findByPaymentId($paymentId);
    }

    /**
     * Get fraud risk indicators for payment.
     */
    /**
     * @return array<string, mixed>
     */
    public function getFraudRiskIndicators(Payment $payment): array
    {
        $attempts = $this->attemptRepository->findByPaymentId($payment->id);
        $indicators = [];

        // Multiple failures in short time
        $failedAttempts = $attempts->where('status', 'failed');

        if ($failedAttempts->count() > 2) {
            $indicators['multiple_failures'] = [
                'severity' => 'high',
                'message' => 'Multiple payment failures detected',
                'count' => $failedAttempts->count(),
            ];
        }

        // Rapid successive attempts
        if ($attempts->count() > 1) {
            $timeDiffs = [];
            $sorted = $attempts->sortBy('attempted_at');

            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted->values()[$i - 1];
                $curr = $sorted->values()[$i];

                if ($prev === null) {
                    continue;
                }

                if ($curr === null) {
                    continue;
                }

                $timeDiffs[] = $curr->attempted_at->diffInSeconds($prev->attempted_at);
            }

            if (count($timeDiffs) > 0) {
                $avgTimeDiff = array_sum($timeDiffs) / count($timeDiffs);

                if ($avgTimeDiff < 30) { // Less than 30 seconds between attempts
                    $indicators['rapid_attempts'] = [
                        'severity' => 'medium',
                        'message' => 'Rapid successive payment attempts',
                        'avg_time_diff_seconds' => $avgTimeDiff,
                    ];
                }
            }
        }

        // Different IP addresses
        $uniqueIps = $attempts->pluck('ip_address')->filter()->unique();

        if ($uniqueIps->count() > 1) {
            $indicators['multiple_ips'] = [
                'severity' => 'medium',
                'message' => 'Payment attempts from multiple IP addresses',
                'ip_count' => $uniqueIps->count(),
            ];
        }

        // Unusual error patterns
        $errorCodes = $failedAttempts->pluck('error_code')->filter()->unique();
        $suspiciousErrors = ['card_declined', 'insufficient_funds', 'expired_card'];
        $matchingErrors = $errorCodes->intersect($suspiciousErrors);

        if ($matchingErrors->count() > 1) {
            $indicators['suspicious_error_pattern'] = [
                'severity' => 'low',
                'message' => 'Multiple suspicious error codes',
                'error_codes' => $matchingErrors->toArray(),
            ];
        }

        return $indicators;
    }

    /**
     * Generate audit report for time period.
     */
    /**
     * @return array<string, mixed>
     */
    public function generateAuditReport(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        return $this->attemptRepository->getAuditStatistics($from, $to);
    }

    /**
     * Get gateway performance metrics.
     */
    /**
     * @return array<string, mixed>
     */
    public function getGatewayPerformanceMetrics(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        return [
            'success_rates' => $this->attemptRepository->getGatewaySuccessRates($from, $to),
            'response_times' => $this->attemptRepository->getAverageResponseTimes($from, $to),
            'failure_reasons' => $this->attemptRepository->getMostCommonFailures($from, $to),
        ];
    }

    /**
     * Clean up old audit records.
     */
    public function cleanupOldAuditRecords(int $daysToKeep = 365): int
    {
        return $this->attemptRepository->cleanupOldRecords($daysToKeep);
    }

    /**
     * Get compliance report.
     */
    /**
     * @return array<string, mixed>
     */
    public function getComplianceReport(?DateTimeInterface $from = null, ?DateTimeInterface $to = null): array
    {
        $stats = $this->attemptRepository->getAuditStatistics($from, $to);
        $fraudIndicators = $this->attemptRepository->getFraudIndicators($from, $to);

        return [
            'period' => [
                'from' => $from?->format('Y-m-d H:i:s'),
                'to' => $to?->format('Y-m-d H:i:s'),
            ],
            'statistics' => $stats,
            'fraud_indicators' => [
                'count' => $fraudIndicators->count(),
                'summary' => $fraudIndicators->groupBy('error_code')->map->count(),
            ],
            'suspicious_patterns' => $this->attemptRepository->findSuspiciousPatterns(),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = [
            'card_number',
            'cvv',
            'cvc',
            'security_code',
            'password',
            'secret_key',
            'private_key',
            'access_token',
        ];

        return $this->sanitizeArray($data, $sensitiveKeys);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeResponseData(array $data): array
    {
        $sensitiveKeys = [
            'client_secret',
            'secret_key',
            'private_key',
            'access_token',
            'webhook_secret',
        ];

        return $this->sanitizeArray($data, $sensitiveKeys);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $sensitiveKeys
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), array_map('strtolower', $sensitiveKeys), true)) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, $sensitiveKeys);
            }
        }

        return $data;
    }
}
