<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Campaign\Application\Command\UpdateCampaignCommand;
use Modules\Campaign\Application\Query\FindCampaignByIdQuery;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\CampaignResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Modules\Shared\Application\Query\QueryBusInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<object, CampaignResource>
 */
final readonly class PatchCampaignProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private QueryBusInterface $queryBus,
        private Guard $auth,
        private Request $request,
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

        $campaignId = (int) $uriVariables['id'];

        // First, get the existing campaign to merge with partial data
        try {
            $findQuery = new FindCampaignByIdQuery($campaignId);
            $existingCampaign = $this->queryBus->ask($findQuery);
        } catch (CampaignException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        // Merge existing data with the provided data for partial updates
        $command = new UpdateCampaignCommand(
            campaignId: $campaignId,
            title: property_exists($data, 'title') && is_array($data->title)
                ? $data->title
                : (property_exists($data, 'title')
                    ? ['en' => (string) $data->title]
                    : $existingCampaign->getTranslations('title')),
            description: property_exists($data, 'description') && is_array($data->description)
                ? $data->description
                : (property_exists($data, 'description')
                    ? ['en' => (string) $data->description]
                    : $existingCampaign->getTranslations('description')),
            goalAmount: property_exists($data, 'goal_amount') ? (float) $data->goal_amount : $existingCampaign->goal_amount,
            startDate: property_exists($data, 'start_date') ? (string) $data->start_date : $existingCampaign->start_date->toDateTimeString(),
            endDate: property_exists($data, 'end_date') ? (string) $data->end_date : $existingCampaign->end_date->toDateTimeString(),
            organizationId: property_exists($data, 'organization_id') ? (int) $data->organization_id : $existingCampaign->organization_id,
            employeeId: $user->id,
            locale: $this->request->get('locale', 'en'),
        );

        try {
            $this->commandBus->handle($command);
        } catch (CampaignException $e) {
            // Check for authorization errors
            if (str_contains($e->getMessage(), 'Unauthorized access')) {
                throw new AccessDeniedHttpException($e->getMessage());
            }

            throw new AccessDeniedHttpException($e->getMessage());
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::find($campaignId);

        if (! $campaign) {
            throw new InvalidArgumentException('Campaign not found after update');
        }

        return CampaignResource::fromModel($campaign);
    }
}
