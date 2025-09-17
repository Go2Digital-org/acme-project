<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Organization\Application\Query\SearchOrganizationsQuery;
use Modules\Organization\Infrastructure\ApiPlatform\Resource\OrganizationResource;
use Modules\Shared\Application\Query\QueryBusInterface;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;

/**
 * @implements ProviderInterface<OrganizationResource>
 */
final readonly class OrganizationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Pagination $pagination,
    ) {}

    /**
     * @return Paginator<OrganizationResource>|array<int, OrganizationResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $offset = $limit = null;
        $filters = $context['filters'] ?? [];
        $sorts = ($context['filters'] ?? [])['sort'] ?? [];

        if ($this->pagination->isEnabled($operation, $context)) {
            $offset = $this->pagination->getPage($context);
            $limit = $this->pagination->getLimit($operation, $context);
        }

        $searchTerm = (string) ($filters['search'] ?? '');
        $page = $offset ?? 1;
        $perPage = $limit ?? 15;
        $sortBy = (string) ($sorts['sortBy'] ?? 'created_at');
        $sortOrder = (string) ($sorts['sortOrder'] ?? 'desc');

        $models = $this->queryBus->ask(new SearchOrganizationsQuery(
            searchTerm: $searchTerm,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
        ));

        if (! $models || $models->isEmpty()) {
            return [];
        }

        $resources = $models->map(fn ($model): OrganizationResource => OrganizationResource::fromModel($model));

        if ($models instanceof LengthAwarePaginator) {
            /** @var ArrayIterator<int, OrganizationResource> $iterator */
            $iterator = new ArrayIterator($resources->all());

            return new Paginator(
                $iterator,
                $models->currentPage(),
                $models->perPage(),
                $models->lastPage(),
                $models->total(),
            );
        }

        return $resources->all();
    }
}
