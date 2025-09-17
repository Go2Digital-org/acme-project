<?php

declare(strict_types=1);

namespace Modules\Localization\Application\Service;

use Illuminate\Contracts\Auth\Authenticatable;

final class LocalizationService
{
    public function switchLocale(string $locale, ?Authenticatable $user): void
    {
        $availableLocales = config('app.available_locales', []);

        if (! array_key_exists($locale, $availableLocales)) {
            return;
        }

        session(['locale' => $locale]);
        app()->setLocale($locale);

        if ($user instanceof Authenticatable) {
            $preferences = $user->preferences ?? [];
            $preferences['locale'] = $locale;
            $user->update(['preferences' => $preferences]);
        }
    }
}
