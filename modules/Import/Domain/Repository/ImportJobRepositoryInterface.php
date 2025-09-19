<?php

declare(strict_types=1);

namespace Modules\Import\Domain\Repository;

use Modules\Import\Domain\Model\ImportJob;
use Modules\Import\Domain\ValueObject\ImportStatus;
use Modules\Import\Domain\ValueObject\ImportType;

interface ImportJobRepositoryInterface
{
    public function save(ImportJob $importJob): ImportJob;

    public function findById(int $id): ?ImportJob;

    /**
     * @return array<int, ImportJob>
     */
    public function findByOrganizationId(int $organizationId): array;

    /**
     * @return array<int, ImportJob>
     */
    public function findByStatus(ImportStatus $status): array;

    /**
     * @return array<int, ImportJob>
     */
    public function findByType(ImportType $type): array;

    /**
     * @return array<int, ImportJob>
     */
    public function findByOrganizationIdAndStatus(int $organizationId, ImportStatus $status): array;

    /**
     * @return array<int, ImportJob>
     */
    public function findRecentByOrganizationId(int $organizationId, int $limit = 10): array;

    public function delete(ImportJob $importJob): bool;

    public function deleteById(int $id): bool;
}
