<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Seeder;

use DB;
use Illuminate\Database\Seeder;
use Modules\Donation\Domain\Model\PaymentGateway;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Seed the payment gateway configurations.
     */
    public function run(): void
    {
        $this->command->info('Seeding payment gateway configurations...');

        // Seed Mollie configuration (Primary gateway - Priority 1)
        $this->seedMollie();

        // Seed Stripe configuration (Secondary gateway - Priority 2)
        $this->seedStripe();

        $this->command->info('Payment gateway configurations seeded successfully');
    }

    /**
     * Seed Mollie payment gateway configuration.
     */
    private function seedMollie(): void
    {
        if (DB::table('payment_gateways')->where('provider', PaymentGateway::PROVIDER_MOLLIE)->exists()) {
            $this->command->warn('Mollie payment gateway already exists, skipping...');

            return;
        }

        PaymentGateway::create([
            'name' => 'Mollie Payment Gateway',
            'provider' => PaymentGateway::PROVIDER_MOLLIE,
            'is_active' => false, // Requires manual activation
            'api_key' => config('payment.gateways.mollie.api_key') ?? null, // Use config value if available
            'webhook_secret' => null, // Will be set manually through admin panel
            'priority' => 1, // Primary gateway
            'min_amount' => 0.01,
            'max_amount' => 50000.00,
            'test_mode' => config('payment.gateways.mollie.test_mode', true),
            'settings' => [
                'webhook_url' => config('payment.gateways.mollie.webhook_url', url('/webhooks/mollie')),
                'description_prefix' => config('payment.gateways.mollie.description_prefix', 'ACME Corp Donation'),
                'supported_methods' => [
                    'creditcard',
                    'ideal',
                    'paypal',
                    'bancontact',
                    'sofort',
                    'giropay',
                    'eps',
                    'kbc',
                    'belfius',
                    'applepay',
                    'przelewy24',
                    'banktransfer',
                ],
                'locale' => 'en_US',
                'method_restrictions' => [],
                'metadata' => [
                    'platform' => 'ACME Corp CSR Platform',
                    'version' => '1.0',
                ],
            ],
        ]);

        $this->command->info('Mollie payment gateway configuration created');
    }

    /**
     * Seed Stripe payment gateway configuration.
     */
    private function seedStripe(): void
    {
        if (DB::table('payment_gateways')->where('provider', PaymentGateway::PROVIDER_STRIPE)->exists()) {
            $this->command->warn('Stripe payment gateway already exists, skipping...');

            return;
        }

        PaymentGateway::create([
            'name' => 'Stripe Payment Gateway',
            'provider' => PaymentGateway::PROVIDER_STRIPE,
            'is_active' => false, // Requires manual activation
            'api_key' => config('payment.gateways.stripe.secret_key') ?? null, // Use config value if available
            'webhook_secret' => config('payment.gateways.stripe.webhook_secret') ?? null,
            'priority' => 2, // Secondary gateway
            'min_amount' => 0.50,
            'max_amount' => 999999.99,
            'test_mode' => config('payment.gateways.stripe.test_mode', true),
            'settings' => [
                'publishable_key' => config('payment.gateways.stripe.public_key', null),
                'webhook_url' => url('/webhooks/stripe'),
                'supported_methods' => [
                    'card',
                    'apple_pay',
                    'google_pay',
                    'link',
                    'us_bank_account',
                    'sepa_debit',
                    'ideal',
                    'sofort',
                    'giropay',
                    'eps',
                    'p24',
                    'bancontact',
                    'alipay',
                    'wechat_pay',
                ],
                'capture_method' => 'automatic',
                'payment_method_types' => ['card'],
                'statement_descriptor' => 'ACME CORP CSR',
                'metadata' => [
                    'platform' => 'ACME Corp CSR Platform',
                    'version' => '1.0',
                ],
            ],
        ]);

        $this->command->info('Stripe payment gateway configuration created');
    }
}
