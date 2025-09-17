<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for getting user notifications query.
 */
final readonly class GetUserNotificationsQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof GetUserNotificationsQuery) {
            throw new InvalidArgumentException('Expected GetUserNotificationsQuery');
        }

        if ($query->unreadOnly) {
            // Get unread notifications
            $notifications = $this->repository->getUnreadNotifications(
                $query->userId,
                $query->perPage,
            );

            // Convert to paginator format (simplified for unread)
            return new LengthAwarePaginator(
                $notifications->all(),
                $notifications->count(),
                $query->perPage,
                $query->page,
            );
        }

        // Build search filters
        $filters = ['notifiable_id' => $query->userId];

        if ($query->status) {
            $filters['status'] = $query->status;
        }

        if ($query->type) {
            $filters['type'] = $query->type;
        }

        if ($query->readAt !== null) {
            $filters['read_at'] = $query->readAt;
        }

        return $this->repository->search(
            filters: $filters,
            sortBy: 'created_at',
            sortOrder: 'desc',
            page: $query->page,
            perPage: $query->perPage,
        );
    }
}
