<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\ReadModel;

final class DomainAnalysisReadModel
{
    /**
     * @param  array<string, mixed>  $entities
     * @param  array<string, mixed>  $valueObjects
     * @param  array<string, mixed>  $aggregates
     * @param  array<string, mixed>  $services
     * @param  array<string, mixed>  $repositories
     * @param  array<string, mixed>  $events
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>  $violations
     */
    public function __construct(
        public string $module,
        public array $entities,
        public array $valueObjects,
        public array $aggregates,
        public array $services,
        public array $repositories,
        public array $events,
        public array $metrics,
        public array $violations,
        public string $timestamp
    ) {}
}
