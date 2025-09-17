<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Organization\Domain\Event\OrganizationUpdatedEvent;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;

class UpdateOrganizationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Organization
    {
        if (! $command instanceof UpdateOrganizationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Organization {
            $organization = $this->repository->findById($command->organizationId);

            if (! $organization instanceof Organization) {
                throw OrganizationException::notFound($command->organizationId);
            }

            // Check for duplicate organization names (excluding current organization)
            $existingByName = $this->repository->findByName($command->name);

            if ($existingByName instanceof Organization && $existingByName->id !== $command->organizationId) {
                throw OrganizationException::nameAlreadyExists($command->name);
            }

            // Check for duplicate registration numbers if provided
            if ($command->registrationNumber !== null) {
                $existingByRegistration = $this->repository->findByRegistrationNumber($command->registrationNumber);

                if ($existingByRegistration instanceof Organization && $existingByRegistration->id !== $command->organizationId) {
                    throw OrganizationException::registrationNumberAlreadyExists($command->registrationNumber);
                }
            }

            // Check for duplicate tax IDs if provided
            if ($command->taxId !== null) {
                $existingByTaxId = $this->repository->findByTaxId($command->taxId);

                if ($existingByTaxId instanceof Organization && $existingByTaxId->id !== $command->organizationId) {
                    throw OrganizationException::taxIdAlreadyExists($command->taxId);
                }
            }

            // Update organization
            $this->repository->updateById($command->organizationId, [
                'name' => $command->name,
                'registration_number' => $command->registrationNumber,
                'tax_id' => $command->taxId,
                'category' => $command->category,
                'website' => $command->website,
                'email' => $command->email,
                'phone' => $command->phone,
                'address' => $command->address,
                'city' => $command->city,
                'country' => $command->country,
            ]);

            // Refresh model
            $updatedOrganization = $this->repository->findById($command->organizationId);

            if (! $updatedOrganization instanceof Organization) {
                throw OrganizationException::notFound($command->organizationId);
            }

            // Dispatch domain event
            event(new OrganizationUpdatedEvent(
                organizationId: $organization->id,
                name: $command->name,
            ));

            return $updatedOrganization;
        });
    }
}
