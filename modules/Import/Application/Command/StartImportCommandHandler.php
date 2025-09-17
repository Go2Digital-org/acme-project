<?php

declare(strict_types=1);

namespace Modules\Import\Application\Command;

use Modules\Import\Domain\Model\ImportJob;
use Modules\Import\Domain\Repository\ImportJobRepositoryInterface;
use Modules\Import\Domain\Service\ImportService;
use Modules\Import\Domain\ValueObject\ImportStatus;
use Modules\Import\Domain\ValueObject\ImportType;

final readonly class StartImportCommandHandler
{
    public function __construct(
        private ImportService $importService,
        private ImportJobRepositoryInterface $importJobRepository
    ) {}

    public function handle(StartImportCommand $command): ImportJob
    {
        $importJob = new ImportJob(
            id: null, // Will be generated
            type: new ImportType($command->type),
            filePath: $command->filePath,
            mapping: $command->mapping,
            options: $command->options,
            organizationId: (int) $command->organizationId,
            status: ImportStatus::pending()
        );

        $this->importJobRepository->save($importJob);

        // Start the import process asynchronously
        $this->importService->startImport($importJob);

        return $importJob;
    }
}
