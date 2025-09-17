<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Helper;

use InvalidArgumentException;

/**
 * Currency formatting helper following hexagonal architecture principles.
 * Provides consistent currency formatting across the application.
 */
final class CurrencyHelper
{
    /**
     * Format amount as Euro with European number formatting.
     * Returns format: €1.234,56.
     */
    public static function formatEuro(float $amount): string
    {
        return self::formatCurrency($amount, 'EUR');
    }

    /**
     * Format amount with specified currency using appropriate locale formatting.
     *
     * @param  float  $amount  The amount to format
     * @param  string  $currency  The currency code (EUR, USD, GBP)
     * @return string Formatted currency string
     */
    public static function formatCurrency(float $amount, string $currency): string
    {
        // Validate currency
        if (! in_array($currency, ['EUR', 'USD', 'GBP'], true)) {
            throw new InvalidArgumentException("Unsupported currency: {$currency}");
        }

        // Define currency symbols and locale formatting
        $currencyConfig = [
            'EUR' => [
                'symbol' => '€',
                'thousands_separator' => '.',
                'decimal_separator' => ',',
                'decimals' => 2,
            ],
            'USD' => [
                'symbol' => '$',
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'decimals' => 2,
            ],
            'GBP' => [
                'symbol' => '£',
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'decimals' => 2,
            ],
        ];

        $config = $currencyConfig[$currency];

        // Format the number with appropriate separators
        $formattedAmount = number_format(
            $amount,
            $config['decimals'],
            $config['decimal_separator'],
            $config['thousands_separator'],
        );

        return $config['symbol'] . $formattedAmount;
    }

    /**
     * Parse currency string back to float amount.
     * Useful for form input processing.
     */
    public static function parseCurrency(string $currencyString, string $currency = 'EUR'): float
    {
        // Remove currency symbol and normalize separators
        $cleaned = preg_replace('/[€$£\s]/', '', $currencyString) ?? '';

        if ($currency === 'EUR') {
            // Convert European format (1.234,56) to standard format
            $cleaned = str_replace('.', '', $cleaned); // Remove thousands separator
            $cleaned = str_replace(',', '.', $cleaned); // Convert decimal separator
        } else {
            // For USD/GBP, just remove commas (thousands separator)
            $cleaned = str_replace(',', '', $cleaned);
        }

        return (float) $cleaned;
    }

    /**
     * Get supported currencies.
     */
    /** @return array<array-key, mixed> */
    public static function getSupportedCurrencies(): array
    {
        return ['EUR', 'USD', 'GBP'];
    }

    /**
     * Get currency symbol for a given currency code.
     */
    public static function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
        ];

        return $symbols[$currency] ?? $currency;
    }

    /**
     * Format amount with default EUR currency.
     * Convenience method for backward compatibility.
     */
    public static function format(float $amount, string $currency = 'EUR'): string
    {
        return self::formatCurrency($amount, $currency);
    }

    /**
     * Format amount with short notation for large numbers.
     * Examples: €1,200 -> €1.2K, €1,500,000 -> €1.5M.
     */
    public static function formatShort(float $amount, string $currency = 'EUR'): string
    {
        if ($amount >= 1000000) {
            $shortened = $amount / 1000000;

            return self::formatCurrency($shortened, $currency) . 'M';
        }

        if ($amount >= 1000) {
            $shortened = $amount / 1000;

            return self::formatCurrency($shortened, $currency) . 'K';
        }

        return self::formatCurrency($amount, $currency);
    }
}
