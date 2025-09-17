<?php

declare(strict_types=1);

namespace Modules\Import\Application\Query;

use Modules\Import\Application\ReadModel\ImportJobReadModel;
use Modules\Import\Domain\Model\ImportJob;
use Modules\Import\Domain\Repository\ImportJobRepositoryInterface;

final readonly class GetImportStatusQueryHandler
{
    public function __construct(
        private ImportJobRepositoryInterface $importJobRepository
    ) {}

    public function handle(GetImportStatusQuery $query): ?ImportJobReadModel
    {
        $importJob = $this->importJobRepository->findById((int) $query->importJobId);

        if (! $importJob instanceof ImportJob) {
            return null;
        }

        return ImportJobReadModel::fromDomainModel($importJob);
    }
}
