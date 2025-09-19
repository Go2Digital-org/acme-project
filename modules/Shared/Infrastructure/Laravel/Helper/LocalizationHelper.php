<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Helper;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

final class LocalizationHelper
{
    /**
     * Get all supported locales with their display information.
     */
    /**
     * @return array<string, mixed>
     */
    public static function getSupportedLocales(): array
    {
        return LaravelLocalization::getSupportedLocales();
    }

    /**
     * Get current locale code.
     */
    public static function getCurrentLocale(): string
    {
        return LaravelLocalization::getCurrentLocale();
    }

    /**
     * Get localized URL for a specific locale.
     */
    public static function getLocalizedUrl(string $locale, ?string $url = null): string
    {
        $localizedUrl = LaravelLocalization::getLocalizedURL($locale, $url);

        return is_string($localizedUrl) ? $localizedUrl : ($url ?? '/');
    }

    /**
     * Get non-localized URL (removes locale prefix).
     */
    public static function getNonLocalizedUrl(?string $url = null): string
    {
        return LaravelLocalization::getNonLocalizedURL($url) ?? ($url ?? '/');
    }

    /**
     * Check if locale is supported.
     */
    public static function isLocaleSupported(string $locale): bool
    {
        return array_key_exists($locale, LaravelLocalization::getSupportedLocales());
    }

    /**
     * Get locale display name.
     */
    public static function getLocaleDisplayName(string $locale): string
    {
        $locales = LaravelLocalization::getSupportedLocales();
        $native = $locales[$locale]['native'] ?? null;

        return is_string($native) ? $native : strtoupper($locale);
    }

    /**
     * Get available locales (excluding current).
     */
    /**
     * @return array<string, mixed>
     */
    public static function getAvailableLocales(): array
    {
        $current = self::getCurrentLocale();

        return array_filter(
            LaravelLocalization::getSupportedLocales(),
            fn ($code): bool => $code !== $current,
            ARRAY_FILTER_USE_KEY,
        );
    }
}
