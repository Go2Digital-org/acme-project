<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Currency\Domain\Model\Currency;

interface CurrencyRepositoryInterface
{
    public function findByCode(string $code): ?Currency;

    /**
     * @return Collection<int, Currency>
     */
    public function getActive(): Collection;

    public function getDefault(): ?Currency;

    /**
     * @return Collection<int, Currency>
     */
    public function getAll(): Collection;

    public function save(Currency $currency): bool;

    public function setDefault(Currency $currency): void;
}
