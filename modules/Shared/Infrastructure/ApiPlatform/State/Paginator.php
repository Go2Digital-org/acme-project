<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\State;

use ApiPlatform\State\Pagination\PaginatorInterface;
use Iterator;
use IteratorAggregate;

/**
 * @template T of object
 *
 * @implements IteratorAggregate<mixed, T>
 * @implements PaginatorInterface<T>
 */
final readonly class Paginator implements IteratorAggregate, PaginatorInterface
{
    /**
     * @param  Iterator<T>  $iterator
     */
    public function __construct(
        private Iterator $iterator,
        private int $currentPage,
        private int $itemsPerPage,
        private int $lastPage,
        private int $totalItems,
    ) {}

    public function getCurrentPage(): float
    {
        return (float) $this->currentPage;
    }

    public function getLastPage(): float
    {
        return (float) $this->lastPage;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    /**
     * @return Iterator<T>
     */
    public function getIterator(): Iterator
    {
        return $this->iterator;
    }

    public function count(): int
    {
        return iterator_count($this->iterator);
    }
}
