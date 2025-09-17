<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Export;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Organization\Domain\Model\Organization;

/**
 * @implements WithMapping<Organization>
 */
final readonly class OrganizationExporter implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(private array $filters = []) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(array $filters = []): self
    {
        return new self($filters);
    }

    /**
     * @return Builder<Organization>
     */
    public function query(): Builder
    {
        $query = Organization::query();

        // Apply filters if provided
        if (! empty($this->filters['status'])) {
            $query->where('is_verified', $this->filters['status'] === 'verified');
        }

        return $query;
    }

    /**
     * @param  Organization  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->category,
            $row->tax_id,
            $row->is_verified ? 'verified' : 'unverified',
            $row->created_at,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Category',
            'Tax ID',
            'Verification Status',
            'Created At',
        ];
    }
}
