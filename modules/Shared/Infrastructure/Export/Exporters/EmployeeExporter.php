<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Export\Exporters;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * @implements WithMapping<User>
 */
final readonly class EmployeeExporter implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function create(array $filters = []): self
    {
        return new self;
    }

    /**
     * @return Builder<User>
     */
    public function query(): Builder
    {
        // Apply filters if provided
        // Department filtering removed as department field was removed from User model

        return User::query();
    }

    /**
     * @param  User  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->email,
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
            'Email',
            'Created At',
        ];
    }
}
