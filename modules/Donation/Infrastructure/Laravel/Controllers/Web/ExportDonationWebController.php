<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Controllers\Web;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Donation\Application\Service\DonationExportService;
use Modules\Donation\Infrastructure\Laravel\Requests\Web\ExportDonationRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class ExportDonationWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private DonationExportService $exportService,
    ) {}

    public function __invoke(ExportDonationRequest $request): Response|JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        // Get properly typed and validated data from request
        $filters = $request->getFilters($user->getId());
        $format = $request->getExportFormat();
        $locale = $request->getLocale();

        try {
            $content = match ($format) {
                'csv' => $this->exportService->exportToCSV($filters, $locale),
                default => throw new InvalidArgumentException('Unsupported export format: ' . $format),
            };

            $filename = $this->generateFilename($format, $filters);

            return response($content, 200, [
                'Content-Type' => $this->getContentType($format),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (Exception $e) {
            Log::error('Donation export failed', [
                'user_id' => $user->getId(),
                'filters' => $filters,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Export failed. Please try again.',
            ], 500);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function generateFilename(string $format, array $filters): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "donations_export_{$timestamp}";

        // Add filter indicators to filename
        if (isset($filters['campaign_id'])) {
            $filename .= '_campaign_' . $filters['campaign_id'];
        }

        if (isset($filters['status'])) {
            $filename .= '_' . $filters['status'];
        }

        if (isset($filters['date_from']) && isset($filters['date_to'])) {
            $filename .= '_' . $filters['date_from'] . '_to_' . $filters['date_to'];

            return $filename . '.' . $format;
        }

        if (isset($filters['date_from'])) {
            $filename .= '_from_' . $filters['date_from'];

            return $filename . '.' . $format;
        }

        if (isset($filters['date_to'])) {
            $filename .= '_until_' . $filters['date_to'];
        }

        return $filename . '.' . $format;
    }

    private function getContentType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv; charset=utf-8',
            default => 'application/octet-stream',
        };
    }
}
