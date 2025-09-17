<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\Organization\Application\Query\FindOrganizationByIdQuery;
use Modules\Organization\Infrastructure\ApiPlatform\Resource\OrganizationResource;
use Modules\Shared\Application\Query\QueryBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProviderInterface<OrganizationResource>
 */
final readonly class OrganizationItemProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): OrganizationResource {
        $id = $uriVariables['id'];
        Assert::notNull($id);
        $organizationId = (int) $id;

        $model = $this->queryBus->ask(new FindOrganizationByIdQuery($organizationId));

        return OrganizationResource::fromModel($model);
    }
}
