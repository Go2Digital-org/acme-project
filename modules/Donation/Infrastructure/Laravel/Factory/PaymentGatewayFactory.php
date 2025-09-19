<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Donation\Domain\Model\PaymentGateway;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(['stripe', 'mollie', 'paypal']);
        $testMode = fake()->boolean(70); // 70% chance of being in test mode

        return [
            'name' => fake()->company() . ' ' . ucfirst((string) $provider) . ' Gateway',
            'provider' => $provider,
            'is_active' => fake()->boolean(80), // 80% chance of being active
            'test_mode' => $testMode,
            'api_key' => $this->generateApiKey($provider, $testMode),
            'webhook_secret' => $this->generateWebhookSecret($provider),
            'settings' => $this->generateSettings($provider),
            'min_amount' => fake()->randomFloat(2, 1.00, 5.00), // 1.00 to 5.00
            'max_amount' => fake()->randomFloat(2, 1000.00, 50000.00), // 1000 to 50000
            'priority' => fake()->numberBetween(0, 20),
        ];
    }

    public function stripe(): static
    {
        return $this->state(function (array $attributes): array {
            $testMode = $attributes['test_mode'] ?? true;

            return [
                'provider' => 'stripe',
                'name' => fake()->company() . ' Stripe Gateway',
                'api_key' => $this->generateApiKey('stripe', $testMode),
                'webhook_secret' => 'whsec_' . fake()->regexify('[A-Za-z0-9]{40}'),
                'settings' => [
                    'publishable_key' => 'pk_' . ($testMode ? 'test' : 'live') . '_' . fake()->regexify('[A-Za-z0-9]{24}'),
                    'webhook_endpoint_secret' => 'whsec_' . fake()->regexify('[A-Za-z0-9]{40}'),
                    'supported_methods' => ['card', 'bank_transfer'],
                ],
            ];
        });
    }

    public function mollie(): static
    {
        return $this->state(function (array $attributes): array {
            $testMode = $attributes['test_mode'] ?? true;

            return [
                'provider' => 'mollie',
                'name' => fake()->company() . ' Mollie Gateway',
                'api_key' => $this->generateApiKey('mollie', $testMode),
                'webhook_secret' => null, // Mollie doesn't use webhook secrets
                'settings' => [
                    'profile_id' => 'pfl_' . fake()->regexify('[A-Za-z0-9]{10}'),
                    'webhook_url' => fake()->url() . '/webhooks/mollie',
                    'supported_methods' => ['creditcard', 'ideal', 'banktransfer'],
                ],
            ];
        });
    }

    public function paypal(): static
    {
        return $this->state(function (array $attributes): array {
            $testMode = $attributes['test_mode'] ?? true;

            return [
                'provider' => 'paypal',
                'name' => fake()->company() . ' PayPal Gateway',
                'api_key' => $this->generateApiKey('paypal', $testMode), // client_secret
                'webhook_secret' => 'webhook_id_' . fake()->regexify('[A-Za-z0-9]{20}'),
                'settings' => [
                    'client_id' => ($testMode ? 'sb-' : '') . fake()->regexify('[A-Za-z0-9]{80}'),
                    'webhook_id' => 'webhook_id_' . fake()->regexify('[A-Za-z0-9]{20}'),
                    'supported_methods' => ['paypal', 'credit_card'],
                ],
            ];
        });
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function testMode(): static
    {
        return $this->state(['test_mode' => true]);
    }

    public function liveMode(): static
    {
        return $this->state(['test_mode' => false]);
    }

    public function configured(): static
    {
        return $this->state(function (array $attributes): array {
            $provider = $attributes['provider'] ?? 'stripe';
            $testMode = $attributes['test_mode'] ?? true;

            return [
                'api_key' => $this->generateApiKey($provider, $testMode),
                'webhook_secret' => $this->generateWebhookSecret($provider),
                'settings' => $this->generateSettings($provider),
            ];
        });
    }

    public function incomplete(): static
    {
        return $this->state([
            'api_key' => null,
            'webhook_secret' => null,
            'settings' => null,
        ]);
    }

    public function withPriority(int $priority): static
    {
        return $this->state(['priority' => $priority]);
    }

    public function withAmountLimits(float $min, float $max): static
    {
        return $this->state([
            'min_amount' => $min,
            'max_amount' => $max,
        ]);
    }

    private function generateApiKey(string $provider, bool $testMode = true): string
    {
        return match ($provider) {
            'stripe' => 'sk_' . ($testMode ? 'test' : 'live') . '_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'mollie' => ($testMode ? 'test' : 'live') . '_' . fake()->regexify('[A-Za-z0-9]{30}'),
            'paypal' => ($testMode ? 'sb-' : '') . fake()->regexify('[A-Za-z0-9]{80}'), // client_secret
            default => 'key_' . fake()->regexify('[A-Za-z0-9]{32}'),
        };
    }

    private function generateWebhookSecret(string $provider): ?string
    {
        return match ($provider) {
            'stripe' => 'whsec_' . fake()->regexify('[A-Za-z0-9]{40}'),
            'mollie' => null, // Mollie doesn't use webhook secrets
            'paypal' => 'webhook_id_' . fake()->regexify('[A-Za-z0-9]{20}'),
            default => 'whsec_' . fake()->regexify('[A-Za-z0-9]{32}'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSettings(string $provider): array
    {
        return match ($provider) {
            'stripe' => [
                'publishable_key' => 'pk_test_' . fake()->regexify('[A-Za-z0-9]{24}'),
                'webhook_endpoint_secret' => 'whsec_' . fake()->regexify('[A-Za-z0-9]{40}'),
                'supported_methods' => ['card', 'bank_transfer'],
            ],
            'mollie' => [
                'profile_id' => 'pfl_' . fake()->regexify('[A-Za-z0-9]{10}'),
                'webhook_url' => fake()->url() . '/webhooks/mollie',
                'supported_methods' => ['creditcard', 'ideal', 'banktransfer'],
            ],
            'paypal' => [
                'client_id' => 'sb-' . fake()->regexify('[A-Za-z0-9]{80}'),
                'webhook_id' => 'webhook_id_' . fake()->regexify('[A-Za-z0-9]{20}'),
                'supported_methods' => ['paypal', 'credit_card'],
            ],
            default => [],
        };
    }
}
