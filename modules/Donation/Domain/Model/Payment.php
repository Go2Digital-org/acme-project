<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Model;

use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Donation\Domain\ValueObject\PaymentStatus;
use Modules\Donation\Infrastructure\Laravel\Factory\PaymentFactory;
use Modules\Shared\Domain\ValueObject\Money;

/**
 * Payment Domain Entity.
 *
 * Represents a payment transaction with comprehensive audit trail.
 * Tracks all payment attempts, states, and gateway interactions.
 *
 * @property int $id
 * @property int $donation_id
 * @property string $gateway_name
 * @property string $intent_id
 * @property string|null $transaction_id
 * @property numeric $amount
 * @property string $currency
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $status
 * @property string|null $gateway_customer_id
 * @property string|null $gateway_payment_method_id
 * @property string|null $failure_code
 * @property string|null $failure_message
 * @property string|null $decline_code
 * @property array<array-key, mixed>|null $gateway_data
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $authorized_at
 * @property Carbon|null $captured_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Donation $donation
 * @property string $formatted_amount
 * @property string $gateway_display_name
 * @property string $status_description
 * @property int|null $processing_duration
 *
 * @method static Builder|Payment where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|Payment newModelQuery()
 * @method static Builder<static>|Payment newQuery()
 * @method static Builder<static>|Payment query()
 * @method static Builder<static>|Payment whereAmount($value)
 * @method static Builder<static>|Payment whereAuthorizedAt($value)
 * @method static Builder<static>|Payment whereCancelledAt($value)
 * @method static Builder<static>|Payment whereCapturedAt($value)
 * @method static Builder<static>|Payment whereCreatedAt($value)
 * @method static Builder<static>|Payment whereCurrency($value)
 * @method static Builder<static>|Payment whereDeclineCode($value)
 * @method static Builder<static>|Payment whereDonationId($value)
 * @method static Builder<static>|Payment whereExpiresAt($value)
 * @method static Builder<static>|Payment whereFailedAt($value)
 * @method static Builder<static>|Payment whereFailureCode($value)
 * @method static Builder<static>|Payment whereFailureMessage($value)
 * @method static Builder<static>|Payment whereGatewayCustomerId($value)
 * @method static Builder<static>|Payment whereGatewayData($value)
 * @method static Builder<static>|Payment whereGatewayName($value)
 * @method static Builder<static>|Payment whereGatewayPaymentMethodId($value)
 * @method static Builder<static>|Payment whereId($value)
 * @method static Builder<static>|Payment whereIntentId($value)
 * @method static Builder<static>|Payment whereMetadata($value)
 * @method static Builder<static>|Payment wherePaymentMethod($value)
 * @method static Builder<static>|Payment whereStatus($value)
 * @method static Builder<static>|Payment whereTransactionId($value)
 * @method static Builder<static>|Payment whereUpdatedAt($value)
 *
 * @mixin Model
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public int $id;

    public int $donation_id;

    public string $gateway_name;

    public string $intent_id;

    public ?string $transaction_id = null;

    public float $amount;

    public string $currency;

    public PaymentMethod $payment_method;

    public PaymentStatus $status;

    public ?string $gateway_customer_id = null;

    public ?string $gateway_payment_method_id = null;

    public ?string $failure_code = null;

    public ?string $failure_message = null;

    public ?string $decline_code = null;

    /** @var array<string, mixed>|null */
    public ?array $gateway_data = null;

    /** @var array<string, mixed>|null */
    public ?array $metadata = null;

    public ?Carbon $authorized_at = null;

    public ?Carbon $captured_at = null;

    public ?Carbon $failed_at = null;

    public ?Carbon $cancelled_at = null;

    public ?Carbon $expires_at = null;

    public ?Carbon $created_at = null;

    public ?Carbon $updated_at = null;

    protected $fillable = [
        'donation_id',
        'gateway_name',
        'intent_id',
        'transaction_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'gateway_customer_id',
        'gateway_payment_method_id',
        'failure_code',
        'failure_message',
        'decline_code',
        'gateway_data',
        'metadata',
        'authorized_at',
        'captured_at',
        'failed_at',
        'cancelled_at',
        'expires_at',
    ];

    protected $attributes = [
        'currency' => 'USD',
        'status' => PaymentStatus::PENDING,
    ];

    /**
     * Get the donation this payment belongs to.
     *
     * @return BelongsTo<Donation, $this>
     */
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    /**
     * Create Money value object from amount and currency.
     */
    public function getMoney(): Money
    {
        return new Money(
            (float) $this->amount,
            $this->currency ?? 'USD',
        );
    }

    /**
     * Update payment amount.
     */
    public function updateAmount(Money $money): void
    {
        $this->amount = $money->amount;
        $this->currency = $money->currency;
    }

    /**
     * Mark payment as authorized.
     */
    /**
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function authorize(string $transactionId, ?array $gatewayData = null): void
    {
        if (! $this->canBeAuthorized()) {
            throw new DomainException('Payment cannot be authorized in current state: ' . $this->status->value);
        }

        $this->update([
            'transaction_id' => $transactionId,
            'status' => PaymentStatus::PROCESSING,
            'authorized_at' => now(),
            'gateway_data' => array_merge($this->gateway_data ?? [], $gatewayData ?? []),
        ]);
    }

    /**
     * Mark payment as captured/completed.
     */
    /**
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function capture(?array $gatewayData = null): void
    {
        if (! $this->canBeCaptured()) {
            throw new DomainException('Payment cannot be captured in current state: ' . $this->status->value);
        }

        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'captured_at' => now(),
            'gateway_data' => array_merge($this->gateway_data ?? [], $gatewayData ?? []),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    /**
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function fail(
        string $failureMessage,
        ?string $failureCode = null,
        ?string $declineCode = null,
        ?array $gatewayData = null,
    ): void {
        if (! $this->canBeFailed()) {
            throw new DomainException('Payment cannot be failed in current state: ' . $this->status->value);
        }

        $this->update([
            'status' => PaymentStatus::FAILED,
            'failure_message' => $failureMessage,
            'failure_code' => $failureCode,
            'decline_code' => $declineCode,
            'failed_at' => now(),
            'gateway_data' => array_merge($this->gateway_data ?? [], $gatewayData ?? []),
        ]);
    }

    /**
     * Mark payment as cancelled.
     */
    /**
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function cancel(?array $gatewayData = null): void
    {
        if (! $this->canBeCancelled()) {
            throw new DomainException('Payment cannot be cancelled in current state: ' . $this->status->value);
        }

        $this->update([
            'status' => PaymentStatus::CANCELLED,
            'cancelled_at' => now(),
            'gateway_data' => array_merge($this->gateway_data ?? [], $gatewayData ?? []),
        ]);
    }

    /**
     * Update payment status from gateway.
     */
    /**
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function updateFromGateway(
        PaymentStatus $status,
        ?string $transactionId = null,
        ?array $gatewayData = null,
    ): void {
        $updates = [
            'status' => $status,
            'gateway_data' => array_merge($this->gateway_data ?? [], $gatewayData ?? []),
        ];

        if ($transactionId && ! $this->transaction_id) {
            $updates['transaction_id'] = $transactionId;
        }

        // Set timestamps based on status
        match ($status) {
            PaymentStatus::PROCESSING => $updates['authorized_at'] = now(),
            PaymentStatus::COMPLETED => $updates['captured_at'] = now(),
            PaymentStatus::FAILED => $updates['failed_at'] = now(),
            PaymentStatus::CANCELLED => $updates['cancelled_at'] = now(),
            default => null,
        };

        $this->update($updates);
    }

    /**
     * Set payment expiration.
     */
    public function setExpiration(Carbon $expiresAt): void
    {
        $this->update(['expires_at' => $expiresAt]);
    }

    /**
     * Check if payment is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isPast();
    }

    /**
     * Check if payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment requires action.
     */
    public function requiresAction(): bool
    {
        return $this->status === PaymentStatus::REQUIRES_ACTION;
    }

    /**
     * Check if payment failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if payment can be authorized.
     */
    public function canBeAuthorized(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment can be captured.
     */
    public function canBeCaptured(): bool
    {
        return in_array($this->status, [
            PaymentStatus::PENDING,
            PaymentStatus::PROCESSING,
            PaymentStatus::REQUIRES_ACTION,
        ], true);
    }

    /**
     * Check if payment can be failed.
     */
    public function canBeFailed(): bool
    {
        if (! $this->status->isFinal()) {
            return true;
        }

        return $this->status === PaymentStatus::PROCESSING;
    }

    /**
     * Check if payment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Get formatted amount for display.
     *
     * @return Attribute<string, never>
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->getMoney()->format());
    }

    /**
     * Get gateway display name.
     *
     * @return Attribute<string, never>
     */
    protected function gatewayDisplayName(): Attribute
    {
        return Attribute::make(get: fn (): string => match ($this->gateway_name) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'mock' => 'Mock Gateway',
            default => ucfirst($this->gateway_name),
        });
    }

    /**
     * Get status display description.
     *
     * @return Attribute<string, never>
     */
    protected function statusDescription(): Attribute
    {
        return Attribute::make(get: fn (): string => $this->status->getDescription());
    }

    /**
     * Get processing duration in seconds.
     *
     * @return Attribute<int|null, never>
     */
    protected function processingDuration(): Attribute
    {
        return Attribute::make(get: function (): ?int {
            if (! $this->captured_at instanceof Carbon || ! $this->created_at instanceof Carbon) {
                return null;
            }

            return (int) $this->created_at->diffInSeconds($this->captured_at);
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentFactory
    {
        return PaymentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'currency' => 'string',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'gateway_data' => 'array',
            'metadata' => 'array',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
