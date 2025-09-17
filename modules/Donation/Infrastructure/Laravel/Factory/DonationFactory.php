<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Modules\Shared\Infrastructure\Laravel\Traits\SafeDateGeneration;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
{
    use SafeDateGeneration;

    protected $model = Donation::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5.00, 2500.00);
        $status = fake()->randomElement([
            DonationStatus::PENDING,
            DonationStatus::PROCESSING,
            DonationStatus::COMPLETED,
            DonationStatus::FAILED,
            DonationStatus::REFUNDED,
            DonationStatus::CANCELLED,
        ]);

        $isAnonymous = fake()->boolean(15); // 15% chance of anonymous donation
        $createdAt = fake()->dateTimeBetween('-1 year', '-1 day');

        return [
            'amount' => $amount,
            'currency' => fake()->randomElement(['EUR', 'USD', 'GBP']),
            'status' => $status->value,
            'payment_method' => fake()->randomElement(['card', 'bank_transfer', 'paypal', 'stripe']),
            'gateway_response_id' => fake()->optional(0.8)->uuid(),
            'transaction_id' => $this->generateTransactionId(),
            'user_id' => $isAnonymous ? null : User::factory(),
            'campaign_id' => Campaign::factory(),
            'anonymous' => $isAnonymous,
            'recurring' => fake()->boolean(10), // 10% chance of recurring
            'recurring_frequency' => fake()->boolean(10) ? fake()->randomElement(['monthly', 'quarterly', 'yearly']) : null,
            // Remove fields that don't exist in schema
            'notes' => fake()->optional(0.3)->sentence(),
            'metadata' => $this->generateMetadata(),
            'processed_at' => in_array($status, [DonationStatus::COMPLETED, DonationStatus::REFUNDED], true)
                ? $this->safeDateTimeBetween($createdAt, 'now')
                : null,
            'donated_at' => $this->safeDateTimeBetween($createdAt, 'now'),
            'created_at' => $createdAt,
            'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DonationStatus::COMPLETED->value,
            'processed_at' => $this->safeDateTimeBetween('-6 months', 'now'),
            'donated_at' => $this->safeDateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DonationStatus::PENDING->value,
            'processed_at' => null,
            'donated_at' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DonationStatus::PROCESSING->value,
            'processed_at' => null,
            'donated_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DonationStatus::FAILED->value,
            'processed_at' => null,
            'donated_at' => null,
            'failure_reason' => fake()->randomElement([
                'insufficient_funds',
                'card_declined',
                'payment_timeout',
                'invalid_payment_method',
                'gateway_error',
            ]),
        ]);
    }

    public function refunded(): static
    {
        $processedAt = $this->safeDateTimeBetween('-6 months', '-1 month');

        return $this->state(fn (array $attributes): array => [
            'status' => DonationStatus::REFUNDED->value,
            'processed_at' => $processedAt,
            'donated_at' => $processedAt,
            'refunded_at' => $this->safeDateTimeBetween($processedAt, 'now'),
            'refund_reason' => fake()->randomElement([
                'donor_request',
                'fraudulent_transaction',
                'duplicate_payment',
                'campaign_cancelled',
                'processing_error',
            ]),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => DonationStatus::CANCELLED->value,
            'processed_at' => null,
            'donated_at' => null,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => [
            'anonymous' => true,
            'user_id' => null,
            // Remove field that doesn't exist in schema
        ]);
    }

    public function withEmployee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'anonymous' => false,
            'user_id' => User::factory(),
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes): array => [
            'recurring' => true,
            'recurring_frequency' => fake()->randomElement(['monthly', 'quarterly', 'yearly']),
        ]);
    }

    public function withCorporateMatching(): static
    {
        return $this->state(fn (array $attributes): array =>
            // Corporate matching feature not yet implemented in schema
            [
                'notes' => 'Corporate matching eligible donation',
            ]);
    }

    public function creditCard(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => 'card',
            'payment_gateway' => 'stripe',
        ]);
    }

    public function paypal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => 'paypal',
            'payment_gateway' => 'paypal',
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_method' => 'bank_transfer',
            'payment_gateway' => null,
        ]);
    }

    public function mediumDonation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount' => fake()->randomFloat(2, 50, 500),
        ]);
    }

    public function withNotes(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notes' => fake()->sentences(3, true),
            'notes_translations' => [
                'en' => fake()->sentences(3, true),
                'nl' => 'Nederlandse notitie: ' . fake()->sentence(),
                'fr' => 'Note française: ' . fake()->sentence(),
            ],
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'donated_at' => $this->safeDateTimeBetween('-1 week', 'now'),
            'created_at' => $this->safeDateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes): array => [
            'donated_at' => $this->safeDateTimeBetween('-1 year', '-2 months'),
            'created_at' => $this->safeDateTimeBetween('-1 year', '-2 months'),
        ]);
    }

    public function largeDonation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount' => fake()->randomFloat(2, 1000, 10000),
        ]);
    }

    public function smallDonation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount' => fake()->randomFloat(2, 5, 50),
        ]);
    }

    public function majorAmount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount' => fake()->randomFloat(2, 5000, 25000),
        ]);
    }

    public function smallAmount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'amount' => fake()->randomFloat(2, 5, 25),
        ]);
    }

    public function withMessage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'notes' => fake()->sentence(),
        ]);
    }

    private function generateTransactionId(): string
    {
        $prefixes = ['TXN', 'PAY', 'DON', 'STR', 'MOL'];
        $prefix = fake()->randomElement($prefixes);
        $id = fake()->numerify('########');
        $suffix = strtoupper(fake()->randomLetter() . fake()->randomLetter());

        return "{$prefix}_{$id}_{$suffix}";
    }

    /**
     * @return array<string, mixed>
     */
    private function generateMetadata(): array
    {
        $metadata = [
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->optional(0.7)->url(),
        ];

        // Randomly add additional metadata
        if (fake()->boolean(30)) {
            $metadata['utm_source'] = fake()->randomElement(['google', 'facebook', 'email', 'direct']);
            $metadata['utm_medium'] = fake()->randomElement(['cpc', 'social', 'email', 'organic']);
            $metadata['utm_campaign'] = fake()->randomElement(['donation-drive', 'social-campaign', 'newsletter']);
        }

        if (fake()->boolean(20)) {
            $metadata['device_type'] = fake()->randomElement(['desktop', 'mobile', 'tablet']);
            $metadata['browser'] = fake()->randomElement(['chrome', 'firefox', 'safari', 'edge']);
        }

        return $metadata;
    }

    // safeDateTimeBetween method is now provided by SafeDateGeneration trait
}
