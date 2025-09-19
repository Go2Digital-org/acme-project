<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Cache;

use Illuminate\Support\Collection;
use Modules\Currency\Domain\Model\Currency;
use Modules\Currency\Domain\Service\CurrencyCacheInterface;

final class RequestLevelCurrencyCache implements CurrencyCacheInterface
{
    /** @var Collection<int, Currency>|null */
    private ?Collection $currencies = null;

    /**
     * @return Collection<int, Currency>|null
     */
    public function get(): ?Collection
    {
        return $this->currencies;
    }

    /**
     * @param  Collection<int, Currency>  $currencies
     */
    public function set(Collection $currencies): void
    {
        $this->currencies = $currencies;
    }

    public function has(): bool
    {
        return $this->currencies instanceof Collection;
    }

    public function clear(): void
    {
        $this->currencies = null;
    }
}
