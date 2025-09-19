<?php

declare(strict_types=1);

namespace Modules\Import\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Import\Domain\Model\Import;

interface ImportRepositoryInterface
{
    public function findById(int $id): ?Import;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Import;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateById(int $id, array $data): bool;

    public function deleteById(int $id): bool;

    /**
     * @return Collection<int, Import>
     */
    public function getByUserId(int $userId): Collection;

    /**
     * @return Collection<int, Import>
     */
    public function getPendingImports(): Collection;

    /**
     * @return Collection<int, Import>
     */
    public function getProcessingImports(): Collection;
}
