<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Service;

use InvalidArgumentException;
use Modules\Donation\Domain\Exception\PaymentGatewayException;
use Modules\Donation\Domain\Model\PaymentGateway;
use Modules\Donation\Domain\Repository\PaymentGatewayRepositoryInterface;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Infrastructure\Gateway\MockPaymentGateway;
use Modules\Donation\Infrastructure\Gateway\MolliePaymentGateway;
use Modules\Donation\Infrastructure\Gateway\PayPalPaymentGateway;
use Modules\Donation\Infrastructure\Gateway\StripePaymentGateway;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Payment Gateway Factory.
 *
 * Creates payment gateway instances based on configuration.
 * Implements Strategy pattern for flexible gateway switching.
 */
class PaymentGatewayFactory
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    private const GATEWAY_CLASSES = [
        'mollie' => MolliePaymentGateway::class,
        'stripe' => StripePaymentGateway::class,
        'paypal' => PayPalPaymentGateway::class,
        'mock' => MockPaymentGateway::class,
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PaymentGatewayRepositoryInterface $gatewayRepository,
        /** @var array<string, array<string, mixed>> */
        private array $gatewayConfigs = [],
    ) {}

    /**
     * Create gateway instance by name.
     */
    public function create(string $gatewayName): PaymentGatewayInterface
    {
        $gatewayName = strtolower($gatewayName);

        if (! isset(self::GATEWAY_CLASSES[$gatewayName])) {
            throw PaymentGatewayException::unsupportedOperation(
                $gatewayName,
                'Gateway not supported',
            );
        }

        $config = $this->getGatewayConfig($gatewayName);
        $gatewayClass = self::GATEWAY_CLASSES[$gatewayName];

        try {
            $gateway = $this->createGatewayInstance($gatewayClass, $config);

            if (! $gateway->validateConfiguration()) {
                throw PaymentGatewayException::invalidConfiguration(
                    $gatewayName,
                    'Gateway configuration validation failed',
                );
            }

            $this->logger->info('Payment gateway created', [
                'gateway' => $gatewayName,
                'class' => $gatewayClass,
            ]);

            return $gateway;
        } catch (Throwable $e) {
            $this->logger->error('Failed to create payment gateway', [
                'gateway' => $gatewayName,
                'error' => $e->getMessage(),
            ]);

            throw PaymentGatewayException::invalidConfiguration(
                $gatewayName,
                'Failed to initialize gateway: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Get default gateway from configuration.
     */
    public function createDefault(): PaymentGatewayInterface
    {
        $defaultGateway = config('payment.default', 'mock');

        return $this->create($defaultGateway);
    }

    /**
     * Get all available gateway names.
     *
     * @return string[]
     */
    public function getAvailableGateways(): array
    {
        return array_keys(self::GATEWAY_CLASSES);
    }

    /**
     * Check if gateway is available.
     */
    public function hasGateway(string $gatewayName): bool
    {
        return isset(self::GATEWAY_CLASSES[strtolower($gatewayName)]);
    }

    /**
     * Get gateway configuration status.
     *
     * @return array<string, bool>
     */
    public function getGatewayStatuses(): array
    {
        $statuses = [];

        foreach (array_keys(self::GATEWAY_CLASSES) as $gatewayName) {
            try {
                $gateway = $this->create($gatewayName);
                $statuses[$gatewayName] = $gateway->validateConfiguration();
            } catch (Throwable) {
                $statuses[$gatewayName] = false;
            }
        }

        return $statuses;
    }

    /**
     * Get gateway that supports specific payment method.
     *
     * @return array<int, PaymentGatewayInterface>
     */
    public function getGatewaysForPaymentMethod(string $paymentMethod): array
    {
        $supportedGateways = [];

        foreach (array_keys(self::GATEWAY_CLASSES) as $gatewayName) {
            try {
                $gateway = $this->create($gatewayName);

                if ($gateway->supports($paymentMethod)) {
                    $supportedGateways[] = $gateway;
                }
            } catch (Throwable $e) {
                $this->logger->debug('Gateway not available for payment method check', [
                    'gateway' => $gatewayName,
                    'payment_method' => $paymentMethod,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $supportedGateways;
    }

    /**
     * Get gateway that supports specific currency.
     *
     * @return array<int, PaymentGatewayInterface>
     */
    public function getGatewaysForCurrency(string $currency): array
    {
        $supportedGateways = [];

        foreach (array_keys(self::GATEWAY_CLASSES) as $gatewayName) {
            try {
                $gateway = $this->create($gatewayName);

                if (in_array($currency, $gateway->getSupportedCurrencies(), true)) {
                    $supportedGateways[] = $gateway;
                }
            } catch (Throwable $e) {
                $this->logger->debug('Gateway not available for currency check', [
                    'gateway' => $gatewayName,
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $supportedGateways;
    }

    /**
     * Select best gateway for payment requirements.
     */
    public function selectBestGateway(
        string $paymentMethod,
        string $currency,
        ?float $amount = null,
    ): PaymentGatewayInterface {
        $candidates = [];

        foreach (array_keys(self::GATEWAY_CLASSES) as $gatewayName) {
            try {
                $gateway = $this->create($gatewayName);

                if ($gateway->supports($paymentMethod) &&
                    in_array($currency, $gateway->getSupportedCurrencies(), true)) {
                    $candidates[] = ['name' => $gatewayName, 'gateway' => $gateway];
                }
            } catch (Throwable) {
                // Skip unavailable gateways
                continue;
            }
        }

        if ($candidates === []) {
            throw PaymentGatewayException::unsupportedOperation(
                'any',
                sprintf('No gateway supports payment method "%s" and currency "%s"', $paymentMethod, $currency),
            );
        }

        // Check if only mock gateway is available (no real payment gateways configured)
        if (count($candidates) === 1 && $candidates[0]['name'] === 'mock') {
            $this->logger->error('No real payment gateway configured, only mock gateway is available', [
                'payment_method' => $paymentMethod,
                'currency' => $currency,
                'amount' => $amount,
            ]);

            throw PaymentGatewayException::invalidConfiguration(
                'payment',
                'No payment gateway is properly configured. Please configure Stripe, Mollie, or PayPal with valid API credentials.',
            );
        }

        // Apply selection logic (could be based on fees, reliability, etc.)
        $selected = $this->applyGatewaySelectionLogic($candidates, $amount);

        $this->logger->info('Gateway selected', [
            'selected' => $selected['name'],
            'payment_method' => $paymentMethod,
            'currency' => $currency,
            'amount' => $amount,
            'candidates' => array_column($candidates, 'name'),
        ]);

        return $selected['gateway'];
    }

    /** @return array<array-key, mixed> */
    private function getGatewayConfig(string $gatewayName): array
    {
        // Try to get configuration from database first
        $dbGateway = $this->gatewayRepository->findByProvider($gatewayName);

        if ($dbGateway instanceof PaymentGateway && $dbGateway->isConfigured()) {
            return $this->convertDomainModelToConfig($dbGateway);
        }

        // Try injected config next
        if (isset($this->gatewayConfigs[$gatewayName])) {
            return $this->gatewayConfigs[$gatewayName];
        }

        // Fall back to Laravel config
        $config = config("payment.gateways.{$gatewayName}", []);

        if (empty($config)) {
            throw PaymentGatewayException::invalidConfiguration(
                $gatewayName,
                'No configuration found in database or config files',
            );
        }

        return $config;
    }

    /**
     * Convert PaymentGateway domain model to configuration array format.
     */
    /** @return array<array-key, mixed> */
    private function convertDomainModelToConfig(PaymentGateway $gateway): array
    {
        $settings = $gateway->settings ?? [];

        return match ($gateway->provider) {
            'mollie' => [
                'api_key' => $gateway->api_key,
                'webhook_url' => $settings['webhook_url'] ?? '',
                'description_prefix' => $settings['description_prefix'] ?? 'ACME Corp Donation',
            ],
            'stripe' => [
                'secret_key' => $gateway->api_key,
                'public_key' => $settings['publishable_key'] ?? '',
                'webhook_secret' => $gateway->webhook_secret,
                'test_mode' => $gateway->test_mode,
            ],
            'paypal' => [
                'client_id' => $settings['client_id'] ?? '',
                'client_secret' => $gateway->api_key,
                'webhook_id' => $settings['webhook_id'] ?? '',
                'sandbox_mode' => $gateway->test_mode,
            ],
            'mock' => [
                'simulate_failures' => $settings['simulate_failures'] ?? false,
                'failure_rate' => $settings['failure_rate'] ?? 0.1,
            ],
            default => [],
        };
    }

    /** @param array<string, mixed> $config */
    private function createGatewayInstance(string $gatewayClass, array $config): PaymentGatewayInterface
    {
        return match ($gatewayClass) {
            MolliePaymentGateway::class => new MolliePaymentGateway(
                apiKey: $config['api_key'] ?? '',
                webhookUrl: $config['webhook_url'] ?? '',
                logger: $this->logger,
                descriptionPrefix: $config['description_prefix'] ?? 'ACME Corp Donation',
            ),
            StripePaymentGateway::class => new StripePaymentGateway(
                secretKey: $config['secret_key'] ?? '',
                publicKey: $config['public_key'] ?? '',
                webhookSecret: $config['webhook_secret'] ?? '',
                logger: $this->logger,
            ),
            PayPalPaymentGateway::class => new PayPalPaymentGateway(
                clientId: $config['client_id'] ?? '',
                clientSecret: $config['client_secret'] ?? '',
                webhookId: $config['webhook_id'] ?? '',
                logger: $this->logger,
                sandboxMode: $config['sandbox_mode'] ?? true,
            ),
            MockPaymentGateway::class => new MockPaymentGateway(
                logger: $this->logger,
                simulateFailures: $config['simulate_failures'] ?? false,
                failureRate: $config['failure_rate'] ?? 0.1,
            ),
            default => throw new InvalidArgumentException("Unknown gateway class: {$gatewayClass}"),
        };
    }

    /**
     * @param  array<int, array{name: string, gateway: PaymentGatewayInterface}>  $candidates
     * @return array{name: string, gateway: PaymentGatewayInterface}
     */
    private function applyGatewaySelectionLogic(array $candidates, ?float $amount): array
    {
        // Simple selection logic - could be enhanced with:
        // - Fee comparison
        // - Reliability scores
        // - Regional preferences
        // - A/B testing

        // For now, prefer Stripe for larger amounts, PayPal for smaller
        if ($amount !== null && $amount > 1000) {
            $stripe = collect($candidates)->firstWhere('name', 'stripe');

            if ($stripe) {
                return $stripe;
            }
        }

        // Default to first available gateway
        return $candidates[0];
    }
}
