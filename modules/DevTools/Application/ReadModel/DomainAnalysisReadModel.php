<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\ReadModel;

final class DomainAnalysisReadModel
{
    public function __construct(
        public string $module,
        /** @var array<string, mixed> */
        public array $entities,
        /** @var array<string, mixed> */
        public array $valueObjects,
        /** @var array<string, mixed> */
        public array $aggregates,
        /** @var array<string, mixed> */
        public array $services,
        /** @var array<string, mixed> */
        public array $repositories,
        /** @var array<string, mixed> */
        public array $events,
        /** @var array<string, mixed> */
        public array $metrics,
        /** @var array<string, mixed> */
        public array $violations,
        public string $timestamp
    ) {}
}
