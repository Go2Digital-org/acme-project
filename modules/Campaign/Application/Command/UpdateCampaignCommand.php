<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdateCampaignCommand implements CommandInterface
{
    /**
     * @param  array<string, string>  $title
     * @param  array<string, string>  $description
     */
    public function __construct(
        public int $campaignId,
        public array $title,
        public array $description,
        public float $goalAmount,
        public string $startDate,
        public string $endDate,
        public int $organizationId,
        public int $employeeId,
        public ?string $locale = 'en',
    ) {}
}
