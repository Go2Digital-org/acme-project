<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Event;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Export\Domain\ValueObject\ExportId;
use Modules\Export\Domain\ValueObject\ExportProgress;

final readonly class ExportProgressUpdated
{
    public function __construct(
        public ExportId $exportId,
        public ExportProgress $progress,
        public DateTimeInterface $occurredAt = new DateTimeImmutable
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->exportId->toString(),
            'progress' => $this->progress->toArray(),
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getEventName(): string
    {
        return 'export.progress_updated';
    }

    public function getAggregateId(): string
    {
        return $this->exportId->toString();
    }

    public function getPercentage(): int
    {
        return $this->progress->percentage;
    }

    public function getMessage(): string
    {
        return $this->progress->message;
    }

    public function getProcessedRecords(): int
    {
        return $this->progress->processedRecords;
    }

    public function getTotalRecords(): int
    {
        return $this->progress->totalRecords;
    }
}
