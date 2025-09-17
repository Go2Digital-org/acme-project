<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Seeder;

use DB;
use Illuminate\Database\Seeder;
use Modules\Donation\Domain\Model\PaymentGateway;

/**
 * Default Payment Gateway Seeder for Tenants.
 *
 * Seeds the default payment gateway configurations for new tenant organizations.
 * These need to be configured with actual API keys by each tenant after provisioning.
 */
class DefaultPaymentGatewaySeeder extends Seeder
{
    /**
     * Seed the default payment gateway configurations.
     */
    public function run(): void
    {
        if ($this->command) {
            $this->command->info('Seeding default payment gateway configurations for tenant...');
        }

        // Seed Mollie configuration (Primary gateway - Priority 1)
        $this->seedMollie();

        // Seed Stripe configuration (Secondary gateway - Priority 2)
        $this->seedStripe();

        if ($this->command) {
            $this->command->info('Successfully seeded payment gateway configurations');
        }
    }

    /**
     * Seed Mollie payment gateway configuration.
     */
    private function seedMollie(): void
    {
        if (DB::table('payment_gateways')->where('provider', PaymentGateway::PROVIDER_MOLLIE)->exists()) {
            if ($this->command) {
                $this->command->warn('Mollie payment gateway already exists, skipping...');
            }

            return;
        }

        PaymentGateway::create([
            'name' => 'Mollie Payment Gateway',
            'provider' => PaymentGateway::PROVIDER_MOLLIE,
            'is_active' => false, // Requires manual activation by tenant
            'api_key' => null, // Tenant needs to add their own API key
            'webhook_secret' => null, // Will be set manually through admin panel
            'priority' => 1, // Primary gateway
            'min_amount' => 0.01,
            'max_amount' => 50000.00,
            'test_mode' => true, // Default to test mode for safety
            'settings' => [
                'webhook_url' => url('/webhooks/mollie'),
                'description_prefix' => 'Donation',
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
                    'platform' => 'CSR Platform',
                    'version' => '1.0',
                ],
            ],
        ]);

        if ($this->command) {
            $this->command->info('Mollie payment gateway configuration created');
        }
    }

    /**
     * Seed Stripe payment gateway configuration.
     */
    private function seedStripe(): void
    {
        if (DB::table('payment_gateways')->where('provider', PaymentGateway::PROVIDER_STRIPE)->exists()) {
            if ($this->command) {
                $this->command->warn('Stripe payment gateway already exists, skipping...');
            }

            return;
        }

        PaymentGateway::create([
            'name' => 'Stripe Payment Gateway',
            'provider' => PaymentGateway::PROVIDER_STRIPE,
            'is_active' => false, // Requires manual activation by tenant
            'api_key' => null, // Tenant needs to add their own API key
            'webhook_secret' => null, // Tenant needs to add their own webhook secret
            'priority' => 2, // Secondary gateway
            'min_amount' => 0.50,
            'max_amount' => 999999.99,
            'test_mode' => true, // Default to test mode for safety
            'settings' => [
                'publishable_key' => null, // Tenant needs to add their own publishable key
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
                'statement_descriptor' => 'DONATION',
                'metadata' => [
                    'platform' => 'CSR Platform',
                    'version' => '1.0',
                ],
            ],
        ]);

        if ($this->command) {
            $this->command->info('Stripe payment gateway configuration created');
        }
    }
}
