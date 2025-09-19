<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Model;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\Currency\Domain\Model\Currency;
use Modules\Donation\Domain\Service\PaymentGatewayConfigRegistry;
use Modules\Donation\Infrastructure\Laravel\Factory\PaymentGatewayFactory;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Payment Gateway Domain Model.
 *
 * Represents a payment gateway configuration with encrypted sensitive data,
 * provider-specific settings, and validation methods for payment processing.
 *
 * @property int $id
 * @property string $name
 * @property string $provider
 * @property bool $is_active
 * @property string|null $api_key
 * @property string|null $webhook_secret
 * @property array<string, mixed>|null $settings
 * @property int $priority
 * @property float|string $min_amount
 * @property float|string $max_amount
 * @property bool $test_mode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|PaymentGateway where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static static|null find($id, $columns = ['*'])
 * @method static static findOrFail($id, $columns = ['*'])
 * @method static Collection<int, static> all($columns = ['*'])
 * @method static int count($columns = '*')
 * @method static Builder<static>|PaymentGateway newModelQuery()
 * @method static Builder<static>|PaymentGateway newQuery()
 * @method static Builder<static>|PaymentGateway query()
 * @method static Builder<static>|PaymentGateway active()
 * @method static Builder<static>|PaymentGateway configured()
 * @method static Builder<static>|PaymentGateway byProvider(string $provider)
 * @method static Builder<static>|PaymentGateway byPriority()
 * @method static Builder<static>|PaymentGateway whereApiKey($value)
 * @method static Builder<static>|PaymentGateway whereCreatedAt($value)
 * @method static Builder<static>|PaymentGateway whereId($value)
 * @method static Builder<static>|PaymentGateway whereIsActive($value)
 * @method static Builder<static>|PaymentGateway whereMaxAmount($value)
 * @method static Builder<static>|PaymentGateway whereMinAmount($value)
 * @method static Builder<static>|PaymentGateway whereName($value)
 * @method static Builder<static>|PaymentGateway wherePriority($value)
 * @method static Builder<static>|PaymentGateway whereProvider($value)
 * @method static Builder<static>|PaymentGateway whereSettings($value)
 * @method static Builder<static>|PaymentGateway whereTestMode($value)
 * @method static Builder<static>|PaymentGateway whereUpdatedAt($value)
 * @method static Builder<static>|PaymentGateway whereWebhookSecret($value)
 *
 * @mixin Model
 */
class PaymentGateway extends Model implements Auditable
{
    use AuditableTrait;

    /** @use HasFactory<PaymentGatewayFactory> */
    use HasFactory;

    use SoftDeletes;

    /** @var string */
    public const PROVIDER_MOLLIE = 'mollie';

    /** @var string */
    public const PROVIDER_STRIPE = 'stripe';

    /** @var string */
    public const PROVIDER_PAYPAL = 'paypal';

    /** @var array<int, string> */
    public const PROVIDERS = [
        self::PROVIDER_MOLLIE,
        self::PROVIDER_STRIPE,
        self::PROVIDER_PAYPAL,
    ];

    protected $fillable = [
        'name',
        'provider',
        'is_active',
        'api_key',
        'webhook_secret',
        'settings',
        'priority',
        'min_amount',
        'max_amount',
        'test_mode',
    ];

    /**
     * Activate the payment gateway.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the payment gateway.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Detect test mode from API key.
     */
    public function detectTestMode(): ?bool
    {
        if (empty($this->api_key)) {
            return null;
        }

        return PaymentGatewayConfigRegistry::detectTestMode($this->provider, $this->api_key);
    }

    /**
     * Check if the payment gateway is properly configured.
     */
    public function isConfigured(): bool
    {
        return match ($this->provider) {
            self::PROVIDER_MOLLIE => $this->isMollieConfigured(),
            self::PROVIDER_STRIPE => $this->isStripeConfigured(),
            self::PROVIDER_PAYPAL => $this->isPayPalConfigured(),
            default => false,
        };
    }

    /**
     * Check if this gateway can process a payment of the given amount and currency.
     */
    public function canProcessPayment(float $amount, string $currency): bool
    {
        if (! $this->is_active || ! $this->isConfigured()) {
            return false;
        }

        if (! $this->supportsCurrency($currency)) {
            return false;
        }

        $minAmount = (float) $this->min_amount;
        $maxAmount = (float) $this->max_amount;

        return $amount >= $minAmount && $amount <= $maxAmount;
    }

    /**
     * Check if this gateway supports a specific payment method.
     */
    public function supportsMethod(string $method): bool
    {
        if (! $this->is_active || ! $this->isConfigured()) {
            return false;
        }

        $settings = $this->settings;

        if (empty($settings)) {
            return false;
        }

        /** @var array<int, string> $supportedMethods */
        $supportedMethods = $settings['supported_methods'] ?? [];

        return in_array($method, $supportedMethods, true);
    }

    /**
     * Check if this gateway supports a specific currency.
     */
    public function supportsCurrency(string $currency): bool
    {
        return $this->currencies()->where('code', $currency)->exists();
    }

    /**
     * Define the many-to-many relationship with currencies.
     */
    public function currencies(): BelongsToMany // @phpstan-ignore-line
    {
        return $this->belongsToMany(Currency::class, 'currency_payment_gateway', 'payment_gateway_id', 'currency_id')
            ->withTimestamps()
            ->withPivot(['min_amount', 'max_amount', 'transaction_fee', 'is_active']);
    }

    /**
     * Scope query to active gateways.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to configured gateways.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeConfigured(Builder $query): Builder
    {
        // This needs to check provider-specific requirements
        // For now, just check that api_key is not null as minimum requirement
        return $query->whereNotNull('api_key');
    }

    /**
     * Scope query by provider.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope query ordered by priority (highest first).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PaymentGatewayFactory
    {
        return PaymentGatewayFactory::new();
    }

    /**
     * Get the API key for the gateway.
     */
    /**
     * @return Attribute<?string, ?string>
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return decrypt($value);
                } catch (Exception) {
                    return null;
                }
            },
            set: function (?string $value): ?string {
                if ($value === null || $value === '' || $value === '0') {
                    return null;
                }

                return encrypt($value);
            },
        );
    }

    /**
     * Get the webhook secret for the gateway.
     */
    /**
     * @return Attribute<?string, ?string>
     */
    protected function webhookSecret(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                try {
                    return decrypt($value);
                } catch (Exception) {
                    return null;
                }
            },
            set: function (?string $value): ?string {
                if ($value === null || $value === '' || $value === '0') {
                    return null;
                }

                return encrypt($value);
            },
        );
    }

    /**
     * Get the settings for the gateway.
     *
     * @return Attribute<array<string, mixed>, array<string, mixed>|null>
     */
    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): array {
                if ($value === null || $value === '') {
                    return [];
                }

                try {
                    $decrypted = decrypt($value);

                    /** @var array<string, mixed> $decoded */
                    $decoded = json_decode($decrypted, true) ?? [];

                    return is_array($decrypted) ? $decrypted : $decoded;
                } catch (Exception) {
                    return [];
                }
            },
            set: function (?array $value): ?string {
                if ($value === null || ($value === [])) {
                    return null;
                }

                $jsonValue = json_encode($value);

                return encrypt($jsonValue);
            },
        );
    }

    /**
     * Boot method to set up model event listeners.
     */
    protected static function booted(): void
    {
        self::creating(function (self $gateway): void {
            // Ensure provider is valid
            if (! in_array($gateway->provider, self::PROVIDERS, true)) {
                throw new InvalidArgumentException('Invalid payment gateway provider: ' . $gateway->provider);
            }

            // Set default values
            $gateway->is_active ??= false;
            $gateway->test_mode ??= true;
            $gateway->priority ??= 0;
            $gateway->min_amount ??= '1.00';
            $gateway->max_amount ??= '10000.00';
        });

        self::updating(function (self $gateway): void {
            // Validate provider if being changed
            if ($gateway->isDirty('provider') && ! in_array($gateway->provider, self::PROVIDERS, true)) {
                throw new InvalidArgumentException('Invalid payment gateway provider: ' . $gateway->provider);
            }
        });

        // Handle empty strings for encrypted fields
        self::saving(function (self $gateway): void {
            // Convert empty strings to null for encrypted fields to prevent decryption errors
            if ($gateway->api_key === '' || $gateway->api_key === '0') {
                $gateway->api_key = null;
            }

            if ($gateway->webhook_secret === '' || $gateway->webhook_secret === '0') {
                $gateway->webhook_secret = null;
            }
        });
    }

    /**
     * Check if Mollie is properly configured.
     */
    private function isMollieConfigured(): bool
    {
        // Mollie only requires API key
        return ! empty($this->api_key);
    }

    /**
     * Check if Stripe is properly configured.
     */
    private function isStripeConfigured(): bool
    {
        // Stripe requires API key and webhook secret
        return ! empty($this->api_key) && ! empty($this->webhook_secret);
    }

    /**
     * Check if PayPal is properly configured.
     */
    private function isPayPalConfigured(): bool
    {
        $settings = $this->settings;

        if (empty($settings)) {
            return false;
        }

        // PayPal requires client_id and client_secret (stored in settings)
        return isset($settings['client_id']) &&
               ! empty($settings['client_id']) &&
               ! empty($this->api_key); // api_key stores client_secret for PayPal
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'test_mode' => 'boolean',
        ];
    }
}
