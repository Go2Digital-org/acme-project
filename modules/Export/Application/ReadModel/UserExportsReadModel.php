<?php

declare(strict_types=1);

namespace Modules\Export\Application\ReadModel;

use Modules\Export\Application\DTO\ExportListDTO;
use Modules\Shared\Application\ReadModel\AbstractReadModel;

final class UserExportsReadModel extends AbstractReadModel
{
    protected int $cacheTtl = 300; // 5 minutes for user exports list

    /**
     * @return array<int, ExportListDTO>
     */
    public function getExports(): array
    {
        return $this->get('exports', []);
    }

    public function getTotalExports(): int
    {
        return count($this->getExports());
    }

    public function hasActiveExports(): bool
    {
        foreach ($this->getExports() as $export) {
            if ($export->isProcessing()) {
                return true;
            }
        }

        return false;
    }

    public function getActiveExportsCount(): int
    {
        $count = 0;
        foreach ($this->getExports() as $export) {
            if ($export->isProcessing()) {
                $count++;
            }
        }

        return $count;
    }

    public function getCompletedExportsCount(): int
    {
        $count = 0;
        foreach ($this->getExports() as $export) {
            if ($export->isCompleted()) {
                $count++;
            }
        }

        return $count;
    }

    public function getFailedExportsCount(): int
    {
        $count = 0;
        foreach ($this->getExports() as $export) {
            if ($export->isFailed()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array<int, ExportListDTO>
     */
    public function getRecentExports(int $limit = 5): array
    {
        return array_slice($this->getExports(), 0, $limit);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->getId(),
            'exports' => array_map(fn (ExportListDTO $export): array => $export->toArray(), $this->getExports()),
            'total_exports' => $this->getTotalExports(),
            'has_active_exports' => $this->hasActiveExports(),
            'active_exports_count' => $this->getActiveExportsCount(),
            'completed_exports_count' => $this->getCompletedExportsCount(),
            'failed_exports_count' => $this->getFailedExportsCount(),
            'recent_exports' => array_map(fn (ExportListDTO $export): array => $export->toArray(), $this->getRecentExports()),
            'version' => $this->getVersion(),
        ];
    }
}
