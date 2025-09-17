<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Exception;

use RuntimeException;

class ExchangeRateProviderException extends RuntimeException
{
    public static function providerUnavailable(string $provider, ?string $reason = null): self
    {
        $message = "Exchange rate provider '{$provider}' is unavailable";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self($message);
    }

    public static function fetchFailed(string $provider, string $reason): self
    {
        return new self("Failed to fetch exchange rates from '{$provider}': {$reason}");
    }

    public static function invalidResponse(string $provider, string $details): self
    {
        return new self("Invalid response from exchange rate provider '{$provider}': {$details}");
    }

    public static function rateLimitExceeded(string $provider): self
    {
        return new self("Rate limit exceeded for exchange rate provider '{$provider}'");
    }

    public static function authenticationFailed(string $provider): self
    {
        return new self("Authentication failed for exchange rate provider '{$provider}'");
    }

    public static function noProvidersAvailable(): self
    {
        return new self('No exchange rate providers are available');
    }
}
