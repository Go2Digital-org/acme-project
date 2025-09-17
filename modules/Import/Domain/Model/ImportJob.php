<?php

declare(strict_types=1);

namespace Modules\Import\Domain\Model;

use DateTime;
use Modules\Import\Domain\ValueObject\ImportStatus;
use Modules\Import\Domain\ValueObject\ImportType;

final class ImportJob
{
    /**
     * @param  array<string, string>  $mapping
     * @param  array<string, mixed>  $options
     * @param  array<string>  $errors
     */
    public function __construct(
        private readonly ?int $id,
        private readonly ImportType $type,
        private readonly string $filePath,
        private readonly array $mapping,
        private readonly array $options,
        private readonly int $organizationId,
        private ImportStatus $status,
        private ?string $errorMessage = null,
        private ?int $processedRows = null,
        private ?int $totalRows = null,
        private readonly int $totalRecords = 0,
        private readonly int $processedRecords = 0,
        private readonly int $successfulRecords = 0,
        private readonly int $failedRecords = 0,
        private readonly array $errors = [],
        private ?DateTime $startedAt = null,
        private ?DateTime $completedAt = null,
        private readonly ?DateTime $createdAt = null,
        private readonly ?DateTime $updatedAt = null
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ImportType
    {
        return $this->type;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return array<string, string>
     */
    public function getMapping(): array
    {
        return $this->mapping;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOrganizationId(): string
    {
        return (string) $this->organizationId;
    }

    public function getStatus(): ImportStatus
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getProcessedRows(): ?int
    {
        return $this->processedRows;
    }

    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function markAsStarted(): void
    {
        $this->status = ImportStatus::processing();
        $this->startedAt = new DateTime;
    }

    public function markAsCompleted(int $processedRows, int $totalRows): void
    {
        $this->status = ImportStatus::completed();
        $this->processedRows = $processedRows;
        $this->totalRows = $totalRows;
        $this->completedAt = new DateTime;
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = ImportStatus::failed();
        $this->errorMessage = $errorMessage;
        $this->completedAt = new DateTime;
    }

    public function updateProgress(int $processedRows, int $totalRows): void
    {
        $this->processedRows = $processedRows;
        $this->totalRows = $totalRows;
    }

    public function getTotalRecords(): int
    {
        return $this->totalRecords;
    }

    public function getProcessedRecords(): int
    {
        return $this->processedRecords;
    }

    public function getSuccessfulRecords(): int
    {
        return $this->successfulRecords;
    }

    public function getFailedRecords(): int
    {
        return $this->failedRecords;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
