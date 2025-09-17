<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Event;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Export\Domain\ValueObject\ExportId;

final readonly class ExportFailed
{
    public function __construct(
        public ExportId $exportId,
        public string $errorMessage,
        public int $processedRecords = 0,
        public DateTimeInterface $occurredAt = new DateTimeImmutable
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->exportId->toString(),
            'error_message' => $this->errorMessage,
            'processed_records' => $this->processedRecords,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getEventName(): string
    {
        return 'export.failed';
    }

    public function getAggregateId(): string
    {
        return $this->exportId->toString();
    }

    public function getShortErrorMessage(int $maxLength = 100): string
    {
        if (strlen($this->errorMessage) <= $maxLength) {
            return $this->errorMessage;
        }

        return substr($this->errorMessage, 0, $maxLength - 3) . '...';
    }
}
