<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Donation\Infrastructure\Laravel\Factory\DonationFactory;
use Modules\Shared\Domain\Contract\DonationInterface;
use Modules\Shared\Domain\Traits\HasTranslations;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Modules\User\Infrastructure\Laravel\Models\User;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $campaign_id
 * @property int|null $user_id
 * @property float $amount
 * @property string $currency
 * @property PaymentMethod|null $payment_method
 * @property string|null $payment_gateway
 * @property string|null $transaction_id
 * @property string|null $gateway_response_id
 * @property DonationStatus $status
 * @property bool $anonymous
 * @property bool $recurring
 * @property string|null $recurring_frequency
 * @property \Illuminate\Support\Carbon $donated_at
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property string|null $refund_reason
 * @property string|null $notes
 * @property array<string, string>|null $notes_translations
 * @property array<string, mixed>|null $metadata
 * @property bool $is_anonymous
 * @property float|null $corporate_match_amount
 * @property \Illuminate\Support\Carbon|null $confirmation_email_failed_at
 * @property string|null $confirmation_email_failure_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Campaign|null $campaign
 * @property User|null $user
 * @property int $days_since_donation
 * @property string $formatted_amount
 *
 * @method static Builder|Donation where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static DonationFactory factory($count = null, $state = [])
 * @method static Builder<static>|Donation newModelQuery()
 * @method static Builder<static>|Donation newQuery()
 * @method static Builder<static>|Donation query()
 * @method static Builder<static>|Donation whereAmount($value)
 * @method static Builder<static>|Donation whereAnonymous($value)
 * @method static Builder<static>|Donation whereCampaignId($value)
 * @method static Builder<static>|Donation whereCancelledAt($value)
 * @method static Builder<static>|Donation whereCompletedAt($value)
 * @method static Builder<static>|Donation whereCreatedAt($value)
 * @method static Builder<static>|Donation whereCurrency($value)
 * @method static Builder<static>|Donation whereDonatedAt($value)
 * @method static Builder<static>|Donation whereUserId($value)
 * @method static Builder<static>|Donation whereFailureReason($value)
 * @method static Builder<static>|Donation whereGatewayResponseId($value)
 * @method static Builder<static>|Donation whereId($value)
 * @method static Builder<static>|Donation whereMetadata($value)
 * @method static Builder<static>|Donation whereNotes($value)
 * @method static Builder<static>|Donation whereNotesTranslations($value)
 * @method static Builder<static>|Donation wherePaymentGateway($value)
 * @method static Builder<static>|Donation wherePaymentMethod($value)
 * @method static Builder<static>|Donation whereProcessedAt($value)
 * @method static Builder<static>|Donation whereRecurring($value)
 * @method static Builder<static>|Donation whereRecurringFrequency($value)
 * @method static Builder<static>|Donation whereRefundReason($value)
 * @method static Builder<static>|Donation whereRefundedAt($value)
 * @method static Builder<static>|Donation whereStatus($value)
 * @method static Builder<static>|Donation whereTransactionId($value)
 * @method static Builder<static>|Donation whereUpdatedAt($value)
 *
 * @mixin Model
 */
class Donation extends Model implements Auditable, DonationInterface
{
    use AuditableTrait;

    /** @use HasFactory<DonationFactory> */
    use HasFactory;

    use HasTranslations;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'payment_gateway',
        'transaction_id',
        'gateway_response_id',
        'status',
        'anonymous',
        'recurring',
        'recurring_frequency',
        'donated_at',
        'processed_at',
        'completed_at',
        'cancelled_at',
        'refunded_at',
        'failure_reason',
        'failed_at',
        'refund_reason',
        'notes',
        'notes_translations',
        'metadata',
        'corporate_match_amount',
        'confirmation_email_failed_at',
        'confirmation_email_failure_reason',
        'donor_name',
        'donor_email',
    ];

    /**
     * Translatable fields.
     *
     * @var list<string>
     */
    protected $translatable = [
        'notes',
    ];

    /**
     * Boot method to handle model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set default values for new models
        static::creating(function (Donation $donation): void {
            $donation->currency ??= 'EUR';
            $donation->anonymous ??= false;
            $donation->recurring ??= false;
            $donation->status ??= DonationStatus::PENDING;
        });

        // Update donations_count when a donation is created and completed
        static::created(function (Donation $donation): void {
            if ($donation->status === DonationStatus::COMPLETED) {
                $donation->campaign()->increment('donations_count');
            }
        });

        // Update donations_count when status changes
        static::updated(function (Donation $donation): void {
            // Check if status changed
            if ($donation->isDirty('status')) {
                $oldStatusValue = $donation->getOriginal('status');

                $oldStatus = $oldStatusValue instanceof DonationStatus
                    ? $oldStatusValue
                    : DonationStatus::tryFrom((string) $oldStatusValue);
                $newStatus = $donation->status;

                // If changed to completed, increment
                if ($oldStatus !== DonationStatus::COMPLETED && $newStatus === DonationStatus::COMPLETED) {
                    $donation->campaign()->increment('donations_count');

                    return;
                }

                // If changed from completed to something else, decrement
                if ($oldStatus === DonationStatus::COMPLETED && $newStatus !== DonationStatus::COMPLETED) {
                    $donation->campaign()->decrement('donations_count');
                }
            }
        });

        // Decrement donations_count when a completed donation is deleted
        static::deleting(function (Donation $donation): void {
            if ($donation->status === DonationStatus::COMPLETED) {
                $donation->campaign()->decrement('donations_count');
            }
        });
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(get: fn (): string => '$' . number_format($this->amount, 2, '.', ','));
    }

    /**
     * @return Attribute<int, never>
     */
    protected function daysSinceDonation(): Attribute
    {
        return Attribute::make(get: function (): int {
            if ($this->donated_at === null) {
                return 0;
            }

            return (int) $this->donated_at->diffInDays(now());
        });
    }

    public function getIsAnonymousAttribute(): bool
    {
        return $this->anonymous;
    }

    public function canBeProcessed(): bool
    {
        return $this->status->canBeProcessed();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function canBeRefunded(): bool
    {
        if (! $this->status->canBeRefunded()) {
            return false;
        }

        // Can only refund within 90 days (inclusive)
        if (! $this->processed_at instanceof Carbon) {
            return false;
        }

        $refundDeadline = $this->processed_at->copy()->addDays(90)->endOfDay();

        return now()->lessThanOrEqualTo($refundDeadline);
    }

    public function isSuccessful(): bool
    {
        return $this->status === DonationStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === DonationStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === DonationStatus::FAILED;
    }

    public function process(string $transactionId): void
    {
        $this->transaction_id = $transactionId;
        $this->status = DonationStatus::PROCESSING;
        $this->processed_at = now();

        if ($this->exists) {
            $this->save();
        }
    }

    public function complete(): void
    {
        $this->status = DonationStatus::COMPLETED;
        $this->completed_at = now();

        if ($this->exists) {
            $this->save();
        }
    }

    public function fail(string $reason): void
    {
        $this->status = DonationStatus::FAILED;
        $this->failure_reason = $reason;
        $this->failed_at = now();

        if ($this->exists) {
            $this->save();
        }
    }

    public function cancel(?string $reason = null): void
    {
        $this->status = DonationStatus::CANCELLED;
        $this->cancelled_at = now();

        if ($reason !== null) {
            // Store cancellation reason in notes field with prefix
            $this->notes = 'Cancelled: ' . $reason;
        }

        if ($this->exists) {
            $this->save();
        }
    }

    public function refund(string $reason): void
    {
        $this->status = DonationStatus::REFUNDED;
        $this->refund_reason = $reason;
        $this->refunded_at = now();

        if ($this->exists) {
            $this->save();
        }
    }

    /**
     * Update payment details from gateway response.
     *
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function updatePaymentDetails(string $gatewayPaymentId, ?array $gatewayData = null): void
    {
        $this->gateway_response_id = $gatewayPaymentId;
        $this->metadata = $gatewayData;

        if ($this->exists) {
            $this->save();
        }
    }

    /**
     * Determine if donation is eligible for tax receipt.
     */
    public function isEligibleForTaxReceipt(): bool
    {
        // Tax receipts are eligible for completed donations above minimum threshold
        return $this->status === DonationStatus::COMPLETED
            && $this->amount >= 20.00
            && ! $this->anonymous;
    }

    // DonationInterface implementation
    public function getId(): int
    {
        return $this->id;
    }

    public function getCampaignId(): int
    {
        return $this->campaign_id;
    }

    public function getDonorId(): ?int
    {
        return $this->user_id;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status->value;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at?->toISOString() ?? '';
    }

    public function getTransactionId(): ?string
    {
        return $this->transaction_id;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method?->value;
    }

    public function getRecurringFrequency(): ?string
    {
        return $this->recurring_frequency;
    }

    public function isAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function getCampaignTitle(): ?string
    {
        return $this->campaign?->title;
    }

    public function getOrganizationName(): ?string
    {
        return $this->campaign?->organization?->getName();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->load(['campaign.organization', 'user']);

        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method?->value,
            'payment_gateway' => $this->payment_gateway,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status->value,
            'anonymous' => $this->anonymous,
            'recurring' => $this->recurring,
            'recurring_frequency' => $this->recurring_frequency,
            'donated_at' => $this->donated_at->toIso8601String(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'campaign_id' => $this->campaign_id,
            'campaign_title' => $this->campaign?->title,
            'campaign_category' => $this->campaign?->category,
            'organization_id' => $this->campaign?->organization_id,
            'organization_name' => $this->campaign?->organization?->getName(),
            'user_id' => $this->user_id,
            'user_name' => $this->anonymous ? null : $this->user?->name,
            'user_email' => $this->anonymous ? null : $this->user?->email,
            'user_department' => $this->anonymous ? null : $this->user?->department,
            'donor_name' => $this->anonymous ? 'Anonymous' : ($this->user->name ?? 'Guest'),
            'donor_email' => $this->anonymous ? null : ($this->user->email ?? $this->donor_email ?? null),
            'notes' => $this->notes,
            'failure_reason' => $this->failure_reason,
            'refund_reason' => $this->refund_reason,
            'corporate_match_amount' => $this->corporate_match_amount,
            'is_successful' => $this->isSuccessful(),
            'is_pending' => $this->isPending(),
            'is_failed' => $this->isFailed(),
            'can_be_refunded' => $this->canBeRefunded(),
            'eligible_for_tax_receipt' => $this->isEligibleForTaxReceipt(),
            'days_since_donation' => $this->days_since_donation,
            'formatted_amount' => $this->formatted_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Amount ranges for filtering
            'amount_range' => $this->getAmountRange(),
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return true; // All donations should be searchable for admin purposes
    }

    /**
     * Modify the query used to retrieve models when making all searchable.
     */
    protected function makeAllSearchableUsing(mixed $query): mixed
    {
        return $query->with(['campaign.organization', 'user']);
    }

    /**
     * Get amount range for filtering.
     */
    public function getAmountRange(): string
    {
        $amount = $this->amount;

        if ($amount < 25) {
            return 'under_25';
        }

        if ($amount < 100) {
            return '25_to_100';
        }

        if ($amount < 500) {
            return '100_to_500';
        }

        if ($amount < 1000) {
            return '500_to_1000';
        }

        return 'over_1000';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DonationFactory
    {
        return DonationFactory::new();
    }

    protected static function booted(): void
    {
        self::creating(function (self $donation): void {
            if ($donation->donated_at === null) {
                $donation->donated_at = now();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'payment_method' => PaymentMethod::class,
            'status' => DonationStatus::class,
            'anonymous' => 'boolean',
            'recurring' => 'boolean',
            'donated_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
            'notes_translations' => 'array',
            'corporate_match_amount' => 'float',
            'confirmation_email_failed_at' => 'datetime',
        ];
    }
}
