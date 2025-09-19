<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\ApiPlatform\Handler\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Notification\Infrastructure\ApiPlatform\Resource\NotificationResource;
use Modules\Shared\Infrastructure\ApiPlatform\State\Paginator;

/**
 * @implements ProviderInterface<NotificationResource>
 */
final readonly class NotificationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private Pagination $pagination,
    ) {}

    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     * @return Paginator<NotificationResource>|array<int, NotificationResource>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Paginator|array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $offset = $limit = null;
        $filters = $context['filters'] ?? [];
        $sorts = ($context['filters'] ?? [])['sort'] ?? [];

        if ($this->pagination->isEnabled($operation, $context)) {
            $offset = $this->pagination->getPage($context);
            $limit = $this->pagination->getLimit($operation, $context);
        }

        // Query the notifications table using Laravel's standard column name
        $query = DB::table('notifications')->where('notifiable_id', (string) $user->id);

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['read_at'])) {
            if ($filters['read_at'] === 'null') {
                $query->whereNull('read_at');
            } else {
                $query->whereNotNull('read_at');
            }
        }

        // Apply sorting
        if (! empty($sorts)) {
            $sortBy = array_key_first($sorts);
            $sortOrder = reset($sorts);
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Get total count for pagination
        $total = $query->count();

        // Apply pagination
        if ($offset && $limit) {
            $query->offset(($offset - 1) * $limit)->limit($limit);
        } elseif ($limit) {
            $query->limit($limit);
        } else {
            $query->limit(50);
        }

        $models = $query->get();

        /** @var Collection<int, NotificationResource> $resources */
        $resources = $models->map(fn ($notification): NotificationResource =>
            // Convert stdClass to NotificationResource
            new NotificationResource(
                id: $notification->id,
                type: $notification->type,
                notifiable_type: $notification->notifiable_type,
                notifiable_id: $notification->notifiable_id,
                data: json_decode((string) $notification->data, true),
                read_at: $notification->read_at,
                created_at: $notification->created_at,
                updated_at: $notification->updated_at,
            ));

        $currentPage = $offset ?: 1;
        $perPage = $limit ?: 50;
        $lastPage = (int) ceil($total / $perPage);

        /** @var ArrayIterator<int, NotificationResource> $iterator */
        $iterator = new ArrayIterator($resources->all());

        return new Paginator(
            $iterator,
            $currentPage,
            $perPage,
            $lastPage,
            $total,
        );
    }
}
