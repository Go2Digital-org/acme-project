<?php

declare(strict_types=1);

namespace Modules\Export\Application\DTO;

final readonly class ExportListDTO
{
    /**
     * @param  array<string, mixed>  $exports
     */
    public function __construct(
        /** @var array<string, mixed> */
        public array $exports,
        public int $totalCount,
        public int $currentPage,
        public int $perPage,
        public int $totalPages,
        public bool $hasMorePages
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => $this->exports,
            'meta' => [
                'total' => $this->totalCount,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page' => $this->totalPages,
                'has_more_pages' => $this->hasMorePages,
                'from' => $this->getFromRecord(),
                'to' => $this->getToRecord(),
            ],
            'links' => [
                'first' => 1,
                'last' => $this->totalPages,
                'prev' => $this->currentPage > 1 ? $this->currentPage - 1 : null,
                'next' => $this->hasMorePages ? $this->currentPage + 1 : null,
            ],
        ];
    }

    public function getFromRecord(): int
    {
        return $this->totalCount === 0
            ? 0
            : (($this->currentPage - 1) * $this->perPage) + 1;
    }

    public function getToRecord(): int
    {
        return $this->totalCount === 0
            ? 0
            : min($this->totalCount, $this->currentPage * $this->perPage);
    }

    public function isEmpty(): bool
    {
        return $this->exports === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $statusCounts = array_count_values(
            array_column($this->exports, 'status')
        );

        return [
            'total_exports' => $this->totalCount,
            'status_counts' => $statusCounts,
            'completed_exports' => $statusCounts['completed'] ?? 0,
            'pending_exports' => $statusCounts['pending'] ?? 0,
            'processing_exports' => $statusCounts['processing'] ?? 0,
            'failed_exports' => $statusCounts['failed'] ?? 0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<string, mixed>
     */
    public function getCompletedExports(): array
    {
        return array_filter(
            $this->exports,
            fn (array $export): bool => $export['status'] === 'completed'
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return array<string, mixed>
     */
    public function getActiveExports(): array
    {
        return array_filter(
            $this->exports,
            fn (array $export): bool => in_array($export['status'], ['pending', 'processing'])
        );
    }

    public function isProcessing(): bool
    {
        return in_array('processing', array_column($this->exports, 'status'));
    }

    public function isCompleted(): bool
    {
        return in_array('completed', array_column($this->exports, 'status'));
    }

    public function isFailed(): bool
    {
        return in_array('failed', array_column($this->exports, 'status'));
    }
}
