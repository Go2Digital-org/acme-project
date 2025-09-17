<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Organization\Domain\Event\OrganizationDeactivatedEvent;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class DeactivateOrganizationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Organization
    {
        if (! $command instanceof DeactivateOrganizationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Organization {
            $organization = $this->repository->findById($command->organizationId);

            if (! $organization instanceof Organization) {
                throw OrganizationException::notFound($command->organizationId);
            }

            // Deactivate using domain logic
            $organization->deactivate();
            $organization->save();

            // Dispatch domain event
            event(new OrganizationDeactivatedEvent(
                organizationId: $organization->id,
                reason: $command->reason ?? 'Organization deactivated',
                deactivatedAt: now(),
            ));

            return $organization;
        });
    }
}
