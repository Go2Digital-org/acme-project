<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Organization\Domain\Event\OrganizationActivatedEvent;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class ActivateOrganizationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Organization
    {
        if (! $command instanceof ActivateOrganizationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Organization {
            $organization = $this->repository->findById($command->organizationId);

            if (! $organization instanceof Organization) {
                throw OrganizationException::notFound($command->organizationId);
            }

            // Activate using domain logic
            $organization->activate();
            $organization->save();

            // Dispatch domain event
            event(new OrganizationActivatedEvent(
                organizationId: $organization->id,
                activatedAt: now(),
            ));

            return $organization;
        });
    }
}
