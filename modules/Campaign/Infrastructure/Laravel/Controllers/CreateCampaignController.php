<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Campaign\Application\Command\CreateCampaignCommand;
use Modules\Campaign\Application\Command\CreateCampaignCommandHandler;
use Modules\Campaign\Application\Request\CreateCampaignRequest;
use Modules\Campaign\Infrastructure\Laravel\Resource\CampaignResource;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class CreateCampaignController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CreateCampaignCommandHandler $handler,
    ) {}

    /**
     * Create a new fundraising campaign.
     */
    public function __invoke(CreateCampaignRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $command = new CreateCampaignCommand(
            title: is_array($validated['title']) ? $validated['title'] : ['en' => (string) $validated['title']],
            description: is_array($validated['description']) ? $validated['description'] : ['en' => (string) $validated['description']],
            goalAmount: (float) $validated['goal_amount'],
            startDate: (string) $validated['start_date'],
            endDate: (string) $validated['end_date'],
            organizationId: (int) $validated['organization_id'],
            userId: $this->getAuthenticatedUserId($request),
        );

        $campaign = $this->handler->handle($command);

        return ApiResponse::created(
            data: new CampaignResource($campaign),
            message: 'Campaign created successfully.',
        );
    }
}
