<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Export\Application\Command\RequestDonationExportCommand;
use Modules\Export\Application\Handler\RequestDonationExportCommandHandler;
use Modules\Export\Domain\ValueObject\ExportFormat;

/**
 * @implements ProcessorInterface<array, JsonResponse>
 */
final readonly class CreateExportProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestDonationExportCommandHandler $donationHandler,
        private Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $requestData = $this->request->all();

        // Validate required fields
        if (! isset($requestData['type'])) {
            return response()->json(['message' => 'Export type is required'], 422);
        }

        // Handle different export types
        if ($requestData['type'] !== 'donations') {
            return response()->json(['message' => 'Invalid export type. Only donations export is supported'], 422);
        }

        // Get format or default to CSV
        $format = isset($requestData['format'])
            ? ExportFormat::from(strtolower($requestData['format']))
            : ExportFormat::CSV;

        // Get filters from request
        $filters = $requestData['filters'] ?? [];

        try {
            // Handle donation export
            $exportJob = $this->handleDonationExport($user, $format, $filters);

            return response()->json([
                'id' => $exportJob->export_id,
                'message' => 'Export started successfully',
                'status' => 'pending',
                'type' => $requestData['type'],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to start export',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function handleDonationExport(mixed $user, ExportFormat $format, array $filters): mixed
    {
        /** @var int $userId */
        $userId = $user->id ?? 0;
        /** @var int $organizationId */
        $organizationId = $user->organization_id ?? 1;

        $command = new RequestDonationExportCommand(
            userId: $userId,
            organizationId: $organizationId,
            format: $format,
            filters: $filters,
            dateRangeFrom: $filters['date_from'] ?? null,
            dateRangeTo: $filters['date_to'] ?? null,
            campaignIds: isset($filters['campaign_ids']) ? array_map('intval', $filters['campaign_ids']) : null,
            includeAnonymous: $filters['include_anonymous'] ?? true,
            includeRecurring: $filters['include_recurring'] ?? true,
        );

        return $this->donationHandler->handle($command);
    }
}
