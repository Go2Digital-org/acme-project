<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Contracts\Auth\Guard;
use InvalidArgumentException;
use Modules\Campaign\Application\Command\CreateCampaignCommand;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\CampaignResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<object, CampaignResource>
 */
final readonly class CreateCampaignProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Guard $auth,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CampaignResource {
        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        $user = $this->auth->user();

        if (! $user) {
            throw new UnauthorizedHttpException('', 'Authentication required');
        }

        // Extract dates with proper validation
        $startDate = property_exists($data, 'start_date') && ! empty($data->start_date)
            ? (string) $data->start_date
            : now()->toDateTimeString();

        $endDate = property_exists($data, 'end_date') && ! empty($data->end_date)
            ? (string) $data->end_date
            : now()->addMonth()->toDateTimeString();

        $command = new CreateCampaignCommand(
            title: property_exists($data, 'title') && is_array($data->title)
                ? $data->title
                : ['en' => (property_exists($data, 'title') ? (string) $data->title : '')],
            description: property_exists($data, 'description') && is_array($data->description)
                ? $data->description
                : ['en' => (property_exists($data, 'description') ? (string) $data->description : '')],
            goalAmount: property_exists($data, 'goal_amount') && $data->goal_amount !== null ? (float) $data->goal_amount : 1.0,
            startDate: $startDate,
            endDate: $endDate,
            organizationId: property_exists($data, 'organization_id') ? (int) $data->organization_id : 0,
            userId: $user->id,
        );

        /** @var Campaign $campaign */
        $campaign = $this->commandBus->handle($command);

        return CampaignResource::fromModel($campaign);
    }
}
