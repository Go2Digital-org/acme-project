<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Repository\CurrencyRepositoryInterface;

class EloquentCurrencyRepository implements CurrencyRepositoryInterface
{
    public function findByCode(string $code): ?Currency
    {
        return Currency::findByCode($code);
    }

    public function getActive(): Collection
    {
        return Currency::getActiveCurrencies();
    }

    public function getDefault(): ?Currency
    {
        return Currency::getDefaultCurrency();
    }

    public function getAll(): Collection
    {
        return Currency::orderBy('sort_order')->get();
    }

    public function save(Currency $currency): bool
    {
        return $currency->save();
    }

    public function setDefault(Currency $currency): void
    {
        // Unset all other defaults
        Currency::where('is_default', true)->update(['is_default' => false]);

        // Set this one as default
        $currency->is_default = true;
        $currency->save();
    }
}
