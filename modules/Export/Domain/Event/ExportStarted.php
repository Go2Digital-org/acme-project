<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Event;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Export\Domain\ValueObject\ExportId;

final readonly class ExportStarted
{
    public function __construct(
        public ExportId $exportId,
        public int $totalRecords = 0,
        public DateTimeInterface $occurredAt = new DateTimeImmutable
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->exportId->toString(),
            'total_records' => $this->totalRecords,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getEventName(): string
    {
        return 'export.started';
    }

    public function getAggregateId(): string
    {
        return $this->exportId->toString();
    }
}
