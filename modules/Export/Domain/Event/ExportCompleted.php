<?php

declare(strict_types=1);

namespace Modules\Export\Domain\Event;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Export\Domain\ValueObject\ExportId;

final readonly class ExportCompleted
{
    public function __construct(
        public ExportId $exportId,
        public string $filePath,
        public int $fileSize,
        public int $recordsExported,
        public DateTimeInterface $occurredAt = new DateTimeImmutable
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'export_id' => $this->exportId->toString(),
            'file_path' => $this->filePath,
            'file_size' => $this->fileSize,
            'records_exported' => $this->recordsExported,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
        ];
    }

    public function getEventName(): string
    {
        return 'export.completed';
    }

    public function getAggregateId(): string
    {
        return $this->exportId->toString();
    }

    public function getFileSizeFormatted(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->fileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
