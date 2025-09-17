<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Event;

use DateTimeInterface;
use Modules\Shared\Application\Event\AbstractDomainEvent;

final class OrganizationActivatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $organizationId,
        public readonly DateTimeInterface $activatedAt,
    ) {
        parent::__construct($this->organizationId);
    }

    public function getEventName(): string
    {
        return 'organization.activated';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'organization_id' => $this->organizationId,
            'activated_at' => $this->activatedAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function isAsync(): bool
    {
        return false; // Synchronous for immediate processing
    }
}
