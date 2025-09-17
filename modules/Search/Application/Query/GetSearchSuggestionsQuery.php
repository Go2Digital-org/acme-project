<?php

declare(strict_types=1);

namespace Modules\Search\Application\Query;

use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetSearchSuggestionsQuery implements QueryInterface
{
    public function __construct(
        public string $query,
        public string $entityType = 'campaign',
        public int $limit = 10,
    ) {}
}
