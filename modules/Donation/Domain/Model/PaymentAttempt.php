<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Donation\Domain\ValueObject\PaymentStatus;

/**
 * Payment Attempt Domain Entity.
 *
 * Provides comprehensive audit trail for all payment processing attempts.
 * Tracks every interaction with payment gateways for compliance and debugging.
 *
 * @property int $id
 * @property int $payment_id
 * @property int $attempt_number
 * @property string $gateway_name
 * @property string $gateway_action
 * @property string|null $gateway_request_id
 * @property array<string, mixed>|null $request_data
 * @property array<string, mixed>|null $response_data
 * @property PaymentStatus $status
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int|null $response_time_ms
 * @property string|null $gateway_transaction_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $attempted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Payment $payment
 *
 * @method static Builder|PaymentAttempt where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|PaymentAttempt newModelQuery()
 * @method static Builder<static>|PaymentAttempt newQuery()
 * @method static Builder<static>|PaymentAttempt query()
 * @method static Builder<static>|PaymentAttempt whereAttemptNumber($value)
 * @method static Builder<static>|PaymentAttempt whereAttemptedAt($value)
 * @method static Builder<static>|PaymentAttempt whereCreatedAt($value)
 * @method static Builder<static>|PaymentAttempt whereErrorCode($value)
 * @method static Builder<static>|PaymentAttempt whereErrorMessage($value)
 * @method static Builder<static>|PaymentAttempt whereGatewayAction($value)
 * @method static Builder<static>|PaymentAttempt whereGatewayName($value)
 * @method static Builder<static>|PaymentAttempt whereGatewayRequestId($value)
 * @method static Builder<static>|PaymentAttempt whereGatewayTransactionId($value)
 * @method static Builder<static>|PaymentAttempt whereId($value)
 * @method static Builder<static>|PaymentAttempt whereIpAddress($value)
 * @method static Builder<static>|PaymentAttempt wherePaymentId($value)
 * @method static Builder<static>|PaymentAttempt whereRequestData($value)
 * @method static Builder<static>|PaymentAttempt whereResponseData($value)
 * @method static Builder<static>|PaymentAttempt whereResponseTimeMs($value)
 * @method static Builder<static>|PaymentAttempt whereStatus($value)
 * @method static Builder<static>|PaymentAttempt whereUpdatedAt($value)
 * @method static Builder<static>|PaymentAttempt whereUserAgent($value)
 *
 * @mixin Model
 */
class PaymentAttempt extends Model
{
    public int $id;

    public int $payment_id;

    public int $attempt_number;

    public string $gateway_name;

    public string $gateway_action;

    public ?string $gateway_request_id = null;

    /** @var array<string, mixed>|null */
    public ?array $request_data = null;

    /** @var array<string, mixed>|null */
    public ?array $response_data = null;

    public PaymentStatus $status;

    public ?string $error_code = null;

    public ?string $error_message = null;

    public ?int $response_time_ms = null;

    public ?string $gateway_transaction_id = null;

    public ?string $ip_address = null;

    public ?string $user_agent = null;

    public Carbon $attempted_at;

    public Carbon $created_at;

    public Carbon $updated_at;

    protected $fillable = [
        'payment_id',
        'attempt_number',
        'gateway_name',
        'gateway_action',
        'gateway_request_id',
        'request_data',
        'response_data',
        'status',
        'error_code',
        'error_message',
        'response_time_ms',
        'gateway_transaction_id',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    protected $attributes = [
        'status' => PaymentStatus::PENDING,
    ];

    /**
     * Get the payment this attempt belongs to.
     *
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Create a new payment attempt.
     */
    /**
     * @param  array<string, mixed>  $requestData
     */
    public static function create(
        int $paymentId,
        string $gatewayName,
        string $gatewayAction,
        array $requestData = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        $lastAttempt = self::where('payment_id', $paymentId)
            ->orderBy('attempt_number', 'desc')
            ->first();

        $attemptNumber = ($lastAttempt->attempt_number ?? 0) + 1;

        $attempt = new self([
            'payment_id' => $paymentId,
            'attempt_number' => $attemptNumber,
            'gateway_name' => $gatewayName,
            'gateway_action' => $gatewayAction,
            'gateway_request_id' => uniqid('req_', true),
            'request_data' => $requestData,
            'status' => PaymentStatus::PENDING,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'attempted_at' => now(),
        ]);

        $attempt->save();

        return $attempt;
    }

    /**
     * Mark attempt as successful.
     */
    /**
     * @param  array<string, mixed>  $responseData
     */
    public function markSuccessful(
        array $responseData = [],
        ?string $gatewayTransactionId = null,
        ?int $responseTimeMs = null,
    ): void {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'response_data' => $responseData,
            'gateway_transaction_id' => $gatewayTransactionId,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Mark attempt as failed.
     */
    /**
     * @param  array<string, mixed>  $responseData
     */
    public function markFailed(
        string $errorMessage,
        ?string $errorCode = null,
        array $responseData = [],
        ?int $responseTimeMs = null,
    ): void {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'response_data' => $responseData,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Mark attempt as pending (requires action).
     */
    /**
     * @param  array<string, mixed>  $responseData
     */
    public function markPending(
        array $responseData = [],
        ?int $responseTimeMs = null,
    ): void {
        $this->update([
            'status' => PaymentStatus::PENDING,
            'response_data' => $responseData,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Check if this was a successful attempt.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if this attempt failed.
     */
    public function failed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if this attempt is still pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Get the duration of this attempt in milliseconds.
     */
    public function getDurationMs(): ?int
    {
        return $this->response_time_ms;
    }

    /**
     * Get the duration of this attempt in seconds.
     */
    public function getDurationSeconds(): ?float
    {
        return $this->response_time_ms ? $this->response_time_ms / 1000 : null;
    }

    /**
     * Check if this attempt is retryable.
     */
    public function isRetryable(): bool
    {
        if (! $this->failed()) {
            return false;
        }

        // Some error codes indicate permanent failures
        $permanentFailureCodes = [
            'card_declined',
            'insufficient_funds',
            'invalid_card',
            'expired_card',
            'authentication_required',
        ];

        return ! in_array($this->error_code, $permanentFailureCodes, true);
    }

    /**
     * Get sanitized request data (removes sensitive information).
     *
     * @return array<string, mixed>
     */
    public function getSanitizedRequestData(): array
    {
        if (! $this->request_data) {
            return [];
        }

        $sensitiveKeys = [
            'card_number',
            'cvv',
            'cvc',
            'password',
            'secret_key',
            'private_key',
        ];

        return $this->sanitizeArray($this->request_data, $sensitiveKeys);
    }

    /**
     * Get sanitized response data (removes sensitive information).
     *
     * @return array<string, mixed>
     */
    public function getSanitizedResponseData(): array
    {
        if (! $this->response_data) {
            return [];
        }

        $sensitiveKeys = [
            'secret_key',
            'private_key',
            'access_token',
        ];

        return $this->sanitizeArray($this->response_data, $sensitiveKeys);
    }

    /**
     * Get formatted error message for display.
     */
    public function getFormattedErrorMessage(): ?string
    {
        if (! $this->error_message) {
            return null;
        }

        return sprintf(
            '[%s] %s',
            $this->error_code ?? 'UNKNOWN',
            $this->error_message,
        );
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'response_data' => 'array',
            'status' => PaymentStatus::class,
            'response_time_ms' => 'integer',
            'attempted_at' => 'datetime',
        ];
    }
}
