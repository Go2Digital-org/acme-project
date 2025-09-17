<?php

declare(strict_types=1);

use Modules\Currency\Application\Service\CurrencyPreferenceService;
use Modules\Currency\Domain\ValueObject\Currency;

if (! function_exists('current_currency')) {
    function current_currency(): Currency
    {
        return app(CurrencyPreferenceService::class)->getCurrentCurrency();
    }
}

if (! function_exists('format_currency')) {
    function format_currency(float $amount, ?Currency $currency = null): string
    {
        return app(CurrencyPreferenceService::class)->formatAmount($amount, $currency);
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(?Currency $currency = null): string
    {
        $currency ??= current_currency();

        return $currency->getSymbol();
    }
}
