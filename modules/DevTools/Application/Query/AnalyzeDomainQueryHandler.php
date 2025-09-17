<?php

declare(strict_types=1);

namespace Modules\DevTools\Application\Query;

use Modules\DevTools\Application\ReadModel\DomainAnalysisReadModel;
use Modules\DevTools\Domain\Service\DomainAnalysisService;

final readonly class AnalyzeDomainQueryHandler
{
    public function __construct(
        private DomainAnalysisService $domainAnalysisService
    ) {}

    public function handle(AnalyzeDomainQuery $query): DomainAnalysisReadModel
    {
        $analysis = $this->domainAnalysisService->analyzeModule($query->module);

        return new DomainAnalysisReadModel(
            module: $query->module,
            entities: $analysis['entities'] ?? [],
            valueObjects: $analysis['value_objects'] ?? [],
            aggregates: $analysis['aggregates'] ?? [],
            services: $analysis['services'] ?? [],
            repositories: $analysis['repositories'] ?? [],
            events: $analysis['events'] ?? [],
            metrics: $analysis['metrics'] ?? [],
            violations: $analysis['violations'] ?? [],
            timestamp: date('Y-m-d H:i:s')
        );
    }
}
