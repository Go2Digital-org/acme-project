<?php

declare(strict_types=1);

namespace Modules\Export\Domain\ValueObject;

enum ExportFormat: string
{
    case CSV = 'csv';
    case EXCEL = 'excel';
    case PDF = 'pdf';

    public function getExtension(): string
    {
        return match ($this) {
            self::CSV => 'csv',
            self::EXCEL => 'xlsx',
            self::PDF => 'pdf',
        };
    }

    public function getFileExtension(): string
    {
        return $this->getExtension();
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::CSV => 'text/csv',
            self::EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::PDF => 'application/pdf',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CSV => 'CSV',
            self::EXCEL => 'Excel',
            self::PDF => 'PDF',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CSV => 'heroicon-o-table-cells',
            self::EXCEL => 'heroicon-o-document-chart-bar',
            self::PDF => 'heroicon-o-document-text',
        };
    }

    public function supportsMultipleSheets(): bool
    {
        return $this === self::EXCEL;
    }

    public function supportsImages(): bool
    {
        return in_array($this, [self::EXCEL, self::PDF]);
    }

    public function getMaxFileSizeMB(): int
    {
        return match ($this) {
            self::CSV => 50,
            self::EXCEL => 100,
            self::PDF => 25,
        };
    }
}
