<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Donation\Application\Query\SearchDonationsQuery;
use Modules\Donation\Infrastructure\ApiPlatform\Resource\DonationResource;
use Modules\Shared\Application\Query\QueryBusInterface;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;

/**
 * @implements ProviderInterface<DonationResource>
 */
final readonly class DonationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
        private Pagination $pagination,
    ) {}

    /**
     * @return Paginator<DonationResource>|array<DonationResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $page = 1;
        $perPage = 15;
        $filters = $context['filters'] ?? [];
        $sorts = ($context['filters'] ?? [])['sort'] ?? [];

        if ($this->pagination->isEnabled($operation, $context)) {
            $page = $this->pagination->getPage($context);
            $perPage = $this->pagination->getLimit($operation, $context);
        }

        // Extract search term from filters
        $searchTerm = '';

        if (isset($filters['search'])) {
            $searchTerm = (string) $filters['search'];
            unset($filters['search']);
        }

        // Extract sort parameters
        $sortBy = 'created_at';
        $sortOrder = 'desc';

        if (is_array($sorts) && count($sorts) > 0) {
            $sortKeys = array_keys($sorts);
            $sortBy = (string) $sortKeys[0];
            $sortOrder = (string) ($sorts[$sortBy] ?? 'desc');
        }

        $models = $this->queryBus->ask(new SearchDonationsQuery(
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

        $resources = $models->map(fn ($model): DonationResource => DonationResource::fromModel($model));

        if ($models instanceof LengthAwarePaginator) {
            /** @var ArrayIterator<int, DonationResource> $iterator */
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
