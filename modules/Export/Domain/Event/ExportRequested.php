<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Event;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Domain\ValueObject\ExportId;

final readonly class ExportRequested
{
    public function __construct(
        public ExportId $exportId,
        public int $userId,
        public int $organizationId,
        public string $resourceType,
        public ExportFormat $format,
        /** @var array<string, mixed> */
        public array $filters,
        public DateTimeInterface $occurredAt = new DateTimeImmutable
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->exportId->toString(),
            'user_id' => $this->userId,
            'organization_id' => $this->organizationId,
            'resource_type' => $this->resourceType,
            'format' => $this->format->value,
            'filters' => $this->filters,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getEventName(): string
    {
        return 'export.requested';
    }

    public function getAggregateId(): string
    {
        return $this->exportId->toString();
    }
}
