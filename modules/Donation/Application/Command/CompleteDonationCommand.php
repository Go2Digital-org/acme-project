<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class CompleteDonationCommand implements CommandInterface
{
    public function __construct(
        public int $donationId,
        public ?string $gatewayResponseId = null,
    ) {}
}
