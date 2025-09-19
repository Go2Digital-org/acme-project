<?php

declare(strict_types=1);

namespace Modules\Donation\Domain\Exception;

use Exception;
use Throwable;

/**
 * Payment Gateway Exception.
 *
 * Thrown when payment gateway operations fail or encounter errors.
 */
final class PaymentGatewayException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $gatewayData
     */
    public function __construct(
        string $message = 'Payment gateway error',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?string $gatewayCode = null,
        public readonly ?array $gatewayData = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for gateway configuration error.
     */
    public static function invalidConfiguration(string $gateway, string $reason): self
    {
        return new self(
            sprintf('Invalid configuration for gateway "%s": %s', $gateway, $reason),
            gatewayCode: 'INVALID_CONFIG',
        );
    }

    /**
     * Create exception for unsupported operation.
     */
    public static function unsupportedOperation(string $gateway, string $operation): self
    {
        return new self(
            sprintf('Gateway "%s" does not support operation: %s', $gateway, $operation),
            gatewayCode: 'UNSUPPORTED_OPERATION',
        );
    }

    /**
     * Create exception for network/communication errors.
     */
    public static function communicationError(string $gateway, string $details, ?Throwable $previous = null): self
    {
        return new self(
            sprintf('Communication error with gateway "%s": %s', $gateway, $details),
            previous: $previous,
            gatewayCode: 'COMMUNICATION_ERROR',
        );
    }

    /**
     * Create exception for webhook verification failure.
     */
    public static function webhookVerificationFailed(string $gateway, string $reason): self
    {
        return new self(
            sprintf('Webhook verification failed for gateway "%s": %s', $gateway, $reason),
            gatewayCode: 'WEBHOOK_VERIFICATION_FAILED',
        );
    }

    /**
     * Create exception for webhook validation failure.
     */
    public static function webhookValidationFailed(string $reason): self
    {
        return new self(
            sprintf('Webhook validation failed: %s', $reason),
            gatewayCode: 'WEBHOOK_VALIDATION_FAILED',
        );
    }

    /**
     * Get the gateway-specific error code.
     */
    public function getGatewayCode(): ?string
    {
        return $this->gatewayCode;
    }

    /**
     * Get gateway-specific error data.
     *
     * @return array<string, mixed>|null
     */
    public function getGatewayData(): ?array
    {
        return $this->gatewayData;
    }

    /**
     * Check if this is a retryable error.
     */
    public function isRetryable(): bool
    {
        return in_array($this->gatewayCode, [
            'COMMUNICATION_ERROR',
            'RATE_LIMIT',
            'SERVER_ERROR',
            'TIMEOUT',
        ], true);
    }

    /**
     * Check if this is a configuration error.
     */
    public function isConfigurationError(): bool
    {
        return in_array($this->gatewayCode, [
            'INVALID_CONFIG',
            'AUTHENTICATION_FAILED',
            'INVALID_CREDENTIALS',
        ], true);
    }
}
