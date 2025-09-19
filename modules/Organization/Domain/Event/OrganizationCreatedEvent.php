<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Event;

use Modules\Shared\Application\Event\AbstractDomainEvent;

final class OrganizationCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly int $organizationId,
        public readonly string $name,
        public readonly ?string $category,
    ) {
        parent::__construct($this->organizationId);
    }

    public function getEventName(): string
    {
        return 'organization.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function getEventData(): array
    {
        return array_merge(parent::getEventData(), [
            'organization_id' => $this->organizationId,
            'name' => $this->name,
            'category' => $this->category,
        ]);
    }

    public function isAsync(): bool
    {
        // Organization creation can be processed asynchronously
        return true;
    }
}
