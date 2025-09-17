<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->getLocale($request);

        // Validate locale against supported locales
        $supportedLocales = config('app.supported_locales');

        if ($supportedLocales !== null && in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Get the locale from various sources in order of priority.
     */
    private function getLocale(Request $request): string
    {
        // 1. URL parameter (for language switching)
        if ($request->has('locale')) {
            $locale = $request->input('locale');
            Session::put('locale', $locale);

            return $locale;
        }

        // 2. Session (user's previous choice)
        if (Session::has('locale')) {
            return Session::get('locale');
        }

        // 3. User preference (if authenticated)
        $user = $request->user();

        if ($user !== null) {
            $preferences = $user->preferences ?? [];

            if (isset($preferences['locale'])) {
                return $preferences['locale'];
            }
        }

        // 4. Browser Accept-Language header
        $browserLocale = $this->getBrowserLocale($request);

        if ($browserLocale) {
            return $browserLocale;
        }

        // 5. Default fallback
        return config('app.locale') ?? 'en';
    }

    /**
     * Get locale from browser Accept-Language header.
     */
    private function getBrowserLocale(Request $request): ?string
    {
        $supportedLocales = config('app.supported_locales');
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = [];
        preg_match_all('/([a-z]{2}(?:-[A-Z]{2})?)\s*(?:;\s*q\s*=\s*([0-9.]+))?/', $acceptLanguage, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $lang = $match[1];
            $quality = isset($match[2]) ? (float) $match[2] : 1.0;
            $languages[$lang] = $quality;
        }

        // Sort by quality
        arsort($languages);

        // Find first supported locale
        foreach (array_keys($languages) as $lang) {
            // Check exact match (e.g., 'en-US')
            if ($supportedLocales !== null && in_array($lang, $supportedLocales, true)) {
                return $lang;
            }

            // Check language part only (e.g., 'en' from 'en-US')
            $languageCode = substr($lang, 0, 2);

            if ($supportedLocales !== null && in_array($languageCode, $supportedLocales, true)) {
                return $languageCode;
            }
        }

        return null;
    }
}
