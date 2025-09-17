<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Model\Payment;
use Modules\Donation\Domain\ValueObject\PaymentMethod;
use Modules\Donation\Domain\ValueObject\PaymentStatus;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5, 1000);
        $status = $this->generateRealisticPaymentStatus();
        $method = fake()->randomElement(PaymentMethod::cases());
        $gatewayName = $this->getGatewayName($method);

        return [
            'donation_id' => Donation::factory(),
            'gateway_name' => $gatewayName,
            'intent_id' => $this->generateIntentId($gatewayName),
            'transaction_id' => $status === PaymentStatus::COMPLETED ?
                $this->generateTransactionId($gatewayName) : null,
            'amount' => $amount,
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'payment_method' => $method->value,
            'status' => $status->value,
            'gateway_customer_id' => fake()->optional()->regexify('[a-z]{4}_[a-zA-Z0-9]{24}'),
            'gateway_payment_method_id' => fake()->optional()->regexify('pm_[a-zA-Z0-9]{24}'),
            'failure_code' => $status === PaymentStatus::FAILED ?
                fake()->randomElement(['card_declined', 'insufficient_funds', 'expired_card']) : null,
            'failure_message' => $status === PaymentStatus::FAILED ?
                $this->generateFailureMessage() : null,
            'decline_code' => $status === PaymentStatus::FAILED ?
                fake()->optional()->randomElement(['generic_decline', 'insufficient_funds', 'lost_card']) : null,
            'gateway_data' => $this->generateGatewayData($gatewayName, $status),
            'metadata' => fake()->optional()->randomElement([
                ['source' => 'web', 'campaign_id' => fake()->numberBetween(1, 100)],
                ['source' => 'mobile', 'version' => '1.2.3'],
                null,
            ]),
            'authorized_at' => $status->value !== 'pending' ?
                fake()->dateTimeBetween('-1 year', 'now') : null,
            'captured_at' => $status === PaymentStatus::COMPLETED ?
                fake()->dateTimeBetween('-1 year', 'now') : null,
            'failed_at' => $status === PaymentStatus::FAILED ?
                fake()->dateTimeBetween('-1 year', 'now') : null,
            'cancelled_at' => $status === PaymentStatus::CANCELLED ?
                fake()->dateTimeBetween('-1 year', 'now') : null,
            'expires_at' => $status === PaymentStatus::PENDING ?
                fake()->dateTimeBetween('now', '+2 hours') : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function successful(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::COMPLETED->value,
            'transaction_id' => $this->generateTransactionId($attributes['gateway_name']),
            'authorized_at' => fake()->dateTimeBetween('-11 months', '-1 hour'),
            'captured_at' => fake()->dateTimeBetween('-11 months', 'now'),
            'failed_at' => null,
            'cancelled_at' => null,
            'failure_code' => null,
            'failure_message' => null,
            'expires_at' => null,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::FAILED->value,
            'transaction_id' => null,
            'authorized_at' => fake()->dateTimeBetween('-11 months', '-1 hour'),
            'captured_at' => null,
            'failed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'cancelled_at' => null,
            'failure_code' => fake()->randomElement(['card_declined', 'insufficient_funds', 'expired_card']),
            'failure_message' => $this->generateFailureMessage(),
            'expires_at' => null,
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::PENDING->value,
            'transaction_id' => null,
            'authorized_at' => null,
            'captured_at' => null,
            'failed_at' => null,
            'cancelled_at' => null,
            'failure_code' => null,
            'failure_message' => null,
            'expires_at' => fake()->dateTimeBetween('now', '+2 hours'),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::CANCELLED->value,
            'transaction_id' => null,
            'authorized_at' => fake()->optional()->dateTimeBetween('-1 year', '-1 hour'),
            'captured_at' => null,
            'failed_at' => null,
            'cancelled_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'failure_code' => null,
            'failure_message' => 'Payment cancelled by user',
            'expires_at' => null,
        ]);
    }

    public function processing(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::PROCESSING->value,
            'authorized_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'captured_at' => null,
            'failed_at' => null,
            'cancelled_at' => null,
            'failure_code' => null,
            'failure_message' => null,
            'expires_at' => null,
        ]);
    }

    public function stripe(): self
    {
        return $this->state(fn (array $attributes): array => [
            'gateway_name' => 'stripe',
            'intent_id' => 'pi_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            'payment_method' => PaymentMethod::STRIPE->value,
            'gateway_customer_id' => 'cus_' . fake()->regexify('[a-zA-Z0-9]{14}'),
            'gateway_payment_method_id' => 'pm_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            'gateway_data' => $this->generateStripeData(),
        ]);
    }

    public function paypal(): self
    {
        return $this->state(fn (array $attributes): array => [
            'gateway_name' => 'paypal',
            'intent_id' => fake()->regexify('[A-Z0-9]{17}'),
            'payment_method' => PaymentMethod::PAYPAL->value,
            'gateway_data' => $this->generatePaypalData(),
        ]);
    }

    public function ideal(): self
    {
        return $this->state(fn (array $attributes): array => [
            'gateway_name' => 'mollie',
            'intent_id' => 'tr_' . fake()->regexify('[a-zA-Z0-9]{10}'),
            'payment_method' => PaymentMethod::IDEAL->value,
            'gateway_data' => $this->generateMollieData(),
        ]);
    }

    public function recent(): self
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'updated_at' => fn ($attrs) => $attrs['created_at'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function withMetadata(array $metadata): self
    {
        return $this->state(fn (array $attributes): array => [
            'metadata' => $metadata,
        ]);
    }

    private function generateRealisticPaymentStatus(): PaymentStatus
    {
        // Realistic distribution - most payments succeed
        $rand = fake()->numberBetween(1, 100);

        return match (true) {
            $rand <= 85 => PaymentStatus::COMPLETED,   // 85% successful
            $rand <= 95 => PaymentStatus::FAILED,      // 10% failed
            $rand <= 98 => PaymentStatus::CANCELLED,   // 3% cancelled
            default => PaymentStatus::PENDING,         // 2% pending
        };
    }

    private function getGatewayName(PaymentMethod $method): string
    {
        return match ($method) {
            PaymentMethod::STRIPE => 'stripe',
            PaymentMethod::PAYPAL => 'paypal',
            PaymentMethod::IDEAL, PaymentMethod::BANCONTACT, PaymentMethod::SOFORT, PaymentMethod::CARD => 'mollie',
            default => 'stripe', // Default to stripe
        };
    }

    private function generateIntentId(string $gateway): string
    {
        return match ($gateway) {
            'stripe' => 'pi_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            'paypal' => fake()->regexify('[A-Z0-9]{17}'),
            'mollie' => 'tr_' . fake()->regexify('[a-zA-Z0-9]{10}'),
            default => fake()->regexify('[a-zA-Z0-9]{12}'),
        };
    }

    private function generateTransactionId(string $gateway): string
    {
        return match ($gateway) {
            'stripe' => 'txn_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            'paypal' => fake()->regexify('[A-Z0-9]{17}'),
            'mollie' => 'tr_' . fake()->regexify('[a-zA-Z0-9]{10}'),
            default => fake()->regexify('[a-zA-Z0-9]{16}'),
        };
    }

    private function generateFailureMessage(): string
    {
        $messages = [
            'Your card was declined.',
            'Insufficient funds in your account.',
            'Your card has expired.',
            'The CVC number is incorrect.',
            'Your card does not support this type of purchase.',
            'Your card was declined by your bank.',
            'There was a processing error. Please try again.',
            'Payment method not supported.',
            'Transaction limit exceeded.',
            'Card blocked by issuer.',
        ];

        return fake()->randomElement($messages);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateGatewayData(string $gateway, PaymentStatus $status): array
    {
        return match ($gateway) {
            'stripe' => $this->generateStripeData($status),
            'paypal' => $this->generatePaypalData($status),
            'mollie' => $this->generateMollieData($status),
            default => ['status' => $status->value],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function generateStripeData(?PaymentStatus $status = null): array
    {
        $stripeStatus = match ($status) {
            PaymentStatus::COMPLETED => 'succeeded',
            PaymentStatus::FAILED => 'failed',
            PaymentStatus::CANCELLED => 'canceled',
            PaymentStatus::PROCESSING => 'processing',
            PaymentStatus::PENDING => 'requires_payment_method',
            default => 'succeeded',
        };

        return [
            'id' => 'pi_' . fake()->regexify('[a-zA-Z0-9]{24}'),
            'object' => 'payment_intent',
            'status' => $stripeStatus,
            'amount' => fake()->numberBetween(500, 100000),
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'created' => fake()->unixTime(),
            'livemode' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generatePaypalData(?PaymentStatus $status = null): array
    {
        $paypalStatus = match ($status) {
            PaymentStatus::COMPLETED => 'COMPLETED',
            PaymentStatus::FAILED => 'FAILED',
            PaymentStatus::CANCELLED => 'CANCELLED',
            PaymentStatus::PROCESSING => 'PENDING',
            PaymentStatus::PENDING => 'CREATED',
            default => 'COMPLETED',
        };

        return [
            'id' => fake()->regexify('[A-Z0-9]{17}'),
            'status' => $paypalStatus,
            'intent' => 'CAPTURE',
            'create_time' => fake()->iso8601(),
            'update_time' => fake()->iso8601(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateMollieData(?PaymentStatus $status = null): array
    {
        $mollieStatus = match ($status) {
            PaymentStatus::COMPLETED => 'paid',
            PaymentStatus::FAILED => 'failed',
            PaymentStatus::CANCELLED => 'canceled',
            PaymentStatus::PROCESSING => 'pending',
            PaymentStatus::PENDING => 'open',
            default => 'paid',
        };

        return [
            'id' => 'tr_' . fake()->regexify('[a-zA-Z0-9]{10}'),
            'mode' => 'test',
            'status' => $mollieStatus,
            'method' => fake()->randomElement(['ideal', 'creditcard', 'bancontact', 'sofort']),
            'createdAt' => fake()->iso8601(),
            'profileId' => 'pfl_' . fake()->regexify('[a-zA-Z0-9]{10}'),
        ];
    }
}
