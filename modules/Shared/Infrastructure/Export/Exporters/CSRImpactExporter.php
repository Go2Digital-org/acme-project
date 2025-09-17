<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Export\Exporters;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

final readonly class CSRImpactExporter implements FromCollection, WithHeadings
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
     * @return Collection<int, array<string, mixed>>
     */
    public function collection(): Collection
    {
        // Get period from filters or use default
        $period = $this->filters['period'] ?? 'Q1 2024';

        // Return sample CSR impact data for now
        $data = [
            [
                'metric' => 'Total Donations',
                'value' => '50000.00',
                'period' => $period,
            ],
            [
                'metric' => 'Active Campaigns',
                'value' => '25',
                'period' => $period,
            ],
        ];

        /** @var Collection<int, array<string, mixed>> */
        return collect($data);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Period',
        ];
    }
}
