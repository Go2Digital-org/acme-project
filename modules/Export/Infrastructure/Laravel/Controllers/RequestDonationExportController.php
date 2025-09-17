<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Export\Application\Command\RequestDonationExportCommand;
use Modules\Export\Application\Handler\RequestDonationExportCommandHandler;
use Modules\Export\Domain\ValueObject\ExportFormat;
use Modules\Export\Infrastructure\Laravel\Requests\RequestExportRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final readonly class RequestDonationExportController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private RequestDonationExportCommandHandler $handler,
    ) {}

    /**
     * Request a new donation export.
     */
    public function __invoke(RequestExportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->getAuthenticatedUser($request);

        $command = new RequestDonationExportCommand(
            userId: $user->id,
            organizationId: $user->organization_id ?? 0,
            format: ExportFormat::from($validated['format']),
            filters: $validated['filters'] ?? [],
            dateRangeFrom: $validated['date_range_from'] ?? null,
            dateRangeTo: $validated['date_range_to'] ?? null,
            campaignIds: $validated['campaign_ids'] ?? null,
            includeAnonymous: $validated['include_anonymous'] ?? true,
            includeRecurring: $validated['include_recurring'] ?? true,
        );

        $export = $this->handler->handle($command);

        return ApiResponse::created(
            data: [
                'export_id' => $export->export_id,
                'status' => $export->getStatusValueObject()->value,
                'format' => $export->getFormatValueObject()->value,
                'requested_at' => $export->created_at?->toIso8601String(),
                'estimated_completion' => $export->getEstimatedTimeRemaining(),
            ],
            message: 'Export request submitted successfully.',
        );
    }
}
