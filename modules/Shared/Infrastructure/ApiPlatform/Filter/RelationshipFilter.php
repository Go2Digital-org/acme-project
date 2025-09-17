<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class RelationshipFilter
{
    protected string $relation;

    protected string $relatedField;

    /**
     * @param  array<string, mixed>  $extraProperties
     */
    public function __construct(array $extraProperties)
    {
        $this->relation = $extraProperties['relation'] ??
            throw new InvalidArgumentException('Relation not specified');
        $this->relatedField = $extraProperties['relatedField'] ??
            throw new InvalidArgumentException('Related field not specified');
    }

    /**
     * @param  Builder<Model>  $query
     */
    public function apply(Builder $query, string $value): void
    {
        $query->whereHas($this->relation, function (Builder $relatedQuery) use ($value): void {
            $relatedQuery->where($this->relatedField, 'LIKE', '%' . $value . '%');
        });
    }
}
