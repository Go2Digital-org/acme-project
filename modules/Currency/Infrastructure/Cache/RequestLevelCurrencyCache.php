<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Cache;

use Illuminate\Support\Collection;
use Modules\Currency\Domain\Service\CurrencyCacheInterface;
use stdClass;

final class RequestLevelCurrencyCache implements CurrencyCacheInterface
{
    /**
     * @var Collection<int, stdClass>|null
     */
    private ?Collection $currencies = null;

    public function get(): ?Collection
    {
        return $this->currencies;
    }

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
