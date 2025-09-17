<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Query;

use InvalidArgumentException;
use Modules\Notification\Domain\Repository\NotificationRepositoryInterface;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;

/**
 * Handler for getting unread notification count.
 */
final readonly class GetUnreadNotificationCountQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private NotificationRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): mixed
    {
        if (! $query instanceof GetUnreadNotificationCountQuery) {
            throw new InvalidArgumentException('Expected GetUnreadNotificationCountQuery');
        }

        return $this->repository->countUnreadForRecipient($query->userId);
    }
}
