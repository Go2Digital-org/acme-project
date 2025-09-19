<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Log;
use Modules\Campaign\Application\Command\UpdateCampaignCommand;
use Modules\Campaign\Domain\Exception\CampaignException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\ApiPlatform\Resource\CampaignResource;
use Modules\Shared\Application\Command\CommandBusInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @implements ProcessorInterface<object, CampaignResource>
 */
final readonly class UpdateCampaignProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private Guard $auth,
        private Request $request,
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
    ): CampaignResource {
        // Debug logging
        Log::info('UpdateCampaignProcessor called', [
            'uriVariables' => $uriVariables,
            'data' => $data,
            'user' => $this->auth->user()?->id,
        ]);

        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        $user = $this->auth->user();

        if (! $user) {
            throw new UnauthorizedHttpException('', 'Authentication required');
        }

        $campaignId = (int) ($uriVariables['id'] ?? 0);

        if ($campaignId === 0) {
            throw new NotFoundHttpException('Campaign ID is required');
        }

        // Debug: Check if we can find the campaign first
        $existingCampaign = Campaign::find($campaignId);
        if (! $existingCampaign) {
            throw new NotFoundHttpException("Campaign with ID {$campaignId} not found");
        }

        $command = new UpdateCampaignCommand(
            campaignId: $campaignId,
            title: property_exists($data, 'title') && is_array($data->title)
                ? $data->title
                : (property_exists($data, 'title') ? ['en' => (string) $data->title] : ['en' => '']),
            description: property_exists($data, 'description') && is_array($data->description)
                ? $data->description
                : (property_exists($data, 'description') ? ['en' => (string) $data->description] : ['en' => '']),
            goalAmount: property_exists($data, 'goal_amount') ? (float) $data->goal_amount : 0.0,
            startDate: property_exists($data, 'start_date') ? (string) $data->start_date : now()->toDateString(),
            endDate: property_exists($data, 'end_date') ? (string) $data->end_date : now()->addMonth()->toDateString(),
            organizationId: property_exists($data, 'organization_id') ? (int) $data->organization_id : 0,
            userId: $user->id,
            locale: $this->request->get('locale', 'en'),
        );

        try {
            $this->commandBus->handle($command);
        } catch (CampaignException $e) {
            // Check if it's a "not found" error
            if (str_contains($e->getMessage(), 'not found')) {
                throw new NotFoundHttpException($e->getMessage());
            }

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
