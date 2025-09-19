<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class ApiLocaleMiddleware
{
    /**
     * Supported locales for the API.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_LOCALES = ['en', 'nl', 'fr'];

    /**
     * Default locale when none specified or unsupported locale provided.
     *
     * @var string
     */
    private const DEFAULT_LOCALE = 'en';

    /**
     * Handle API locale detection and setting based on Accept-Language header.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);

        // Set the application locale
        App::setLocale($locale);

        // Store locale in request for later use
        $request->attributes->set('api_locale', $locale);

        // Add locale to response headers for client confirmation
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->headers->set('Content-Language', $locale);
        }

        return $response;
    }

    /**
     * Get supported locales for API documentation.
     */
    /** @return list<string> */
    public static function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * Get default locale for API documentation.
     */
    public static function getDefaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }

    /**
     * Detect the best locale from Accept-Language header or query parameter.
     */
    private function detectLocale(Request $request): string
    {
        // Priority 1: Explicit locale query parameter (?locale=en)
        if ($request->has('locale')) {
            $queryLocale = $request->query('locale');

            if (is_string($queryLocale) && in_array($queryLocale, self::SUPPORTED_LOCALES, true)) {
                return $queryLocale;
            }
        }

        // Priority 2: Accept-Language header
        $acceptLanguage = $request->header('Accept-Language', '');

        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);

            if ($locale) {
                return $locale;
            }
        }

        // Priority 3: Default locale
        return self::DEFAULT_LOCALE;
    }

    /**
     * Parse Accept-Language header and find best matching supported locale.
     */
    private function parseAcceptLanguage(string $acceptLanguage): ?string
    {
        // Parse Accept-Language header format: "en-US,en;q=0.9,nl;q=0.8,fr;q=0.7"
        $languages = [];

        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $code = trim($parts[0]);
            $quality = 1.0;

            // Extract quality value if present
            if (isset($parts[1]) && str_contains($parts[1], 'q=')) {
                $quality = (float) substr(trim($parts[1]), 2);
            }

            // Extract base language code (e.g., 'en' from 'en-US')
            $baseCode = strtolower(explode('-', $code)[0]);

            if (in_array($baseCode, self::SUPPORTED_LOCALES, true)) {
                $languages[$baseCode] = $quality;
            }
        }

        if ($languages === []) {
            return null;
        }

        // Sort by quality (preference) descending
        arsort($languages);

        // Return highest quality supported language
        return array_key_first($languages);
    }
}
