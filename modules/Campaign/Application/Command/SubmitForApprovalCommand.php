<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class SubmitForApprovalCommand implements CommandInterface
{
    public function __construct(
        public int $campaignId,
        public int $userId,
    ) {}
}
