<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Modules\Organization\Application\Command\UpdateOrganizationCommand;
use Modules\Organization\Infrastructure\ApiPlatform\Resource\OrganizationResource;
use Modules\Shared\Application\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<object, OrganizationResource>
 */
final readonly class UpdateOrganizationProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): OrganizationResource {
        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        $organizationId = (int) $uriVariables['id'];

        $command = new UpdateOrganizationCommand(
            organizationId: $organizationId,
            name: (property_exists($data, 'name') ? $data->name : null),
            registrationNumber: (property_exists($data, 'registration_number') ? $data->registration_number : null),
            taxId: (property_exists($data, 'tax_id') ? $data->tax_id : null),
            category: (property_exists($data, 'category') ? $data->category : null),
            website: (property_exists($data, 'website') ? $data->website : null),
            email: (property_exists($data, 'email') ? $data->email : null),
            phone: (property_exists($data, 'phone') ? $data->phone : null),
            address: (property_exists($data, 'address') ? $data->address : null),
            city: (property_exists($data, 'city') ? $data->city : null),
            country: (property_exists($data, 'country') ? $data->country : null),
        );

        $organization = $this->commandBus->handle($command);

        return OrganizationResource::fromModel($organization);
    }
}
