<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Exception;

use Exception;

class CurrencyConversionException extends Exception
{
    public static function currencyNotFound(string $currencyCode): self
    {
        return new self("Currency '{$currencyCode}' not found");
    }

    public static function invalidConversion(string $from, string $to): self
    {
        return new self("Cannot convert from '{$from}' to '{$to}'");
    }

    public static function exchangeRateNotAvailable(string $currencyCode): self
    {
        return new self("Exchange rate not available for currency '{$currencyCode}'");
    }
}
