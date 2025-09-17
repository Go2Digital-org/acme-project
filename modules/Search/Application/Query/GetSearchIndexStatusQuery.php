<?php

declare(strict_types=1);

namespace Modules\Search\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

/**
 * Query to get the status of search indexes.
 */
final readonly class GetSearchIndexStatusQuery implements QueryInterface
{
    /**
     * @param  class-string|null  $modelClass  Specific model to check, or null for all
     */
    public function __construct(
        public ?string $modelClass = null,
    ) {}
}
