<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Organization\Domain\Event\OrganizationVerifiedEvent;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class VerifyOrganizationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Organization
    {
        if (! $command instanceof VerifyOrganizationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Organization {
            $organization = $this->repository->findById($command->organizationId);

            if (! $organization instanceof Organization) {
                throw OrganizationException::notFound($command->organizationId);
            }

            // Check if organization is eligible for verification
            if (! $organization->isEligibleForVerification()) {
                throw OrganizationException::notEligibleForVerification($organization);
            }

            // Verify using domain logic
            $organization->verify();
            $organization->save();

            // Dispatch domain event
            event(new OrganizationVerifiedEvent(
                organizationId: $organization->id,
                verifiedBy: $command->verifiedByEmployeeId ?? 0, // Default system verification
                verifiedAt: now(),
            ));

            return $organization;
        });
    }
}
