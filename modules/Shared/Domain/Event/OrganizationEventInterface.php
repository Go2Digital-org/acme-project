<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Event;

/**
 * Contract for organization-related events.
 */
interface OrganizationEventInterface extends DomainEventInterface
{
    public function getOrganizationId(): int;

    public function getEntityType(): string;

    public function getEntityId(): int;
}
