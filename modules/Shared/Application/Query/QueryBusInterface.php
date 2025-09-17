<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Query;

interface QueryBusInterface
{
    /**
     * Handle a query and return its result.
     */
    public function handle(QueryInterface $query): mixed;

    /**
     * Ask a query (alias for handle).
     */
    public function ask(QueryInterface $query): mixed;
}
