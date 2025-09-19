<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Carbon\CarbonInterface;
use Exception;
use InvalidArgumentException;
use Modules\Notification\Domain\Exception\NotificationException;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Handler for searching notifications query.
 *
 * Provides advanced search capabilities with multiple filters,
 * full-text search, and flexible sorting options.
 */
final readonly class SearchNotificationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof SearchNotificationsQuery) {
            throw new InvalidArgumentException('Expected SearchNotificationsQuery');
        }

        $this->validateQuery($query);

        try {
            $startTime = microtime(true);

            // Build search filters
            $filters = $this->buildSearchFilters($query);

            // Perform the search
            // Add search term to filters if provided
            if ($query->searchTerm) {
                $filters['search'] = $query->searchTerm;
            }

            $results = $this->repository->search(
                filters: $filters,
                sortBy: $query->sortBy,
                sortOrder: $query->sortOrder,
                page: $query->page,
                perPage: $query->perPage,
            );

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log search activity for analytics
            $this->logger->info('Notification search completed', [
                'user_id' => $query->userId,
                'search_term' => $query->searchTerm,
                'filters_count' => count(array_filter($filters, fn ($value): bool => $value !== null)),
                'results_count' => $results->total(),
                'page' => $query->page,
                'per_page' => $query->perPage,
                'execution_time_ms' => $executionTime,
            ]);

            return [
                'data' => $results->items(),
                'pagination' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                    'from' => $results->firstItem(),
                    'to' => $results->lastItem(),
                ],
                'search_meta' => [
                    'term' => $query->searchTerm,
                    'filters_applied' => $this->getAppliedFilters($query),
                    'sort_by' => $query->sortBy,
                    'sort_order' => $query->sortOrder,
                    'execution_time_ms' => $executionTime,
                ],
            ];
        } catch (Exception $e) {
            $this->logger->error('Notification search failed', [
                'user_id' => $query->userId,
                'search_term' => $query->searchTerm,
                'error' => $e->getMessage(),
            ]);

            throw NotificationException::queryFailed(
                "Failed to search notifications: {$e->getMessage()}",
            );
        }
    }

    /**
     * Build search filters from query parameters.
     *
     * @return array<string, mixed>
     */
    private function buildSearchFilters(SearchNotificationsQuery $query): array
    {
        $filters = [
            'notifiable_id' => $query->userId,
        ];

        if ($query->types !== []) {
            $filters['type'] = $query->types;
        }

        if ($query->channels !== []) {
            $filters['channel'] = $query->channels;
        }

        if ($query->statuses !== []) {
            $filters['status'] = $query->statuses;
        }

        if ($query->priorities !== []) {
            $filters['priority'] = $query->priorities;
        }

        if ($query->startDate instanceof CarbonInterface) {
            $filters['created_at_gte'] = $query->startDate;
        }

        if ($query->endDate instanceof CarbonInterface) {
            $filters['created_at_lte'] = $query->endDate;
        }

        if ($query->senderId !== null) {
            $filters['sender_id'] = $query->senderId;
        }

        if ($query->hasAttachments !== null) {
            $filters['has_attachments'] = $query->hasAttachments;
        }

        if ($query->isRecurring !== null) {
            $filters['metadata->is_recurring'] = $query->isRecurring;
        }

        return $filters;
    }

    /**
     * Get a summary of applied filters for response metadata.
     *
     * @return array<string, mixed>
     */
    private function getAppliedFilters(SearchNotificationsQuery $query): array
    {
        $applied = [];

        if ($query->types !== []) {
            $applied['types'] = $query->types;
        }

        if ($query->channels !== []) {
            $applied['channels'] = $query->channels;
        }

        if ($query->statuses !== []) {
            $applied['statuses'] = $query->statuses;
        }

        if ($query->priorities !== []) {
            $applied['priorities'] = $query->priorities;
        }

        if ($query->startDate instanceof CarbonInterface || $query->endDate instanceof CarbonInterface) {
            $applied['date_range'] = [
                'start' => $query->startDate?->toISOString(),
                'end' => $query->endDate?->toISOString(),
            ];
        }

        if ($query->senderId !== null) {
            $applied['sender_id'] = $query->senderId;
        }

        if ($query->hasAttachments !== null) {
            $applied['has_attachments'] = $query->hasAttachments;
        }

        if ($query->isRecurring !== null) {
            $applied['is_recurring'] = $query->isRecurring;
        }

        return $applied;
    }

    /**
     * Validate the search notifications query.
     */
    private function validateQuery(SearchNotificationsQuery $query): void
    {
        if ($query->userId <= 0) {
            throw NotificationException::invalidRecipient('User ID must be a positive integer');
        }

        if ($query->searchTerm !== null && strlen(trim($query->searchTerm)) < 2) {
            throw NotificationException::invalidData(
                'search_term',
                'Search term must be at least 2 characters long',
            );
        }

        if ($query->searchTerm !== null && strlen($query->searchTerm) > 255) {
            throw NotificationException::invalidData(
                'search_term',
                'Search term cannot exceed 255 characters',
            );
        }

        if ($query->startDate instanceof CarbonInterface && $query->endDate instanceof CarbonInterface && $query->startDate->isAfter($query->endDate)) {
            throw NotificationException::invalidData(
                'date_range',
                'Start date cannot be after end date',
            );
        }

        // Validate sort fields
        $validSortFields = ['created_at', 'updated_at', 'title', 'type', 'priority', 'status', 'read_at', 'sent_at'];

        if (! in_array($query->sortBy, $validSortFields, true)) {
            throw NotificationException::invalidData(
                'sort_by',
                'Sort field must be one of: ' . implode(', ', $validSortFields),
            );
        }

        // Validate sort order
        if (! in_array($query->sortOrder, ['asc', 'desc'], true)) {
            throw NotificationException::invalidData(
                'sort_order',
                'Sort order must be either asc or desc',
            );
        }

        // Validate pagination
        if ($query->page < 1) {
            throw NotificationException::invalidData(
                'page',
                'Page must be a positive integer',
            );
        }

        if ($query->perPage < 1 || $query->perPage > 100) {
            throw NotificationException::invalidData(
                'per_page',
                'Per page must be between 1 and 100',
            );
        }

        if ($query->senderId !== null && $query->senderId <= 0) {
            throw NotificationException::invalidData(
                'sender_id',
                'Sender ID must be a positive integer',
            );
        }
    }
}
