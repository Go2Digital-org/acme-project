<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Command;

use Modules\Shared\Application\Command\CommandInterface;

final readonly class TrackEventCommand implements CommandInterface
{
    public function __construct(
        public string $eventType,
        public string $eventName,
        public ?int $userId = null,
        public ?int $organizationId = null,
        public ?int $campaignId = null,
        public ?int $donationId = null,
        /** @var array<string, mixed> */
        public array $properties = [],
        public ?string $sessionId = null,
        public ?string $userAgent = null,
        public ?string $ipAddress = null,
        public ?string $referrer = null,
        public ?string $timestamp = null,
    ) {}
}
