<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Laravel\Eloquent\Filter\EqualsFilter;
use ApiPlatform\Laravel\Eloquent\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor\ClearAllNotificationsProcessor;
use Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor\GetMetricsProcessor;
use Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor\MarkNotificationAsReadProcessor;
use Modules\Shared\Infrastructure\ApiPlatform\Handler\Processor\SendNotificationProcessor;
use Modules\Shared\Infrastructure\ApiPlatform\Handler\Provider\NotificationCollectionProvider;

#[ApiResource(
    shortName: 'Notification',
    operations: [
        new GetCollection(
            uriTemplate: '/notifications',
            paginationEnabled: true,
            paginationItemsPerPage: 50,
            paginationMaximumItemsPerPage: 200,
            paginationClientItemsPerPage: true,
            security: "is_granted('ROLE_USER')",
            provider: NotificationCollectionProvider::class,
            parameters: [
                'type' => new QueryParameter(key: 'type', filter: EqualsFilter::class),
                'read_at' => new QueryParameter(key: 'read_at', filter: EqualsFilter::class),
                'sort[:property]' => new QueryParameter(key: 'sort[:property]', filter: OrderFilter::class),
            ],
        ),
        new Post(
            uriTemplate: '/notifications/{id}/read',
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_USER')",
            processor: MarkNotificationAsReadProcessor::class,
        ),
        new Post(
            uriTemplate: '/notifications/clear',
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_USER')",
            processor: ClearAllNotificationsProcessor::class,
        ),
        new Post(
            uriTemplate: '/notifications/send',
            status: Response::HTTP_CREATED,
            security: "is_granted('ROLE_ADMIN')",
            processor: SendNotificationProcessor::class,
        ),
        new Get(
            uriTemplate: '/notifications/metrics',
            status: Response::HTTP_OK,
            security: "is_granted('ROLE_ADMIN')",
            processor: GetMetricsProcessor::class,
        ),
    ],
    middleware: ['auth:sanctum', 'api.locale'],
)]
class NotificationResource
{
    public function __construct(
        public ?string $id = null,
        public ?string $type = null,
        public ?string $notifiable_type = null,
        public ?int $notifiable_id = null,
        /** @var array<string, mixed>|null */
        public ?array $data = null,
        public ?string $read_at = null,
        public ?string $created_at = null,
        public ?string $updated_at = null,
    ) {}

    public static function fromModel(DatabaseNotification $notification): self
    {
        return new self(
            id: $notification->getAttribute('id'),
            type: $notification->getAttribute('type'),
            notifiable_type: $notification->getAttribute('notifiable_type'),
            notifiable_id: (int) $notification->getAttribute('notifiable_id'),
            data: $notification->getAttribute('data'),
            read_at: $notification->getAttribute('read_at')?->toDateTimeString(),
            created_at: $notification->getAttribute('created_at')?->toDateTimeString(),
            updated_at: $notification->getAttribute('updated_at')?->toDateTimeString(),
        );
    }
}
