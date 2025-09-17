<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Modules\Donation\Application\Query\FindDonationByIdQuery;
use Modules\Donation\Infrastructure\ApiPlatform\Resource\DonationResource;
use Modules\Shared\Application\Query\QueryBusInterface;
use Webmozart\Assert\Assert;

/**
 * @implements ProviderInterface<DonationResource>
 */
final readonly class DonationItemProvider implements ProviderInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): DonationResource {
        $id = $uriVariables['id'];
        Assert::notNull($id);
        $donationId = (int) $id;

        $model = $this->queryBus->ask(new FindDonationByIdQuery($donationId));

        return DonationResource::fromModel($model);
    }
}
