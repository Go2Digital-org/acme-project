<?php

declare(strict_types=1);

namespace Modules\Import\Application\ReadModel;

use Modules\Import\Domain\Model\ImportJob;

final class ImportJobReadModel
{
    /**
     * @param  array<string, mixed>  $mapping
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $filePath,
        /** @var array<string, mixed> */
        public array $mapping,
        /** @var array<string, mixed> */
        public array $options,
        public ?string $organizationId,
        public string $status,
        public int $totalRecords,
        public int $processedRecords,
        public int $successfulRecords,
        public int $failedRecords,
        /** @var array<int, string> */
        public array $errors,
        public ?string $startedAt,
        public ?string $completedAt,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}

    public static function fromDomainModel(ImportJob $importJob): self
    {
        return new self(
            id: (string) $importJob->getId(),
            type: (string) $importJob->getType(),
            filePath: $importJob->getFilePath(),
            mapping: $importJob->getMapping(),
            options: $importJob->getOptions(),
            organizationId: $importJob->getOrganizationId(),
            status: $importJob->getStatus()->value,
            totalRecords: $importJob->getTotalRecords(),
            processedRecords: $importJob->getProcessedRecords(),
            successfulRecords: $importJob->getSuccessfulRecords(),
            failedRecords: $importJob->getFailedRecords(),
            errors: $importJob->getErrors(),
            startedAt: $importJob->getStartedAt()?->format('Y-m-d H:i:s'),
            completedAt: $importJob->getCompletedAt()?->format('Y-m-d H:i:s'),
            createdAt: $importJob->getCreatedAt()?->format('Y-m-d H:i:s'),
            updatedAt: $importJob->getUpdatedAt()?->format('Y-m-d H:i:s')
        );
    }
}
