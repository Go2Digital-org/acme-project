<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Event;

use DateTimeInterface;
use Modules\Shared\Application\Event\AbstractDomainEvent;

final class OrganizationVerifiedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $organizationId,
        public readonly int $verifiedBy,
        public readonly DateTimeInterface $verifiedAt,
    ) {
        parent::__construct($this->organizationId);
    }

    public function getEventName(): string
    {
        return 'organization.verified';
    }

    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'organization_id' => $this->organizationId,
            'verified_by' => $this->verifiedBy,
            'verified_at' => $this->verifiedAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function isAsync(): bool
    {
        return true;
    }
}
