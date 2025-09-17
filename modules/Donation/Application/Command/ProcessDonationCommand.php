<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

/**
 * Process Donation Command.
 *
 * Command to process a donation with payment transaction details.
 */
final readonly class ProcessDonationCommand implements CommandInterface
{
    public function __construct(
        public int $donationId,
        public string $transactionId,
        public ?string $gatewayResponseId = null,
    ) {}
}
