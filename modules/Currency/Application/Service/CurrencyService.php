<?php

declare(strict_types=1);

namespace Modules\Currency\Application\Service;

use Illuminate\Contracts\Auth\Authenticatable;

class CurrencyService
{
    public function changeCurrency(string $currency, ?Authenticatable $user): void
    {
        session(['user_currency' => $currency]);

        if ($user instanceof Authenticatable) {
            $preferences = $user->preferences ?? [];
            $preferences['currency'] = $currency;
            $user->update(['preferences' => $preferences]);
        }
    }
}
