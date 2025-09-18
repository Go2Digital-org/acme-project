<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Modules\Notification\Application\Query\GetNotificationMetricsQuery;
use Modules\Notification\Application\Query\GetNotificationMetricsQueryHandlerInterface;

/**
 * @implements ProcessorInterface<object, array<array-key, mixed>>
 */
final readonly class GetMetricsProcessor implements ProcessorInterface
{
    public function __construct(
        private GetNotificationMetricsQueryHandlerInterface $queryHandler
    ) {}

    /** @return array<array-key, mixed> */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $context['request'] ?? null;

        if (! $request instanceof Request) {
            throw new InvalidArgumentException('Request is required');
        }

        // Get query parameters from the request
        $query = $request->query;

        // Fix 1: Change default values from arrays to null, then handle null case
        $startDateParam = $query->get('startDate', null);
        $endDateParam = $query->get('endDate', null);

        // Handle null case - provide default values
        if ($startDateParam === null) {
            $startDateParam = Carbon::now()->subDays(30)->toDateString();
        }

        if ($endDateParam === null) {
            $endDateParam = Carbon::now()->toDateString();
        }

        // Fix 2 & 3: Cast values to string before passing to Carbon (instead of DateTime)
        $startDate = $this->createCarbonFromString((string) $startDateParam);
        $endDate = $this->createCarbonFromString((string) $endDateParam);

        // Fix 4: Ensure groupBy is string type
        $groupBy = (string) $query->get('groupBy', 'day');

        // Validate groupBy parameter
        if (! in_array($groupBy, ['day', 'week', 'month'], true)) {
            $groupBy = 'day';
        }

        // Get optional user and organization filters
        $userId = $query->get('userId', null);
        $organizationId = $query->get('organizationId', null);

        $metricsQuery = new GetNotificationMetricsQuery(
            startDate: $startDate,
            endDate: $endDate,
            groupBy: $groupBy,
            userId: $userId !== null ? (int) $userId : null,
            organizationId: $organizationId !== null ? (int) $organizationId : null
        );

        return $this->queryHandler->handle($metricsQuery);
    }

    /**
     * Create Carbon instance from string with proper error handling
     */
    private function createCarbonFromString(string $dateString): CarbonInterface
    {
        try {
            return Carbon::parse($dateString);
        } catch (Exception) {
            // Fallback to current date if parsing fails
            return Carbon::now();
        }
    }
}
