<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for repositories that can be exported.
 * Used by the export system to decouple from specific domain models.
 */
interface ExportableRepositoryInterface
{
    /**
     * Get a query builder for export data.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<Model>
     */
    public function getExportQuery(array $filters = []): Builder;
}
