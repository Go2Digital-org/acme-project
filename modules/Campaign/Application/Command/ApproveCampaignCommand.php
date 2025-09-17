<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class ApproveCampaignCommand implements CommandInterface
{
    public function __construct(
        public int $campaignId,
        public int $approverId,
        public ?string $notes = null,
    ) {}
}
