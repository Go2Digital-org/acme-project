<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Repository;

use ApiPlatform\Metadata\Operation;
use ArrayIterator;
use Countable;
use Illuminate\Pagination\LengthAwarePaginator;
use IteratorAggregate;
use Traversable;

/**
 * @template TEntity of object
 *
 * @extends IteratorAggregate<int, TEntity>
 */
interface RepositoryInterface extends Countable, IteratorAggregate
{
    /**
     * @return ArrayIterator<int, TEntity>|Traversable<int, TEntity>
     */
    public function getIterator(): ArrayIterator|Traversable;

    public function count(): int;

    /**
     * @return LengthAwarePaginator<int, TEntity>|null
     */
    public function paginator(): ?LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $sorts
     * @return LengthAwarePaginator<int, TEntity>
     */
    public function withPagination(
        int $page,
        int $itemsPerPage,
        array $filters,
        array $sorts,
        Operation $operation,
    ): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $sorts
     * @return iterable<int, TEntity>
     */
    public function withoutPagination(array $filters, array $sorts, Operation $operation): iterable;
}
