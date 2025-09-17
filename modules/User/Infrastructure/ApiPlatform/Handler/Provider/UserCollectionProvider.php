<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;
use Modules\User\Infrastructure\ApiPlatform\Resource\UserResource;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements ProviderInterface<Paginator<UserResource>>
 */
final readonly class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
    ) {}

    /**
     * @return Paginator<UserResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator
    {
        $offset = $limit = null;
        /** @var array<string, mixed> $filters */
        $filters = $context['filters'] ?? [];
        /** @var array<string, mixed> $sorts */
        $sorts = [];

        if (isset($context['filters']) && is_array($context['filters']) && isset($context['filters']['sort']) && is_array($context['filters']['sort'])) {
            $sorts = $context['filters']['sort'];
        }

        if ($this->pagination->isEnabled($operation, $context)) {
            $offset = $this->pagination->getPage($context);
            $limit = $this->pagination->getLimit($operation, $context);
        }

        $query = User::query();

        // Apply filters
        if (isset($filters['name']) && is_string($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (isset($filters['email']) && is_string($filters['email'])) {
            $query->where('email', 'like', "%{$filters['email']}%");
        }

        if (isset($filters['job_title']) && is_string($filters['job_title'])) {
            $query->where('job_title', 'like', "%{$filters['job_title']}%");
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $query->where(function ($q) use ($filters): void {
                $search = $filters['search'];
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = 'id';
        $sortOrder = 'desc';

        if (! empty($sorts)) {
            $firstKey = array_key_first($sorts);

            if (is_string($firstKey)) {
                $sortBy = $firstKey;
            }
            $firstValue = reset($sorts);

            if (is_string($firstValue)) {
                $sortOrder = $firstValue;
            }
        }

        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $models = $offset && $limit ? $query->paginate($limit, ['*'], 'page', $offset) : $query->paginate($limit ?? 20);

        /** @var array<UserResource> $resources */
        $resources = [];

        if (! $models->isEmpty()) {
            $resources = $models->map(fn ($model): UserResource => UserResource::fromModel($model))->all();
        }

        /** @var ArrayIterator<int, UserResource> $iterator */
        $iterator = new ArrayIterator($resources);

        return new Paginator(
            $iterator,
            $models->currentPage(),
            $models->perPage(),
            $models->lastPage(),
            $models->total(),
        );
    }
}
