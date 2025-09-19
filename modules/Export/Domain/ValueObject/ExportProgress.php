<?php

declare(strict_types=1);

namespace Modules\Export\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ExportProgress
{
    private function __construct(
        public int $percentage,
        public string $message,
        public int $processedRecords = 0,
        public int $totalRecords = 0
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100');
        }

        if ($processedRecords < 0) {
            throw new InvalidArgumentException('Processed records cannot be negative');
        }

        if ($totalRecords < 0) {
            throw new InvalidArgumentException('Total records cannot be negative');
        }

        if ($processedRecords > $totalRecords && $totalRecords > 0) {
            throw new InvalidArgumentException('Processed records cannot exceed total records');
        }
    }

    public static function start(string $message = 'Starting export...', int $totalRecords = 0): self
    {
        return new self(0, $message, 0, $totalRecords);
    }

    public static function create(
        int $percentage,
        string $message,
        int $processedRecords = 0,
        int $totalRecords = 0
    ): self {
        return new self($percentage, $message, $processedRecords, $totalRecords);
    }

    public static function fromRecords(int $processedRecords, int $totalRecords, string $message = ''): self
    {
        $percentage = $totalRecords === 0 ? 0 : (int) round(($processedRecords / $totalRecords) * 100);

        $defaultMessage = $message ?: "Processing {$processedRecords} of {$totalRecords} records";

        return new self($percentage, $defaultMessage, $processedRecords, $totalRecords);
    }

    public static function completed(string $message = 'Export completed successfully'): self
    {
        return new self(100, $message);
    }

    public function isStarted(): bool
    {
        return $this->percentage > 0;
    }

    public function isCompleted(): bool
    {
        return $this->percentage === 100;
    }

    public function advance(int $recordsProcessed, string $message = ''): self
    {
        $newProcessedRecords = $this->processedRecords + $recordsProcessed;
        $newMessage = $message ?: $this->message;

        return self::fromRecords($newProcessedRecords, $this->totalRecords, $newMessage);
    }

    public function withMessage(string $message): self
    {
        return new self($this->percentage, $message, $this->processedRecords, $this->totalRecords);
    }

    public function getRemainingRecords(): int
    {
        return max(0, $this->totalRecords - $this->processedRecords);
    }

    public function getProgressBar(int $width = 20): string
    {
        $filled = (int) round(($this->percentage / 100) * $width);
        $empty = $width - $filled;

        return '[' . str_repeat('=', $filled) . str_repeat('-', $empty) . ']';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'percentage' => $this->percentage,
            'message' => $this->message,
            'processed_records' => $this->processedRecords,
            'total_records' => $this->totalRecords,
            'remaining_records' => $this->getRemainingRecords(),
        ];
    }
}
