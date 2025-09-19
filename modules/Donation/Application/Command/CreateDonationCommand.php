<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class CreateDonationCommand implements CommandInterface
{
    public function __construct(
        public int $campaignId,
        public ?int $userId,
        public float $amount,
        public string $currency,
        public string $paymentMethod,
        public ?string $paymentGateway,
        public bool $anonymous = false,
        public bool $recurring = false,
        public ?string $recurringFrequency = null,
        public ?string $notes = null,
    ) {}
}
