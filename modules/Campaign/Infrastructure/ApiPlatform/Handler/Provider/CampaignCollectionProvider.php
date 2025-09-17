<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Campaign\Application\Query\SearchCampaignsQuery;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\CampaignResource;
use Modules\Shared\Application\Query\QueryBusInterface;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;

/**
 * @implements ProviderInterface<CampaignResource>
 */
final readonly class CampaignCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Pagination $pagination,
    ) {}

    /**
     * @return Paginator<CampaignResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
    {
        $page = 1;
        $perPage = 15;
        $filters = $context['filters'] ?? [];
        $searchTerm = $filters['search'] ?? '';
        $sortBy = $filters['sortBy'] ?? 'created_at';
        $sortOrder = $filters['sortOrder'] ?? 'desc';

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $perPage = $this->pagination->getLimit($operation, $context);
        }

        $models = $this->queryBus->ask(new SearchCampaignsQuery(
            searchTerm: $searchTerm,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
        ));

        if (! $models || $models->isEmpty()) {
            /** @var ArrayIterator<int, CampaignResource> $iterator */
            $iterator = new ArrayIterator([]);

            return new Paginator(
                $iterator,
                1,
                $perPage,
                1,
                0,
            );
        }

        $resources = $models->map(fn ($model): CampaignResource => CampaignResource::fromModel($model));

        /** @var ArrayIterator<int, CampaignResource> $iterator */
        $iterator = new ArrayIterator($resources->all());

        if ($models instanceof LengthAwarePaginator) {
            return new Paginator(
                $iterator,
                $models->currentPage(),
                $models->perPage(),
                $models->lastPage(),
                $models->total(),
            );
        }

        // For non-paginated results, still wrap in Paginator for consistent API response
        return new Paginator(
            $iterator,
            1,
            count($resources),
            1,
            count($resources),
        );
    }
}
