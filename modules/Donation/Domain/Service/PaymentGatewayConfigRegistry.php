<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Service;

/**
 * Payment Gateway Configuration Registry.
 *
 * Centralizes configuration requirements for all payment providers.
 * Makes it easy to add new payment gateways by defining their requirements here.
 */
final class PaymentGatewayConfigRegistry
{
    /**
     * Gateway configuration definitions.
     *
     * @var array<string, array{
     *     required_fields: array<string>,
     *     optional_fields: array<string>,
     *     test_mode_detection: ?string,
     *     supports_webhook_secret: bool,
     *     api_key_field: string,
     *     display_name: string
     * }>
     */
    public const GATEWAY_CONFIGS = [
        'mollie' => [
            'required_fields' => ['api_key'],
            'optional_fields' => ['webhook_url', 'description_prefix'],
            'test_mode_detection' => 'test_',
            'supports_webhook_secret' => false,
            'api_key_field' => 'api_key',
            'display_name' => 'Mollie',
        ],
        'stripe' => [
            'required_fields' => ['api_key', 'webhook_secret'],
            'optional_fields' => ['publishable_key'],
            'test_mode_detection' => 'sk_test_',
            'supports_webhook_secret' => true,
            'api_key_field' => 'api_key',
            'display_name' => 'Stripe',
        ],
        'paypal' => [
            'required_fields' => ['client_id', 'client_secret'],
            'optional_fields' => ['webhook_id'],
            'test_mode_detection' => null, // PayPal uses separate endpoints
            'supports_webhook_secret' => true,
            'api_key_field' => 'client_secret', // PayPal's main secret
            'display_name' => 'PayPal',
        ],
    ];

    /**
     * Get configuration for a specific provider.
     *
     * @return array{
     *     required_fields: array<string>,
     *     optional_fields: array<string>,
     *     test_mode_detection: ?string,
     *     supports_webhook_secret: bool,
     *     api_key_field: string,
     *     display_name: string
     * }|null
     */
    public static function getProviderConfig(string $provider): ?array
    {
        return self::GATEWAY_CONFIGS[$provider] ?? null;
    }

    /**
     * Get all available provider names.
     *
     * @return array<string>
     */
    public static function getAvailableProviders(): array
    {
        return array_keys(self::GATEWAY_CONFIGS);
    }

    /**
     * Check if provider requires webhook secret.
     */
    public static function requiresWebhookSecret(string $provider): bool
    {
        $config = self::getProviderConfig($provider);

        if ($config === null) {
            return false;
        }

        return in_array('webhook_secret', $config['required_fields'], true);
    }

    /**
     * Detect test mode from API key.
     */
    public static function detectTestMode(string $provider, string $apiKey): ?bool
    {
        $config = self::getProviderConfig($provider);

        if ($config === null || $config['test_mode_detection'] === null) {
            return null;
        }

        return str_starts_with($apiKey, $config['test_mode_detection']);
    }

    /**
     * Get required fields for provider.
     *
     * @return array<string>
     */
    public static function getRequiredFields(string $provider): array
    {
        $config = self::getProviderConfig($provider);

        return $config['required_fields'] ?? [];
    }

    /**
     * Check if provider configuration is valid.
     *
     * @param  array<string, mixed>  $data
     */
    public static function isProviderConfigured(string $provider, array $data): bool
    {
        $requiredFields = self::getRequiredFields($provider);

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === '' || $data[$field] === '0') {
                return false;
            }
        }

        return true;
    }

    /**
     * Get display options for select fields.
     *
     * @return array<string, string>
     */
    public static function getProviderOptions(): array
    {
        $options = [];

        foreach (self::GATEWAY_CONFIGS as $key => $config) {
            $options[$key] = $config['display_name'];
        }

        return $options;
    }
}
