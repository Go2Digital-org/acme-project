<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Donation\Domain\Model\Payment;
use Modules\Donation\Domain\Model\PaymentAttempt;
use Modules\Donation\Domain\ValueObject\PaymentStatus;

/**
 * @extends Factory<PaymentAttempt>
 */
class PaymentAttemptFactory extends Factory
{
    protected $model = PaymentAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $attemptedAt = fake()->dateTimeBetween('-1 month', 'now');
        $responseTimeMs = fake()->numberBetween(50, 5000);

        return [
            'payment_id' => Payment::factory(),
            'attempt_number' => fake()->numberBetween(1, 3),
            'gateway_name' => fake()->randomElement(['stripe', 'paypal', 'mollie', 'adyen']),
            'gateway_action' => fake()->randomElement(['charge', 'authorize', 'capture', 'refund']),
            'gateway_request_id' => 'req_' . fake()->uuid(),
            'request_data' => $this->generateRequestData(),
            'response_data' => $this->generateResponseData(),
            'status' => fake()->randomElement([
                PaymentStatus::PENDING,
                PaymentStatus::COMPLETED,
                PaymentStatus::FAILED,
            ]),
            'error_code' => fake()->optional(0.3)->randomElement([
                'card_declined',
                'insufficient_funds',
                'payment_timeout',
                'gateway_error',
                'network_error',
            ]),
            'error_message' => fn (array $attributes): ?string => $attributes['error_code'] ? $this->generateErrorMessage($attributes['error_code']) : null,
            'response_time_ms' => $responseTimeMs,
            'gateway_transaction_id' => fake()->optional(0.8)->regexify('txn_[a-zA-Z0-9]{16}'),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'attempted_at' => $attemptedAt,
            'created_at' => $attemptedAt,
            'updated_at' => $attemptedAt,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::COMPLETED,
            'error_code' => null,
            'error_message' => null,
            'gateway_transaction_id' => 'txn_' . fake()->regexify('[a-zA-Z0-9]{16}'),
            'response_time_ms' => fake()->numberBetween(100, 2000),
        ]);
    }

    public function failed(): static
    {
        $errorCode = fake()->randomElement([
            'card_declined',
            'insufficient_funds',
            'payment_timeout',
            'invalid_card',
            'gateway_error',
        ]);

        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::FAILED,
            'error_code' => $errorCode,
            'error_message' => $this->generateErrorMessage($errorCode),
            'gateway_transaction_id' => null,
            'response_time_ms' => fake()->numberBetween(50, 1500),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::PENDING,
            'error_code' => null,
            'error_message' => null,
            'gateway_transaction_id' => null,
            'response_time_ms' => fake()->numberBetween(200, 1000),
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gateway_name' => 'stripe',
            'gateway_request_id' => 'req_' . fake()->regexify('[a-zA-Z0-9]{14}'),
            'gateway_transaction_id' => fake()->optional(0.8)->regexify('ch_[a-zA-Z0-9]{24}'),
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gateway_name' => 'paypal',
            'gateway_request_id' => fake()->uuid(),
            'gateway_transaction_id' => fake()->optional(0.8)->regexify('[A-Z0-9]{16}'),
        ]);
    }

    public function mollie(): static
    {
        return $this->state(fn (array $attributes): array => [
            'gateway_name' => 'mollie',
            'gateway_request_id' => 'tr_' . fake()->regexify('[a-zA-Z0-9]{10}'),
            'gateway_transaction_id' => fake()->optional(0.8)->regexify('tr_[a-zA-Z0-9]{10}'),
        ]);
    }

    public function firstAttempt(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt_number' => 1,
        ]);
    }

    public function retry(): static
    {
        return $this->state(fn (array $attributes): array => [
            'attempt_number' => fake()->numberBetween(2, 5),
        ]);
    }

    public function slow(): static
    {
        return $this->state(fn (array $attributes): array => [
            'response_time_ms' => fake()->numberBetween(3000, 10000),
        ]);
    }

    public function fast(): static
    {
        return $this->state(fn (array $attributes): array => [
            'response_time_ms' => fake()->numberBetween(50, 500),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateRequestData(): array
    {
        return [
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => fake()->randomElement(['EUR', 'USD', 'GBP']),
            'description' => 'Donation payment',
            'customer_id' => fake()->uuid(),
            'metadata' => [
                'campaign_id' => fake()->numberBetween(1, 1000),
                'user_id' => fake()->numberBetween(1, 1000),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateResponseData(): array
    {
        return [
            'id' => fake()->uuid(),
            'status' => fake()->randomElement(['pending', 'succeeded', 'failed']),
            'amount_received' => fake()->randomFloat(2, 10, 1000),
            'fee' => fake()->randomFloat(2, 0.30, 50),
            'net' => fake()->randomFloat(2, 10, 950),
        ];
    }

    private function generateErrorMessage(string $errorCode): string
    {
        $messages = [
            'card_declined' => 'Your card was declined.',
            'insufficient_funds' => 'Your card has insufficient funds.',
            'payment_timeout' => 'Payment request timed out.',
            'invalid_card' => 'Your card number is invalid.',
            'gateway_error' => 'Payment gateway is temporarily unavailable.',
            'network_error' => 'Network connection failed.',
        ];

        return $messages[$errorCode] ?? 'An unknown error occurred.';
    }
}
