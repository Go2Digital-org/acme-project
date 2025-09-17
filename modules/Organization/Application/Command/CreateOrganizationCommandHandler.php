<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Command;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\Shared\Domain\Event\EventBusInterface;

class CreateOrganizationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function handle(CommandInterface $command): Organization
    {
        if (! $command instanceof CreateOrganizationCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        return DB::transaction(function () use ($command): Organization {
            // Check for duplicate organization names
            if ($this->repository->findByName($command->name) instanceof Organization) {
                throw OrganizationException::nameAlreadyExists($command->name);
            }

            // Check for duplicate registration numbers if provided
            if ($command->registrationNumber !== null && $this->repository->findByRegistrationNumber($command->registrationNumber) instanceof Organization) {
                throw OrganizationException::registrationNumberAlreadyExists($command->registrationNumber);
            }

            // Check for duplicate tax IDs if provided
            if ($command->taxId !== null && $this->repository->findByTaxId($command->taxId) instanceof Organization) {
                throw OrganizationException::taxIdAlreadyExists($command->taxId);
            }

            // Create organization
            $organization = $this->repository->create([
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
                'is_verified' => false,
                'is_active' => true,
            ]);

            // Publish domain event through event bus
            $event = new OrganizationCreatedEvent(
                organizationId: $organization->id,
                name: $command->name,
                category: $command->category,
            );

            $this->eventBus->publishAsync($event);

            return $organization;
        });
    }
}
