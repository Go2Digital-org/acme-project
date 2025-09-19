<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;
use Modules\Shared\Domain\Event\OrganizationEventInterface;

final class OrganizationUpdatedEvent extends AbstractDomainEvent implements OrganizationEventInterface
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $name,
    ) {
        parent::__construct($this->organizationId);
    }

    public function getEventName(): string
    {
        return 'organization.updated';
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    public function getEntityType(): string
    {
        return 'organization';
    }

    public function getEntityId(): int
    {
        return $this->organizationId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'organization_id' => $this->organizationId,
            'name' => $this->name,
        ]);
    }

    public function isAsync(): bool
    {
        return true;
    }
}
