<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MultiSearchFilter
{
    /** @param array<string> $allowedFields */
    public function __construct(protected array $allowedFields) {}

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, string>  $filters
     */
    public function apply(Builder $query, array $filters): void
    {
        $query->where(function (Builder $subQuery) use ($filters): void {
            foreach ($filters as $field => $value) {
                $fields = array_map(fn (string $f): string => trim($f), explode(',', $field));

                foreach ($fields as $singleField) {
                    if (! in_array($singleField, $this->allowedFields, true)) {
                        throw new InvalidArgumentException("Field \"{$singleField}\" is not allowed.");
                    }
                }

                $isNegation = str_starts_with($value, '-');
                $term = ltrim($value, '-');
                $operator = $isNegation ? 'NOT LIKE' : 'LIKE';

                $subQuery->orWhere(function (Builder $subSubQuery) use ($fields, $term, $operator): void {
                    foreach ($fields as $singleField) {
                        $subSubQuery->orWhere($singleField, $operator, '%' . $term . '%');
                    }
                });
            }
        });
    }
}
