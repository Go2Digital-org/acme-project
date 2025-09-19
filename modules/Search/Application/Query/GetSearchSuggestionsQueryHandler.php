<?php

declare(strict_types=1);

namespace Modules\Search\Application\Query;

use InvalidArgumentException;
use Modules\Search\Domain\Repository\SearchRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

final readonly class GetSearchSuggestionsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private SearchRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, array{text: string, id: mixed, type: string}>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof GetSearchSuggestionsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $indexName = $this->getIndexName($query->entityType);

        return $this->repository->getSuggestions(
            query: $query->query,
            index: $indexName,
            limit: $query->limit,
        );
    }

    /**
     * Get index name for entity type.
     */
    private function getIndexName(string $entityType): string
    {
        $mapping = [
            'campaign' => 'acme_campaigns',
            'donation' => 'acme_donations',
            'user' => 'acme_users',
            'organization' => 'acme_organizations',
        ];

        return $mapping[$entityType] ?? 'acme_campaigns';
    }
}
