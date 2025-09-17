<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use Modules\Organization\Application\Command\VerifyOrganizationCommand;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Organization\Infrastructure\ApiPlatform\Resource\OrganizationResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements ProcessorInterface<object, OrganizationResource>
 */
final readonly class VerifyOrganizationProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OrganizationRepositoryInterface $organizationRepository,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): OrganizationResource {
        $request = $context['request'] ?? null;

        if (! ($request instanceof Request)) {
            throw new InvalidArgumentException('Request context is required');
        }
        $user = $request->attributes->get('user');

        if (! ($user instanceof User)) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        $organizationId = (int) $uriVariables['id'];

        $command = new VerifyOrganizationCommand(
            organizationId: $organizationId,
            verifiedByEmployeeId: $user->id,
        );

        $this->commandBus->dispatch($command);

        $organization = $this->organizationRepository->findById($organizationId);

        if (! $organization instanceof Organization) {
            throw new InvalidArgumentException('Organization not found');
        }

        return OrganizationResource::fromModel($organization);
    }
}
