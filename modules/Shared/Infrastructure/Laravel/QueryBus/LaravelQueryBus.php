<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\QueryBus;

use Illuminate\Support\Facades\App;
use Modules\Shared\Application\Query\QueryBusInterface;
use Modules\Shared\Application\Query\QueryInterface;
use RuntimeException;

/**
 * Laravel Implementation of Query Bus.
 *
 * This implementation resolves query handlers automatically based on naming conventions:
 * - Query: SomeQuery -> Handler: SomeQueryHandler
 */
final readonly class LaravelQueryBus implements QueryBusInterface
{
    public function handle(QueryInterface $query): mixed
    {
        $queryClass = $query::class;
        $handlerClass = $queryClass . 'Handler';

        if (! class_exists($handlerClass)) {
            throw new RuntimeException(
                "Query handler '{$handlerClass}' not found for query '{$queryClass}'",
            );
        }

        $handler = App::make($handlerClass);

        if (! is_object($handler)) {
            throw new RuntimeException(
                "Query handler '{$handlerClass}' could not be instantiated",
            );
        }

        if (! method_exists($handler, 'handle')) {
            throw new RuntimeException(
                "Query handler '{$handlerClass}' does not have a handle method",
            );
        }

        return $handler->handle($query);
    }

    public function ask(QueryInterface $query): mixed
    {
        return $this->handle($query);
    }
}
