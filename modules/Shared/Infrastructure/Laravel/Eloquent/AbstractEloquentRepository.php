<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Eloquent;

use ApiPlatform\Laravel\Eloquent\Filter\DateFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EndSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrFilter;
use ApiPlatform\Laravel\Eloquent\Filter\PartialSearchFilter;
use ApiPlatform\Laravel\Eloquent\Filter\RangeFilter;
use ApiPlatform\Laravel\Eloquent\Filter\StartSearchFilter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\Parameters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Infrastructure\ApiPlatform\Filter\MultiSearchFilter;
use Modules\Shared\Infrastructure\ApiPlatform\Filter\RelationshipFilter;
use Modules\Shared\Infrastructure\Laravel\Exception\FatalErrorFoundException;
use Modules\Shared\Infrastructure\Laravel\Exception\ResourceNotFoundException;

abstract class AbstractEloquentRepository
{
    public function __construct(protected Model $model) {}

    /**
     * Get results without pagination.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $sorts
     * @return iterable<Model>
     */
    public function withoutPagination(array $filters, array $sorts, Operation $operation): iterable
    {
        $query = $this->model->newQuery();
        $this->applyFiltersAndSorting($query, $filters, $sorts, $operation);

        return $query->get();
    }

    /**
     * Get results with pagination.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $sorts
     * @return LengthAwarePaginator<int, Model>
     */
    public function withPagination(
        int $page,
        int $itemsPerPage,
        array $filters,
        array $sorts,
        Operation $operation,
    ): LengthAwarePaginator {
        try {
            $query = $this->model::query();
            $this->applyFiltersAndSorting($query, $filters, $sorts, $operation);

            return $query->paginate($itemsPerPage, ['*'], 'page', $page);
        } catch (QueryException $e) {
            Log::error('Pagination failed: ' . $e->getMessage(), ['bindings' => $e->getBindings()]);

            return new LengthAwarePaginator([], 0, $itemsPerPage, $page);
        }
    }

    /**
     * @throws ResourceNotFoundException
     * @throws FatalErrorFoundException
     */
    public function find(int $id): Model
    {
        try {
            return $this->model::findOrFail($id);
        } catch (ModelNotFoundException) {
            throw new ResourceNotFoundException(
                class_basename($this->model) . ' not found for ID ' . $id,
                404,
            );
        } catch (QueryException $e) {
            Log::error('Find operation failed for ID ' . $id, ['exception' => $e]);

            throw new FatalErrorFoundException('Internal server error', 500);
        }
    }

    /**
     * @throws ResourceNotFoundException
     * @throws FatalErrorFoundException
     */
    /**
     * Update a model using PUT method (full replacement).
     *
     * @param  array<string, mixed>  $updatedData
     *
     * @throws ResourceNotFoundException
     * @throws FatalErrorFoundException
     */
    public function put(int $id, array $updatedData): Model
    {
        try {
            $entity = $this->model::findOrFail($id);
            $entity->update($updatedData);

            return $entity;
        } catch (ModelNotFoundException) {
            throw new ResourceNotFoundException(
                class_basename($this->model) . ' not found for ID ' . $id,
                404,
            );
        } catch (QueryException $e) {
            Log::error('PUT operation failed for ID ' . $id, ['exception' => $e]);

            throw new FatalErrorFoundException('Internal server error', 500);
        }
    }

    /**
     * @throws ResourceNotFoundException
     * @throws FatalErrorFoundException
     */
    /**
     * Update a model using PATCH method (partial update).
     *
     * @param  array<string, mixed>  $partialData
     *
     * @throws ResourceNotFoundException
     * @throws FatalErrorFoundException
     */
    public function patch(int $id, array $partialData): Model
    {
        try {
            $entity = $this->model::findOrFail($id);
            $partialData = array_filter($partialData, fn ($value): bool => $value !== null);
            $entity->update($partialData);

            return $entity;
        } catch (ModelNotFoundException) {
            Log::warning(class_basename($this->model) . ' not found for ID ' . $id);

            throw new ResourceNotFoundException(
                class_basename($this->model) . ' not found for ID ' . $id,
                404,
            );
        } catch (QueryException $e) {
            Log::error('PATCH operation failed for ID ' . $id, ['exception' => $e]);

            throw new FatalErrorFoundException('Internal server error', 500);
        }
    }

    /**
     * @throws ResourceNotFoundException
     * @throws FatalErrorFoundException
     */
    public function remove(int $id): Model
    {
        try {
            $entity = $this->model::findOrFail($id);
            $entity->delete();

            return $entity;
        } catch (ModelNotFoundException) {
            Log::warning(class_basename($this->model) . ' not found for ID ' . $id);

            throw new ResourceNotFoundException(
                class_basename($this->model) . ' not found for ID ' . $id,
                404,
            );
        } catch (QueryException $e) {
            Log::error('DELETE operation failed for ID ' . $id, ['exception' => $e]);

            throw new FatalErrorFoundException('Internal server error', 500);
        }
    }

    /**
     * Apply filters and sorting to the query.
     *
     * @param  Builder<Model>  $query
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $sorts
     * @return Builder<Model>
     */
    protected function applyFiltersAndSorting(
        Builder $query,
        array $filters,
        array $sorts,
        Operation $operation,
    ): Builder {
        foreach ($filters as $key => $value) {
            $parameter = $this->getParameter($key, $operation);

            if ($parameter instanceof Parameter) {
                $this->applyFilter($query, $key, $value, $parameter);
            }
        }

        $orderFilter = new OrderFilter;

        foreach ($sorts as $sort => $direction) {
            $parameter = $this->getParameter($sort, $operation);

            if ($parameter instanceof Parameter && in_array(strtolower($direction), ['asc', 'desc'], true)) {
                $orderFilter->apply($query, $direction, $parameter);
            }
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     *
     * @param  Builder<Model>  $query
     */
    protected function applyFilter(Builder $query, string $key, mixed $value, Parameter $parameter): void
    {
        $filters = [
            PartialSearchFilter::class => new PartialSearchFilter,
            DateFilter::class => new DateFilter,
            EqualsFilter::class => new EqualsFilter,
            RangeFilter::class => new RangeFilter,
            EndSearchFilter::class => new EndSearchFilter,
            StartSearchFilter::class => new StartSearchFilter,
            OrFilter::class => new OrFilter(new EqualsFilter),
        ];

        if ($parameter->getFilter() === MultiSearchFilter::class) {
            $multiSearchFilter = new MultiSearchFilter($parameter->getExtraProperties());
            $multiSearchFilter->apply($query, $value);

            return;
        }

        if ($parameter->getFilter() === RelationshipFilter::class) {
            $relationshipFilter = new RelationshipFilter($parameter->getExtraProperties());
            $relationshipFilter->apply($query, $value);

            return;
        }

        foreach ($filters as $filterClass => $filterInstance) {
            if ($parameter->getFilter() === $filterClass) {
                $filterInstance->apply($query, $value, $parameter);

                return;
            }
        }

        $query->where($key, '=', $value);
    }

    private function getParameter(string $key, Operation $operation): ?Parameter
    {
        if ($operation->getParameters() instanceof Parameters) {
            foreach ($operation->getParameters() as $parameter) {
                if ($parameter->getKey() === $key) {
                    return $parameter;
                }
            }
        }

        return null;
    }
}
