<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class UpdatePaymentStatusCommand implements CommandInterface
{
    /**
     * @param  array<string, mixed>|null  $gatewayResponse
     */
    public function __construct(
        public int $donationId,
        public string $status,
        public ?string $externalTransactionId = null,
        public ?array $gatewayResponse = null,
        public ?string $failureReason = null,
        public ?float $processingFee = null,
    ) {}
}
